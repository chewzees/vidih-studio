<?php
$vidihConfig = [
    'ffmpeg' => getenv('VIDIH_FFMPEG') ?: 'C:\\ffmpeg\\bin\\ffmpeg.exe',
    'max_upload_bytes' => 500 * 1024 * 1024,
    'file_ttl_seconds' => 7 * 86400,
    'video_extensions' => ['mp4', 'mov', 'webm', 'mkv', 'avi', 'm4v'],
    'audio_extensions' => ['mp3', 'wav', 'm4a', 'aac', 'ogg', 'flac'],
    'video_mimes' => ['video/mp4', 'video/quicktime', 'video/webm', 'video/x-matroska', 'video/avi', 'video/x-msvideo', 'application/octet-stream'],
    'audio_mimes' => ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/mp4', 'audio/aac', 'audio/ogg', 'audio/flac', 'application/octet-stream'],
];

function vidih_json(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function vidih_upload_error_message(int $errorCode): string
{
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'That file is too large for the server upload limit.';
        case UPLOAD_ERR_PARTIAL:
            return 'Upload was interrupted. Try again.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was received.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Server temp folder is missing.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Server could not write the uploaded file.';
        case UPLOAD_ERR_EXTENSION:
            return 'Upload blocked by a PHP extension.';
        default:
            return 'Upload failed.';
    }
}

function vidih_ensure_dir(string $dir): void
{
    if (!is_dir($dir) && !mkdir($dir, 0775, true)) {
        vidih_json(['ok' => false, 'message' => 'Could not create required folders.'], 500);
    }
}

function vidih_ffmpeg_path(array $config): string
{
    $path = (string) $config['ffmpeg'];
    if (!is_file($path)) {
        vidih_json([
            'ok' => false,
            'message' => 'FFmpeg was not found. Set VIDIH_FFMPEG or install it at C:\\ffmpeg\\bin\\ffmpeg.exe.',
            'details' => $path,
        ], 500);
    }
    return $path;
}

function vidih_detect_mime(string $tmpPath): string
{
    if (!is_file($tmpPath) || !class_exists('finfo')) {
        return '';
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpPath);
    return is_string($mime) ? strtolower($mime) : '';
}

function vidih_validate_media(array $file, array $allowedExtensions, array $allowedMimes, int $maxBytes, string $kind): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        vidih_json(['ok' => false, 'message' => vidih_upload_error_message($error)], 400);
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        vidih_json(['ok' => false, 'message' => "Empty {$kind} file."], 400);
    }
    if ($size > $maxBytes) {
        $limitMb = (int) round($maxBytes / (1024 * 1024));
        vidih_json(['ok' => false, 'message' => "{$kind} is too large. Max size is {$limitMb} MB."], 400);
    }

    $originalName = basename((string) ($file['name'] ?? 'file'));
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        vidih_json(['ok' => false, 'message' => "Unsupported {$kind} format. Allowed: " . implode(', ', $allowedExtensions)], 400);
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $mime = vidih_detect_mime($tmp);
    if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
        vidih_json(['ok' => false, 'message' => "File MIME type is not an allowed {$kind} type ({$mime})."], 400);
    }

    return [$originalName, $extension, $tmp];
}

function vidih_safe_filename(string $originalName, string $extension, string $prefix = ''): string
{
    $safeBase = preg_replace('/[^a-zA-Z0-9_-]+/', '-', pathinfo($originalName, PATHINFO_FILENAME));
    $safeBase = trim((string) $safeBase, '-') ?: 'file';
    $prefix = $prefix !== '' ? $prefix . '-' : '';
    return $prefix . $safeBase . '-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
}

function vidih_relative_media_path(string $relativePath, string $allowedFolder = 'uploads'): ?string
{
    $relativePath = str_replace('\\', '/', $relativePath);
    if (!preg_match('#^' . preg_quote($allowedFolder, '#') . '/[a-zA-Z0-9._-]+$#', $relativePath)) {
        return null;
    }
    $absolute = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    return is_file($absolute) ? $absolute : null;
}

function vidih_cleanup(array $config): void
{
    $ttl = (int) $config['file_ttl_seconds'];
    if ($ttl <= 0) {
        return;
    }
    $cutoff = time() - $ttl;
    foreach (['uploads', 'exports'] as $folder) {
        $dir = __DIR__ . DIRECTORY_SEPARATOR . $folder;
        if (!is_dir($dir)) {
            continue;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($path)) {
                continue;
            }
            $mtime = filemtime($path);
            if ($mtime !== false && $mtime < $cutoff) {
                @unlink($path);
            }
        }
    }
}

function vidih_handle_api(array $vidihConfig): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }

    $action = (string) ($_POST['action'] ?? '');
    if ($action === '') {
        return;
    }

    vidih_cleanup($vidihConfig);

    if (in_array($action, ['upload_video', 'upload_merge_clip'], true)) {
        [$originalName, $extension, $tmp] = vidih_validate_media(
            $_FILES['video'] ?? [],
            $vidihConfig['video_extensions'],
            $vidihConfig['video_mimes'],
            $vidihConfig['max_upload_bytes'],
            'video'
        );

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        vidih_ensure_dir($uploadDir);
        $prefix = $action === 'upload_merge_clip' ? 'merge' : 'video';
        $fileName = vidih_safe_filename($originalName, $extension, $prefix);
        $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmp, $target)) {
            vidih_json(['ok' => false, 'message' => 'Could not save uploaded video.'], 500);
        }

        vidih_json([
            'ok' => true,
            'name' => $originalName,
            'path' => 'uploads/' . $fileName,
            'size' => filesize($target) ?: 0,
        ]);
    }

    if ($action === 'upload_audio') {
        [$originalName, $extension, $tmp] = vidih_validate_media(
            $_FILES['audio'] ?? [],
            $vidihConfig['audio_extensions'],
            $vidihConfig['audio_mimes'],
            $vidihConfig['max_upload_bytes'],
            'audio'
        );

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        vidih_ensure_dir($uploadDir);
        $fileName = vidih_safe_filename($originalName, $extension, 'audio');
        $target = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

        if (!move_uploaded_file($tmp, $target)) {
            vidih_json(['ok' => false, 'message' => 'Could not save uploaded audio.'], 500);
        }

        vidih_json([
            'ok' => true,
            'name' => $originalName,
            'path' => 'uploads/' . $fileName,
        ]);
    }

    if ($action === 'merge_videos') {
        $ffmpegPath = vidih_ffmpeg_path($vidihConfig);
        $clipPaths = [];

        if (!empty($_POST['clip_paths'])) {
            $decodedPaths = json_decode((string) $_POST['clip_paths'], true);
            if (is_array($decodedPaths)) {
                foreach ($decodedPaths as $relativePath) {
                    $absolutePath = vidih_relative_media_path((string) $relativePath, 'uploads');
                    if ($absolutePath === null) {
                        vidih_json(['ok' => false, 'message' => 'Invalid merge clip path.'], 400);
                    }
                    $clipPaths[] = $absolutePath;
                }
            }
        }

        if (count($clipPaths) < 2) {
            vidih_json(['ok' => false, 'message' => 'Choose at least two clips to merge.'], 400);
        }

        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        $exportDir = __DIR__ . DIRECTORY_SEPARATOR . 'exports';
        vidih_ensure_dir($uploadDir);
        vidih_ensure_dir($exportDir);

        $listPath = $uploadDir . DIRECTORY_SEPARATOR . 'merge-list-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.txt';
        $listLines = array_map(static function ($clipPath) {
            return "file '" . str_replace("'", "'\\''", str_replace('\\', '/', $clipPath)) . "'";
        }, $clipPaths);
        file_put_contents($listPath, implode(PHP_EOL, $listLines));

        $exportName = 'merged-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.mp4';
        $exportPath = $exportDir . DIRECTORY_SEPARATOR . $exportName;

        $command = escapeshellarg($ffmpegPath)
            . ' -y -f concat -safe 0 -i ' . escapeshellarg($listPath)
            . ' -c:v libx264 -preset veryfast -crf 23 -c:a aac -b:a 192k -movflags +faststart '
            . escapeshellarg($exportPath) . ' 2>&1';
        exec($command, $output, $exitCode);
        @unlink($listPath);

        if ($exitCode !== 0 || !is_file($exportPath)) {
            vidih_json([
                'ok' => false,
                'message' => 'FFmpeg could not merge those clips.',
                'details' => implode("\n", array_slice($output, -8)),
            ], 500);
        }

        vidih_json([
            'ok' => true,
            'path' => 'exports/' . $exportName,
            'name' => $exportName,
        ]);
    }

    if ($action === 'export_edited') {
        $ffmpegPath = vidih_ffmpeg_path($vidihConfig);

        $videoPath = str_replace('\\', '/', (string) ($_POST['video_path'] ?? ''));
        $inputPath = vidih_relative_media_path($videoPath, 'uploads');
        if ($inputPath === null) {
            vidih_json(['ok' => false, 'message' => 'Upload a main video before exporting.'], 400);
        }

        $exportDir = __DIR__ . DIRECTORY_SEPARATOR . 'exports';
        vidih_ensure_dir($exportDir);

        $start = max(0, (float) ($_POST['trim_start'] ?? 0));
        $end = max(0, (float) ($_POST['trim_end'] ?? 0));
        $duration = $end > $start ? $end - $start : 0;
        $muted = ($_POST['muted'] ?? '0') === '1';
        $filter = (string) ($_POST['filter'] ?? 'none');
        $textClips = json_decode((string) ($_POST['text_clips'] ?? '[]'), true);
        $textClips = is_array($textClips) ? $textClips : [];
        $audioRelative = str_replace('\\', '/', (string) ($_POST['audio_path'] ?? ''));
        $audioPath = $audioRelative !== '' ? vidih_relative_media_path($audioRelative, 'uploads') : null;
        if ($audioRelative !== '' && $audioPath === null) {
            vidih_json(['ok' => false, 'message' => 'Invalid music file path.'], 400);
        }

        $filters = [];
        if ($filter === 'grayscale(1)') {
            $filters[] = 'hue=s=0';
        } elseif ($filter === 'sepia(.85)') {
            $filters[] = 'colorchannelmixer=.393:.769:.189:0:.349:.686:.168:0:.272:.534:.131';
        } elseif ($filter === 'contrast(1.25) saturate(1.35)') {
            $filters[] = 'eq=contrast=1.25:saturation=1.35';
        } elseif ($filter === 'brightness(1.12) contrast(.92)') {
            $filters[] = 'eq=brightness=0.08:contrast=0.92';
        }

        $fontCandidates = [
            'C:/Windows/Fonts/NotoSansSC-VF.ttf',
            'C:/Windows/Fonts/msyh.ttc',
            'C:/Windows/Fonts/msgothic.ttc',
            'C:/Windows/Fonts/YuGothB.ttc',
            'C:/Windows/Fonts/malgun.ttf',
            'C:/Windows/Fonts/simsun.ttc',
            'C:/Windows/Fonts/arial.ttf',
        ];
        $fontPath = 'C:/Windows/Fonts/arial.ttf';
        foreach ($fontCandidates as $candidate) {
            if (is_file($candidate)) {
                $fontPath = $candidate;
                break;
            }
        }
        $ffmpegFontPath = str_replace(':', '\:', str_replace('\\', '/', $fontPath));
        $tempTextFiles = [];

        foreach ($textClips as $clipIndex => $clip) {
            $text = trim((string) ($clip['text'] ?? ''));
            $clipStart = (float) ($clip['start'] ?? 0);
            $clipEnd = (float) ($clip['end'] ?? 0);
            if ($text === '' || $clipEnd <= $clipStart) {
                continue;
            }

            $textFile = $exportDir . DIRECTORY_SEPARATOR . 'subtitle-' . date('Ymd-His') . '-' . $clipIndex . '-' . bin2hex(random_bytes(3)) . '.txt';
            file_put_contents($textFile, "\xEF\xBB\xBF" . $text);
            $tempTextFiles[] = $textFile;
            $ffmpegTextFile = str_replace(':', '\:', str_replace('\\', '/', $textFile));
            $xPercent = min(96, max(4, (float) ($clip['x'] ?? 50))) / 100;
            $yPercent = min(94, max(6, (float) ($clip['y'] ?? 82))) / 100;
            $xExpr = '(w-text_w)*' . number_format($xPercent, 4, '.', '');
            $yExpr = '(h-text_h)*' . number_format($yPercent, 4, '.', '');
            $filters[] = "drawtext=fontfile='{$ffmpegFontPath}':textfile='{$ffmpegTextFile}':fontcolor=white:fontsize=42:box=1:boxcolor=black@0.58:boxborderw=12:x={$xExpr}:y={$yExpr}:enable='between(t,{$clipStart},{$clipEnd})'";
        }

        $exportName = 'edited-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.mp4';
        $exportPath = $exportDir . DIRECTORY_SEPARATOR . $exportName;

        $command = escapeshellarg($ffmpegPath) . ' -y';
        if ($start > 0) {
            $command .= ' -ss ' . escapeshellarg((string) $start);
        }
        $command .= ' -i ' . escapeshellarg($inputPath);
        if ($duration > 0) {
            $command .= ' -t ' . escapeshellarg((string) $duration);
        }

        if ($audioPath) {
            $command .= ' -stream_loop -1 -i ' . escapeshellarg($audioPath);
        }

        if ($filters) {
            $command .= ' -vf ' . escapeshellarg(implode(',', $filters));
        }

        $command .= ' -c:v libx264 -preset veryfast -crf 23';

        if ($audioPath && $muted) {
            $command .= ' -map 0:v:0 -map 1:a:0 -c:a aac -b:a 192k -shortest';
        } elseif ($audioPath && !$muted) {
            $command .= ' -filter_complex ' . escapeshellarg('[0:a][1:a]amix=inputs=2:duration=first:dropout_transition=2[aout]')
                . ' -map 0:v:0 -map [aout] -c:a aac -b:a 192k';
        } else {
            $command .= $muted ? ' -an' : ' -c:a aac -b:a 192k';
        }

        $command .= ' -movflags +faststart ' . escapeshellarg($exportPath) . ' 2>&1';

        exec($command, $output, $exitCode);
        foreach ($tempTextFiles as $tempTextFile) {
            @unlink($tempTextFile);
        }

        if ($exitCode !== 0 || !is_file($exportPath)) {
            vidih_json([
                'ok' => false,
                'message' => 'FFmpeg could not export the edited video.',
                'details' => implode("\n", array_slice($output, -10)),
            ], 500);
        }

        vidih_json([
            'ok' => true,
            'path' => 'exports/' . $exportName,
            'name' => $exportName,
        ]);
    }

    vidih_json(['ok' => false, 'message' => 'Unknown action.'], 400);
}
