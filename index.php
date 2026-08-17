<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'vidih_api.php';
vidih_handle_api($vidihConfig);
$maxUploadMb = (int) round($vidihConfig['max_upload_bytes'] / (1024 * 1024));
$features = [
    ['id' => 'trim', 'title' => 'Cut', 'description' => 'Set start & end points', 'icon' => '✂'],
    ['id' => 'merge', 'title' => 'Merge', 'description' => 'Join clips into one', 'icon' => '⧉'],
    ['id' => 'text', 'title' => 'Text', 'description' => 'Titles on the timeline', 'icon' => 'T'],
    ['id' => 'music', 'title' => 'Music', 'description' => 'Preview with audio', 'icon' => '♪'],
    ['id' => 'mute', 'title' => 'Mute', 'description' => 'Remove video sound', 'icon' => '⊘'],
    ['id' => 'filter', 'title' => 'Filters', 'description' => 'Quick look styles', 'icon' => '◉'],
];
?><!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vidih Studio</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            color-scheme: light;
            --ink: #14221f;
            --muted: #5a6b66;
            --line: #d5e0db;
            --panel: rgba(255, 255, 255, 0.92);
            --panel-solid: #ffffff;
            --bg: #eef3f0;
            --bg-deep: #d9e8e1;
            --accent: #0f7a6c;
            --accent-dark: #0a5a50;
            --accent-soft: #dff3ee;
            --accent-glow: rgba(15, 122, 108, 0.18);
            --warn: #b45309;
            --danger: #b42318;
            --success: #117a54;
            --cinema: #101816;
            --radius: 14px;
            --shadow: 0 12px 40px rgba(20, 34, 31, 0.08);
            --shadow-sm: 0 2px 10px rgba(20, 34, 31, 0.05);
            --focus: 0 0 0 3px rgba(15, 122, 108, 0.28);
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: Figtree, "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(1200px 600px at 12% -10%, #c8e6dc 0%, transparent 55%),
                radial-gradient(900px 500px at 100% 0%, #dfe9f2 0%, transparent 50%),
                linear-gradient(180deg, var(--bg) 0%, var(--bg-deep) 100%);
            background-attachment: fixed;
        }

        button,
        input,
        select { font: inherit; }

        button {
            cursor: pointer;
            transition: background .18s ease, border-color .18s ease, transform .15s ease, box-shadow .18s ease, opacity .18s ease;
        }

        button:focus-visible,
        input:focus-visible,
        select:focus-visible,
        .dropzone:focus-visible,
        .scrubber:focus-visible {
            outline: none;
            box-shadow: var(--focus);
        }

        button:disabled {
            opacity: .55;
            cursor: not-allowed;
            transform: none !important;
        }

        .shell {
            width: min(1240px, calc(100% - 28px));
            margin: 0 auto;
            padding: 22px 0 48px;
        }

        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(145deg, #12907f, #0c5f56);
            color: #fff;
            font-family: Sora, Figtree, sans-serif;
            font-size: 15px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(15, 122, 108, 0.28);
        }

        .brand {
            font-family: Sora, Figtree, sans-serif;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1.1;
        }

        .brand small {
            display: block;
            margin-top: 2px;
            font-family: Figtree, sans-serif;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            letter-spacing: 0;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            max-width: min(420px, 100%);
            padding: 9px 14px;
            border: 1px solid var(--line);
            border-radius: 999px;
            color: var(--muted);
            background: var(--panel);
            font-size: 13px;
            font-weight: 500;
            box-shadow: var(--shadow-sm);
        }

        .status::before {
            content: "";
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #94a3b8;
            flex-shrink: 0;
        }

        .status.is-busy::before {
            background: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
            animation: pulse 1.2s ease infinite;
        }

        .status.is-ok {
            color: var(--success);
            border-color: #b7e4d0;
            background: #f0faf5;
        }

        .status.is-ok::before { background: var(--success); }

        .status.is-error {
            color: var(--danger);
            border-color: #f1c4c0;
            background: #fff5f4;
        }

        .status.is-error::before { background: var(--danger); }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .45; }
        }

        .steps {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .step {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px 7px 7px;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--panel);
            box-shadow: var(--shadow-sm);
        }

        .step-num {
            display: grid;
            place-items: center;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: var(--accent-soft);
            color: var(--accent-dark);
            font-size: 11px;
            font-weight: 700;
            flex-shrink: 0;
        }

        .step strong {
            display: inline;
            font-size: 13px;
            font-weight: 650;
        }

        .step span {
            display: none;
        }

        @media (min-width: 820px) {
            .step span {
                display: inline;
                color: var(--muted);
                font-size: 12px;
            }

            .step span::before {
                content: "·";
                margin: 0 6px 0 2px;
                color: #a7b8b2;
            }
        }

        .step.is-done .step-num {
            background: var(--accent);
            color: #fff;
        }

        .workspace {
            display: grid;
            grid-template-columns: 270px minmax(0, 1fr) 300px;
            gap: 16px;
            align-items: start;
        }

        .panel,
        .toolbox,
        .preview-panel {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--panel);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
        }

        .panel { padding: 18px; }

        .panel-label {
            margin: 0 0 6px;
            color: var(--accent-dark);
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .panel h1,
        .panel h2,
        .toolbox h2,
        .controls-head h2 {
            margin: 0 0 8px;
            font-family: Sora, Figtree, sans-serif;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .panel p,
        .hint {
            margin: 0 0 14px;
            color: var(--muted);
            line-height: 1.5;
            font-size: 14px;
        }

        .uploader {
            display: grid;
            gap: 12px;
            margin-top: 4px;
        }

        .dropzone {
            display: grid;
            gap: 6px;
            place-items: center;
            min-height: 132px;
            padding: 18px 14px;
            border: 1.5px dashed #9db8af;
            border-radius: var(--radius);
            background:
                linear-gradient(180deg, rgba(255,255,255,.7), rgba(223,243,238,.55));
            color: var(--muted);
            text-align: center;
            cursor: pointer;
            transition: border-color .18s ease, background .18s ease, transform .15s ease;
        }

        .dropzone:hover,
        .dropzone.is-dragover {
            border-color: var(--accent);
            background: var(--accent-soft);
            transform: translateY(-1px);
        }

        .dropzone strong {
            color: var(--ink);
            font-size: 15px;
            font-weight: 650;
        }

        .dropzone span {
            font-size: 12px;
            line-height: 1.4;
        }

        .dropzone .browse {
            color: var(--accent-dark);
            font-weight: 700;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .dropzone input {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            pointer-events: none;
        }

        .file-label {
            display: grid;
            gap: 8px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        input[type="file"],
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            min-height: 44px;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 8px 12px;
            background: var(--panel-solid);
            color: var(--ink);
        }

        input[type="file"] {
            padding: 10px;
            background: #f7faf8;
        }

        .toolbox {
            overflow: hidden;
            display: grid;
            grid-template-rows: auto auto 1fr;
        }

        .toolbox-head {
            padding: 16px 16px 8px;
        }

        .tools {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            padding: 0 16px 14px;
        }

        .tool-button {
            display: grid;
            grid-template-columns: 34px minmax(0, 1fr);
            gap: 8px;
            align-items: center;
            width: 100%;
            min-height: 64px;
            border: 1px solid var(--line);
            border-radius: 12px;
            padding: 10px;
            background: var(--panel-solid);
            color: var(--ink);
            text-align: left;
        }

        .tool-button:hover {
            border-color: #b4cbc2;
            transform: translateY(-1px);
        }

        .tool-icon {
            display: grid;
            place-items: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #eef4f1;
            color: var(--accent-dark);
            font-family: Sora, Figtree, sans-serif;
            font-size: 14px;
            font-weight: 700;
        }

        .tool-button strong {
            display: block;
            margin-bottom: 2px;
            font-size: 13px;
            font-weight: 650;
        }

        .tool-button span {
            display: block;
            color: var(--muted);
            font-size: 11px;
            line-height: 1.3;
        }

        .tool-button.is-active {
            border-color: var(--accent);
            background: var(--accent-soft);
            box-shadow: inset 0 0 0 1px rgba(15, 122, 108, 0.18);
        }

        .tool-button.is-active .tool-icon {
            background: var(--accent);
            color: #fff;
        }

        .preview-panel {
            padding: 14px;
        }

        .preview-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .preview-top h2 {
            margin: 0;
            font-family: Sora, Figtree, sans-serif;
            font-size: 16px;
            letter-spacing: -0.02em;
        }

        .video-wrap {
            position: relative;
            display: grid;
            place-items: center;
            min-height: 420px;
            border-radius: 12px;
            background:
                radial-gradient(circle at 30% 20%, rgba(18, 144, 127, 0.18), transparent 45%),
                var(--cinema);
            overflow: hidden;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
        }

        video {
            width: 100%;
            max-height: 520px;
            background: transparent;
        }

        .empty-preview {
            position: absolute;
            inset: 0;
            display: grid;
            place-items: center;
            gap: 8px;
            padding: 28px;
            color: #d7e5df;
            text-align: center;
            line-height: 1.5;
        }

        .empty-preview strong {
            display: block;
            font-family: Sora, Figtree, sans-serif;
            font-size: 18px;
            color: #fff;
            margin-bottom: 4px;
        }

        .empty-preview span {
            max-width: 280px;
            font-size: 14px;
            opacity: .85;
        }

        .overlay-text {
            position: absolute;
            left: 50%;
            top: 82%;
            transform: translate(-50%, -50%);
            max-width: 86%;
            padding: 8px 12px;
            border: 0;
            border-radius: 8px;
            color: #fff;
            background: rgba(0, 0, 0, .58);
            font-size: clamp(18px, 4vw, 34px);
            font-weight: 700;
            text-align: center;
            overflow-wrap: anywhere;
            display: none;
            cursor: grab;
            user-select: none;
            touch-action: none;
        }

        .overlay-text:active { cursor: grabbing; }

        .editor-timeline {
            margin-top: 12px;
            padding: 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fbfdfc;
        }

        .timeline-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .scrubber {
            position: relative;
            height: 52px;
            border-radius: 10px;
            background:
                repeating-linear-gradient(
                    90deg,
                    #e4ece8 0,
                    #e4ece8 1px,
                    transparent 1px,
                    transparent 10%
                ),
                #e8f0ec;
            cursor: pointer;
            touch-action: none;
            overflow: hidden;
        }

        .trim-range {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 100%;
            background: rgba(15, 122, 108, .22);
        }

        .playhead,
        .trim-handle {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 4px;
            transform: translateX(-50%);
            border: 0;
            padding: 0;
            background: var(--accent);
            cursor: grab;
        }

        .trim-handle {
            width: 16px;
            border-radius: 8px;
            background: #14221f;
        }

        .trim-handle::after {
            content: attr(aria-label);
            position: absolute;
            left: 50%;
            bottom: 5px;
            transform: translateX(-50%);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
        }

        .playhead { z-index: 3; width: 3px; }
        .trim-start,
        .trim-end { z-index: 2; }

        .timeline-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 12px;
            color: var(--muted);
            font-size: 13px;
        }

        .pill {
            border: 1px solid var(--line);
            border-radius: 999px;
            padding: 6px 10px;
            background: #fff;
            font-weight: 500;
        }

        .controls-panel {
            border-top: 1px solid var(--line);
            padding: 16px;
            background: linear-gradient(180deg, #f7fbf9, #fff);
        }

        .control-group {
            display: none;
            gap: 12px;
        }

        .control-group.is-active { display: grid; }

        .field {
            display: grid;
            gap: 6px;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
        }

        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .action {
            min-height: 44px;
            border: 0;
            border-radius: 11px;
            padding: 0 14px;
            color: #fff;
            background: linear-gradient(180deg, #149285, var(--accent));
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(15, 122, 108, 0.22);
        }

        .action:hover:not(:disabled) {
            background: var(--accent-dark);
            transform: translateY(-1px);
        }

        .action:active:not(:disabled) { transform: translateY(0); }

        .secondary {
            color: var(--ink);
            background: #e7efeb;
            box-shadow: none;
        }

        .secondary:hover:not(:disabled) { background: #d8e4df; }

        .export-stack {
            display: grid;
            gap: 10px;
            margin-top: 4px;
            padding-top: 12px;
            border-top: 1px dashed var(--line);
        }

        .note {
            min-height: 44px;
            border-left: 3px solid var(--accent);
            border-radius: 0 10px 10px 0;
            padding: 10px 12px;
            background: var(--accent-soft);
            color: #124f45;
            line-height: 1.45;
            font-size: 13px;
        }

        .note.is-error {
            border-left-color: var(--danger);
            background: #fff5f4;
            color: #7a221c;
        }

        .note a {
            color: var(--accent-dark);
            font-weight: 700;
        }

        .progress {
            display: none;
            width: min(280px, 100%);
            height: 6px;
            border: 0;
            border-radius: 999px;
            overflow: hidden;
            background: #d9e6e1;
        }

        .progress.is-on { display: block; }

        .progress::-webkit-progress-bar { background: #d9e6e1; }
        .progress::-webkit-progress-value {
            background: linear-gradient(90deg, #149285, #0f7a6c);
            transition: width .2s ease;
        }
        .progress::-moz-progress-bar {
            background: linear-gradient(90deg, #149285, #0f7a6c);
        }

        .result-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .text-track {
            position: relative;
            height: 34px;
            margin-top: 4px;
            border-radius: 8px;
            background: #eef2f0;
            overflow: hidden;
        }

        .text-clip-marker {
            position: absolute;
            top: 6px;
            bottom: 6px;
            min-width: 8px;
            border-radius: 6px;
            background: #0f7a6c;
        }

        .merge-list,
        .text-list {
            display: grid;
            gap: 8px;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .merge-list:empty::before,
        .text-list:empty::before {
            content: attr(data-empty);
            display: block;
            padding: 14px;
            border: 1px dashed var(--line);
            border-radius: 10px;
            color: var(--muted);
            font-size: 13px;
            text-align: center;
            background: #f7faf8;
        }

        .merge-list li,
        .text-list li {
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px 12px;
            color: var(--muted);
            overflow-wrap: anywhere;
            background: #fff;
            font-size: 13px;
        }

        .merge-list li {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
        }

        .merge-list .clip-actions {
            margin-top: 0;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .text-list strong {
            display: block;
            margin-bottom: 4px;
            color: var(--ink);
        }

        .clip-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
        }

        .mini-action {
            min-height: 32px;
            border: 0;
            border-radius: 8px;
            padding: 0 10px;
            color: var(--ink);
            background: #e7efeb;
            font-size: 12px;
            font-weight: 600;
        }

        .mini-action:hover { background: #d5e2dc; }

        .filter-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }

        .filter-chip {
            display: grid;
            gap: 8px;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            text-align: left;
            color: var(--ink);
        }

        .filter-chip.is-active {
            border-color: var(--accent);
            background: var(--accent-soft);
        }

        .filter-swatch {
            height: 36px;
            border-radius: 8px;
            background: linear-gradient(120deg, #1f9d88, #5ec4b0 45%, #f2c14e);
        }

        .filter-swatch.bw { filter: grayscale(1); }
        .filter-swatch.sepia { filter: sepia(.85); }
        .filter-swatch.vivid { filter: contrast(1.25) saturate(1.35); }
        .filter-swatch.soft { filter: brightness(1.12) contrast(.92); }

        .filter-chip strong {
            font-size: 13px;
            font-weight: 650;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            border: 0;
        }

        @media (max-width: 1080px) {
            .workspace {
                grid-template-columns: 240px minmax(0, 1fr);
            }

            .toolbox {
                grid-column: 1 / -1;
            }

            .tools {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .workspace {
                grid-template-columns: 1fr;
            }

            .workspace .preview-panel { order: -1; }

            .video-wrap { min-height: 280px; }

            .tools {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 560px) {
            .row,
            .timeline-actions,
            .filter-grid {
                grid-template-columns: 1fr;
            }

            header {
                align-items: flex-start;
            }

            .status {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <main class="shell">
        <header>
            <div class="brand-block">
                <div class="brand-mark" aria-hidden="true">V</div>
                <div class="brand">Vidih Studio<small>Simple video editing in your browser</small></div>
            </div>
            <div class="header-actions">
                <progress class="progress" id="jobProgress" max="100" value="0" aria-label="Upload or export progress"></progress>
                <button class="action secondary" type="button" id="clearProject" title="Clear saved edits and project state">Clear project</button>
                <div class="status" id="statusText" role="status">Ready — drop a video to begin</div>
            </div>
        </header>

        <nav class="steps" aria-label="Editing steps">
            <div class="step" id="stepUpload">
                <span class="step-num">1</span>
                <div>
                    <strong>Upload</strong>
                    <span>Add your main video</span>
                </div>
            </div>
            <div class="step" id="stepEdit">
                <span class="step-num">2</span>
                <div>
                    <strong>Edit</strong>
                    <span>Trim, text, music &amp; filters</span>
                </div>
            </div>
            <div class="step" id="stepExport">
                <span class="step-num">3</span>
                <div>
                    <strong>Export</strong>
                    <span>Download your finished MP4</span>
                </div>
            </div>
        </nav>

        <section class="workspace" aria-label="Vidih Studio editor">
            <aside class="panel">
                <p class="panel-label">Library</p>
                <h1>Your project</h1>
                <p class="hint">Start with a main video. Optional audio powers the Music tool.</p>

                <div class="uploader">
                    <label class="dropzone" id="videoDropzone" for="videoInput" tabindex="0">
                        <strong>Drop video here</strong>
                        <span>or <span class="browse">browse files</span> · MP4, MOV, WEBM · up to <?php echo (int) $maxUploadMb; ?> MB</span>
                        <input id="videoInput" type="file" accept="video/*">
                    </label>
                    <label class="file-label">
                        Background music (optional)
                        <input id="audioInput" type="file" accept="audio/*">
                    </label>
                    <div class="export-stack">
                        <button class="action" type="button" id="exportEdited" disabled>Export edited video</button>
                        <div class="note" id="exportEditedResult" style="display: none;"></div>
                    </div>
                </div>
            </aside>

            <section class="preview-panel">
                <div class="preview-top">
                    <h2>Preview</h2>
                    <span class="pill" id="modeText">Tool: Cut</span>
                </div>
                <div class="video-wrap">
                    <video id="videoPreview" controls playsinline></video>
                    <div class="empty-preview" id="emptyPreview">
                        <div>
                            <strong>No video yet</strong>
                            <span>Upload a file on the left, then use Cut, Text, or Filters while you preview here.</span>
                        </div>
                    </div>
                    <button class="overlay-text" id="overlayText" type="button" aria-label="Drag text overlay"></button>
                </div>
                <div class="editor-timeline" aria-label="Video trim timeline">
                    <div class="timeline-head">
                        <span id="currentTimeText">Current: 0:00</span>
                        <span id="trimRangeText">Trim: 0:00 - 0:00</span>
                    </div>
                    <div class="scrubber" id="scrubber" role="slider" aria-label="Video seeker" aria-valuemin="0" aria-valuemax="0" aria-valuenow="0" tabindex="0">
                        <div class="trim-range" id="trimRange"></div>
                        <button class="trim-handle trim-start" id="startHandle" type="button" aria-label="S"></button>
                        <button class="trim-handle trim-end" id="endHandle" type="button" aria-label="E"></button>
                        <button class="playhead" id="playhead" type="button" aria-label="Current time"></button>
                    </div>
                    <div class="timeline-actions">
                        <button class="action secondary" type="button" id="markStart">Set start here</button>
                        <button class="action secondary" type="button" id="markEnd">Set end here</button>
                    </div>
                </div>
                <div class="meta" aria-live="polite">
                    <span class="pill" id="fileName">No file selected</span>
                    <span class="pill" id="durationText">Duration: 0:00</span>
                </div>
            </section>

            <aside class="toolbox">
                <div class="toolbox-head">
                    <p class="panel-label">Editing</p>
                    <h2>Tools</h2>
                </div>

                <div class="tools" id="toolButtons">
                    <?php foreach ($features as $index => $feature): ?>
                        <button class="tool-button<?php echo $index === 0 ? ' is-active' : ''; ?>" type="button" data-tool="<?php echo htmlspecialchars($feature['id'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="tool-icon" aria-hidden="true"><?php echo htmlspecialchars($feature['icon'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span>
                                <strong><?php echo htmlspecialchars($feature['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?php echo htmlspecialchars($feature['description'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </span>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="controls-panel">
                    <div class="control-group is-active" data-controls="trim">
                        <h2>Cut video</h2>
                        <p class="hint">Drag the S/E handles on the timeline, or type exact times below.</p>
                        <div class="row">
                            <label class="field">Start (sec)<input id="trimStart" type="number" min="0" value="0" step="0.1"></label>
                            <label class="field">End (sec)<input id="trimEnd" type="number" min="0" value="0" step="0.1"></label>
                        </div>
                        <button class="action" type="button" id="previewTrim">Preview trim loop</button>
                        <div class="note" id="trimNote">Playback will loop between your start and end times.</div>
                    </div>

                    <div class="control-group" data-controls="merge">
                        <h2>Merge clips</h2>
                        <p class="hint">Queue 2+ clips in order, then export one file.</p>
                        <label class="field">Add clips<input id="mergeInput" type="file" accept="video/*" multiple></label>
                        <ul class="merge-list" id="mergeList" data-empty="No clips yet — add at least two videos"></ul>
                        <button class="action secondary" type="button" id="clearMergeQueue">Clear queue</button>
                        <button class="action" type="button" id="exportMerge">Export merged video</button>
                        <div class="note" id="mergeNote">Add at least two clips, then export.</div>
                        <div class="note" id="mergeResult" style="display: none;"></div>
                    </div>

                    <div class="control-group" data-controls="text">
                        <h2>Add text</h2>
                        <p class="hint">Text shows only between start and end. Drag it on the preview to reposition.</p>
                        <label class="field">Text<input id="textInput" type="text" placeholder="Your title or caption"></label>
                        <div class="row">
                            <label class="field">Start (sec)<input id="textStart" type="number" min="0" value="0" step="0.1"></label>
                            <label class="field">End (sec)<input id="textEnd" type="number" min="0" value="0" step="0.1"></label>
                        </div>
                        <div class="timeline-actions">
                            <button class="action secondary" type="button" id="textStartHere">Starts at playhead</button>
                            <button class="action secondary" type="button" id="textEndHere">Ends at playhead</button>
                        </div>
                        <div class="timeline-actions">
                            <button class="action" type="button" id="addTextClip">Add to timeline</button>
                            <button class="action secondary" type="button" id="undoTextClip" disabled>Undo last text</button>
                        </div>
                        <div class="text-track" id="textTrack" aria-label="Text timeline"></div>
                        <ul class="text-list" id="textList" data-empty="No text overlays yet"></ul>
                        <div class="note" id="textNote">Tip: scrub to the moment you want, then use “Starts at playhead”.</div>
                    </div>

                    <div class="control-group" data-controls="music">
                        <h2>Add music</h2>
                        <p class="hint">Choose an audio file in Library. It will be included when you export (mixed, or alone if muted).</p>
                        <button class="action" type="button" id="playMusic">Play with music</button>
                        <button class="action secondary" type="button" id="stopMusic">Stop music</button>
                        <audio id="musicPreview"></audio>
                        <div class="note" id="musicNote">No music file loaded yet.</div>
                    </div>

                    <div class="control-group" data-controls="mute">
                        <h2>Mute</h2>
                        <p class="hint">Turn off the video’s original soundtrack for preview and export.</p>
                        <button class="action" type="button" id="toggleMute">Mute video</button>
                        <div class="note" id="muteNote">Preview audio is currently on.</div>
                    </div>

                    <div class="control-group" data-controls="filter">
                        <h2>Filters</h2>
                        <p class="hint">Tap a look to preview. The selected filter is included when you export.</p>
                        <label class="sr-only" for="filterSelect">Filter</label>
                        <select id="filterSelect" class="sr-only" tabindex="-1" aria-hidden="true">
                            <option value="none">Normal</option>
                            <option value="grayscale(1)">Black and white</option>
                            <option value="sepia(.85)">Warm sepia</option>
                            <option value="contrast(1.25) saturate(1.35)">Vivid</option>
                            <option value="brightness(1.12) contrast(.92)">Soft bright</option>
                        </select>
                        <div class="filter-grid" id="filterGrid" role="listbox" aria-label="Filter presets">
                            <button class="filter-chip is-active" type="button" role="option" aria-selected="true" data-filter="none">
                                <span class="filter-swatch" aria-hidden="true"></span>
                                <strong>Normal</strong>
                            </button>
                            <button class="filter-chip" type="button" role="option" aria-selected="false" data-filter="grayscale(1)">
                                <span class="filter-swatch bw" aria-hidden="true"></span>
                                <strong>B&amp;W</strong>
                            </button>
                            <button class="filter-chip" type="button" role="option" aria-selected="false" data-filter="sepia(.85)">
                                <span class="filter-swatch sepia" aria-hidden="true"></span>
                                <strong>Sepia</strong>
                            </button>
                            <button class="filter-chip" type="button" role="option" aria-selected="false" data-filter="contrast(1.25) saturate(1.35)">
                                <span class="filter-swatch vivid" aria-hidden="true"></span>
                                <strong>Vivid</strong>
                            </button>
                            <button class="filter-chip" type="button" role="option" aria-selected="false" data-filter="brightness(1.12) contrast(.92)">
                                <span class="filter-swatch soft" aria-hidden="true"></span>
                                <strong>Soft</strong>
                            </button>
                        </div>
                    </div>
                </div>
            </aside>
        </section>
    </main>
<script>
        const videoInput = document.getElementById('videoInput');
        const audioInput = document.getElementById('audioInput');
        const mergeInput = document.getElementById('mergeInput');
        const video = document.getElementById('videoPreview');
        const music = document.getElementById('musicPreview');
        const emptyPreview = document.getElementById('emptyPreview');
        const overlayText = document.getElementById('overlayText');
        const fileName = document.getElementById('fileName');
        const durationText = document.getElementById('durationText');
        const modeText = document.getElementById('modeText');
        const statusText = document.getElementById('statusText');
        const trimStart = document.getElementById('trimStart');
        const trimEnd = document.getElementById('trimEnd');
        const trimNote = document.getElementById('trimNote');
        const muteNote = document.getElementById('muteNote');
        const toggleMute = document.getElementById('toggleMute');
        const mergeList = document.getElementById('mergeList');
        const mergeNote = document.getElementById('mergeNote');
        const mergeResult = document.getElementById('mergeResult');
        const exportMerge = document.getElementById('exportMerge');
        const exportEdited = document.getElementById('exportEdited');
        const exportEditedResult = document.getElementById('exportEditedResult');
        const scrubber = document.getElementById('scrubber');
        const playhead = document.getElementById('playhead');
        const startHandle = document.getElementById('startHandle');
        const endHandle = document.getElementById('endHandle');
        const trimRange = document.getElementById('trimRange');
        const currentTimeText = document.getElementById('currentTimeText');
        const trimRangeText = document.getElementById('trimRangeText');
        const textInput = document.getElementById('textInput');
        const textStart = document.getElementById('textStart');
        const textEnd = document.getElementById('textEnd');
        const textTrack = document.getElementById('textTrack');
        const textList = document.getElementById('textList');
        const textNote = document.getElementById('textNote');
        const videoDropzone = document.getElementById('videoDropzone');
        const filterSelect = document.getElementById('filterSelect');
        const filterGrid = document.getElementById('filterGrid');
        const stepUpload = document.getElementById('stepUpload');
        const stepEdit = document.getElementById('stepEdit');
        const stepExport = document.getElementById('stepExport');
        const jobProgress = document.getElementById('jobProgress');
        const clearProject = document.getElementById('clearProject');
        const undoTextClip = document.getElementById('undoTextClip');
        const musicNote = document.getElementById('musicNote');
        const saveKey = 'vidihEditorState';
        let trimLoop = false;
        let activeDrag = null;
        let currentTool = 'trim';
        let textClips = [];
        let mergeQueueItems = [];
        let serverVideoPath = '';
        let serverAudioPath = '';
        let restoredState = null;
        let activeTextClipId = null;
        let overlayDragPointer = null;
        let hasExported = false;

        function formatTime(seconds) {
            if (!Number.isFinite(seconds)) return '0:00';
            const whole = Math.max(0, Math.floor(seconds));
            const minutes = Math.floor(whole / 60);
            const rest = String(whole % 60).padStart(2, '0');
            return `${minutes}:${rest}`;
        }

        function clamp(value, min, max) {
            return Math.min(Math.max(value, min), max);
        }

        function setStatus(message, tone = '') {
            statusText.textContent = message;
            statusText.classList.remove('is-busy', 'is-ok', 'is-error');
            if (tone) statusText.classList.add(tone);
        }

        function setProgress(on, value = 0) {
            jobProgress.classList.toggle('is-on', on);
            if (!on) {
                jobProgress.removeAttribute('value');
                jobProgress.value = 0;
                return;
            }
            if (value === null) {
                jobProgress.removeAttribute('value');
                return;
            }
            jobProgress.value = clamp(value, 0, 100);
        }

        function resultLinks(path, name) {
            const safeName = name || path.split('/').pop();
            return `<div class="result-actions">
                <a class="action" href="${path}" download="${safeName}">Download</a>
                <a class="action secondary" href="${path}" target="_blank" rel="noopener">Open</a>
            </div>`;
        }

        function postForm(formData, { onProgress } = {}) {
            return new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'index.php');
                xhr.responseType = 'text';
                if (xhr.upload && onProgress) {
                    xhr.upload.onprogress = (event) => {
                        if (!event.lengthComputable) return;
                        onProgress(Math.round((event.loaded / event.total) * 100));
                    };
                }
                xhr.onload = () => {
                    let result;
                    try {
                        result = JSON.parse(xhr.responseText);
                    } catch (error) {
                        reject(new Error(`Server did not return JSON. ${String(xhr.responseText).slice(0, 240)}`));
                        return;
                    }
                    if (xhr.status < 200 || xhr.status >= 300 || !result.ok) {
                        reject(new Error(result.details ? `${result.message} ${result.details}` : (result.message || 'Request failed.')));
                        return;
                    }
                    resolve(result);
                };
                xhr.onerror = () => reject(new Error('Network error while talking to the server.'));
                xhr.send(formData);
            });
        }

        function updateSteps() {
            const hasVideo = Boolean(serverVideoPath || video.src);
            stepUpload.classList.toggle('is-done', hasVideo);
            stepEdit.classList.toggle('is-done', hasVideo && (textClips.length > 0 || currentTool !== 'trim' || Number(trimStart.value) > 0 || serverAudioPath));
            stepExport.classList.toggle('is-done', hasExported);
            exportEdited.disabled = !serverVideoPath;
            if (undoTextClip) undoTextClip.disabled = textClips.length === 0;
        }

        function syncFilterChips(value) {
            filterSelect.value = value;
            video.style.filter = value === 'none' ? 'none' : value;
            filterGrid.querySelectorAll('.filter-chip').forEach((chip) => {
                const active = chip.dataset.filter === value;
                chip.classList.toggle('is-active', active);
                chip.setAttribute('aria-selected', active ? 'true' : 'false');
            });
        }

        function percentFromTime(time) {
            if (!video.duration) return 0;
            return clamp((time / video.duration) * 100, 0, 100);
        }

        function timeFromPointer(event) {
            const rect = scrubber.getBoundingClientRect();
            const x = clamp(event.clientX - rect.left, 0, rect.width);
            return video.duration ? (x / rect.width) * video.duration : 0;
        }

        function updateTimeline() {
            const duration = Number.isFinite(video.duration) ? video.duration : 0;
            const trimStartValue = clamp(Number(trimStart.value) || 0, 0, duration);
            const trimEndFallback = duration || 0;
            const trimEndValue = clamp(Number(trimEnd.value) || trimEndFallback, 0, duration);
            const textStartValue = clamp(Number(textStart.value) || 0, 0, duration);
            const textEndFallback = Math.min(3, duration) || 0;
            const textEndValue = clamp(Number(textEnd.value) || textEndFallback, 0, duration);
            const activeStart = currentTool === 'text' ? textStartValue : trimStartValue;
            const activeEnd = currentTool === 'text' ? textEndValue : trimEndValue;
            const rangeStart = Math.min(activeStart, activeEnd);
            const rangeEnd = Math.max(activeStart, activeEnd);
            const current = clamp(video.currentTime || 0, 0, duration);
            const rangeLabel = currentTool === 'text' ? 'Text' : 'Trim';

            playhead.style.left = `${percentFromTime(current)}%`;
            startHandle.style.left = `${percentFromTime(rangeStart)}%`;
            endHandle.style.left = `${percentFromTime(rangeEnd)}%`;
            trimRange.style.left = `${percentFromTime(rangeStart)}%`;
            trimRange.style.width = `${Math.max(0, percentFromTime(rangeEnd) - percentFromTime(rangeStart))}%`;
            currentTimeText.textContent = `Current: ${formatTime(current)}`;
            trimRangeText.textContent = `${rangeLabel}: ${formatTime(rangeStart)} - ${formatTime(rangeEnd)}`;
            scrubber.setAttribute('aria-valuemax', duration.toFixed(1));
            scrubber.setAttribute('aria-valuenow', current.toFixed(1));
            updateTimedText();
        }

        function renderTextClips() {
            textList.innerHTML = '';
            textTrack.innerHTML = '';

            textClips.forEach((clip) => {
                const item = document.createElement('li');
                item.innerHTML = `<strong></strong><span>${formatTime(clip.start)} - ${formatTime(clip.end)} · ${Math.round(clip.x || 50)}%, ${Math.round(clip.y || 82)}%</span>`;
                item.querySelector('strong').textContent = clip.text;

                const actions = document.createElement('div');
                actions.className = 'clip-actions';

                const jump = document.createElement('button');
                jump.className = 'mini-action';
                jump.type = 'button';
                jump.textContent = 'Jump';
                jump.addEventListener('click', () => seekTo(clip.start));

                const remove = document.createElement('button');
                remove.className = 'mini-action';
                remove.type = 'button';
                remove.textContent = 'Delete';
                remove.addEventListener('click', () => {
                    textClips = textClips.filter((itemClip) => itemClip.id !== clip.id);
                    renderTextClips();
                    updateTimedText();
                    saveState();
                    updateSteps();
                });

                actions.append(jump, remove);
                item.appendChild(actions);
                textList.appendChild(item);

                const marker = document.createElement('div');
                marker.className = 'text-clip-marker';
                marker.title = `${clip.text}: ${formatTime(clip.start)} - ${formatTime(clip.end)}`;
                marker.style.left = `${percentFromTime(clip.start)}%`;
                marker.style.width = `${Math.max(1, percentFromTime(clip.end) - percentFromTime(clip.start))}%`;
                textTrack.appendChild(marker);
            });
            updateSteps();
        }

        function positionOverlay(clip) {
            const x = Number.isFinite(clip.x) ? clip.x : 50;
            const y = Number.isFinite(clip.y) ? clip.y : 82;
            overlayText.style.left = `${clamp(x, 4, 96)}%`;
            overlayText.style.top = `${clamp(y, 6, 94)}%`;
        }

        function updateTimedText() {
            const current = video.currentTime || 0;
            const activeClip = textClips.find((clip) => current >= clip.start && current <= clip.end);
            activeTextClipId = activeClip ? activeClip.id : null;
            overlayText.textContent = activeClip ? activeClip.text : '';
            overlayText.style.display = activeClip ? 'block' : 'none';
            if (activeClip) positionOverlay(activeClip);
        }

        function moveActiveTextOverlay(event) {
            if (!activeTextClipId) return;
            const rect = document.querySelector('.video-wrap').getBoundingClientRect();
            const x = clamp(((event.clientX - rect.left) / rect.width) * 100, 4, 96);
            const y = clamp(((event.clientY - rect.top) / rect.height) * 100, 6, 94);
            const clip = textClips.find((item) => item.id === activeTextClipId);
            if (!clip) return;
            clip.x = x;
            clip.y = y;
            positionOverlay(clip);
            renderTextClips();
        }

        function seekTo(time) {
            if (!video.src || !video.duration) return;
            trimLoop = false;
            video.currentTime = clamp(time, 0, video.duration);
            updateTimeline();
        }

        function setTrimStart(time) {
            if (!video.duration) return;
            const end = Number(trimEnd.value) || video.duration;
            trimStart.value = clamp(Math.min(time, end), 0, video.duration).toFixed(1);
            updateTimeline();
            updateSteps();
        }

        function setTrimEnd(time) {
            if (!video.duration) return;
            const start = Number(trimStart.value) || 0;
            trimEnd.value = clamp(Math.max(time, start), 0, video.duration).toFixed(1);
            updateTimeline();
        }

        function setTextStart(time) {
            if (!video.duration) return;
            const end = Number(textEnd.value) || Math.min(3, video.duration);
            textStart.value = clamp(Math.min(time, end), 0, video.duration).toFixed(1);
            updateTimeline();
        }

        function setTextEnd(time) {
            if (!video.duration) return;
            const start = Number(textStart.value) || 0;
            textEnd.value = clamp(Math.max(time, start), 0, video.duration).toFixed(1);
            updateTimeline();
        }

        function setActiveStart(time) {
            if (currentTool === 'text') {
                setTextStart(time);
                textNote.textContent = `Text start set at ${formatTime(Number(textStart.value))}.`;
                return;
            }
            setTrimStart(time);
        }

        function setActiveEnd(time) {
            if (currentTool === 'text') {
                setTextEnd(time);
                textNote.textContent = `Text end set at ${formatTime(Number(textEnd.value))}.`;
                return;
            }
            setTrimEnd(time);
        }

        function labelForTool(tool) {
            const button = document.querySelector(`.tool-button[data-tool="${tool}"] strong`);
            return button ? button.textContent : 'Cut';
        }

        function saveState() {
            const state = {
                currentTool,
                trimStart: trimStart.value,
                trimEnd: trimEnd.value,
                textStart: textStart.value,
                textEnd: textEnd.value,
                textDraft: textInput.value,
                textClips,
                mergeQueueItems,
                muted: video.muted,
                filter: filterSelect.value,
                videoFileName: videoInput.files[0] ? videoInput.files[0].name : fileName.textContent,
                videoPath: serverVideoPath,
                audioFileName: audioInput.files[0] ? audioInput.files[0].name : '',
                audioPath: serverAudioPath,
            };
            localStorage.setItem(saveKey, JSON.stringify(state));
        }

        function moveMergeClip(index, direction) {
            const next = index + direction;
            if (next < 0 || next >= mergeQueueItems.length) return;
            const temp = mergeQueueItems[index];
            mergeQueueItems[index] = mergeQueueItems[next];
            mergeQueueItems[next] = temp;
            renderMergeQueue();
            saveState();
        }

        function renderMergeQueue() {
            mergeList.innerHTML = '';
            mergeQueueItems.forEach((clip, index) => {
                const item = document.createElement('li');
                const label = document.createElement('span');
                label.textContent = `${index + 1}. ${clip.name}`;

                const actions = document.createElement('div');
                actions.className = 'clip-actions';
                actions.style.marginTop = '0';

                const up = document.createElement('button');
                up.className = 'mini-action';
                up.type = 'button';
                up.textContent = 'Up';
                up.disabled = index === 0;
                up.addEventListener('click', () => moveMergeClip(index, -1));

                const down = document.createElement('button');
                down.className = 'mini-action';
                down.type = 'button';
                down.textContent = 'Down';
                down.disabled = index === mergeQueueItems.length - 1;
                down.addEventListener('click', () => moveMergeClip(index, 1));

                const remove = document.createElement('button');
                remove.className = 'mini-action';
                remove.type = 'button';
                remove.textContent = 'Remove';
                remove.addEventListener('click', () => {
                    mergeQueueItems.splice(index, 1);
                    renderMergeQueue();
                    mergeNote.textContent = mergeQueueItems.length < 2
                        ? 'Add at least two clips before exporting.'
                        : 'Ready to export merged video.';
                    setStatus(`${mergeQueueItems.length} clip(s) queued`);
                    saveState();
                });

                actions.append(up, down, remove);
                item.append(label, actions);
                mergeList.appendChild(item);
            });
        }

        function restoreState() {
            try {
                restoredState = JSON.parse(localStorage.getItem(saveKey) || 'null');
            } catch (error) {
                restoredState = null;
            }

            if (!restoredState) {
                updateSteps();
                return;
            }

            trimStart.value = restoredState.trimStart || 0;
            trimEnd.value = restoredState.trimEnd || 0;
            textStart.value = restoredState.textStart || 0;
            textEnd.value = restoredState.textEnd || 0;
            textInput.value = restoredState.textDraft || '';
            textClips = Array.isArray(restoredState.textClips) ? restoredState.textClips : [];
            mergeQueueItems = Array.isArray(restoredState.mergeQueueItems) ? restoredState.mergeQueueItems : [];
            serverVideoPath = restoredState.videoPath || '';
            serverAudioPath = restoredState.audioPath || '';
            video.muted = Boolean(restoredState.muted);
            toggleMute.textContent = video.muted ? 'Turn sound back on' : 'Mute video';
            muteNote.textContent = video.muted ? 'The preview video is muted.' : 'Preview audio is currently on.';
            syncFilterChips(restoredState.filter || 'none');
            renderTextClips();
            renderMergeQueue();
            if (serverAudioPath) {
                music.src = serverAudioPath;
                music.load();
                musicNote.textContent = `Music ready: ${restoredState.audioFileName || serverAudioPath.split('/').pop()}. Included on export.`;
            }
            if (serverVideoPath) {
                video.src = serverVideoPath;
                video.load();
                emptyPreview.style.display = 'none';
                fileName.textContent = restoredState.videoFileName || serverVideoPath.split('/').pop();
                setStatus('Project restored', 'is-ok');
            } else if (restoredState.videoFileName && restoredState.videoFileName !== 'No file selected') {
                fileName.textContent = restoredState.videoFileName;
                setStatus('Edits restored — reselect the video to upload it again');
            } else {
                setStatus('Previous edits restored');
            }
            setTool(restoredState.currentTool || 'trim', labelForTool(restoredState.currentTool || 'trim'), false);
            updateSteps();
        }

        function setTool(tool, label, shouldSave = true) {
            currentTool = tool;
            document.querySelectorAll('.tool-button').forEach((button) => {
                button.classList.toggle('is-active', button.dataset.tool === tool);
            });
            document.querySelectorAll('.control-group').forEach((group) => {
                group.classList.toggle('is-active', group.dataset.controls === tool);
            });
            modeText.textContent = `Tool: ${label}`;
            setStatus(`${label} selected`);
            updateTimeline();
            updateSteps();
            if (shouldSave) saveState();
        }

        async function handleVideoFile(file) {
            if (!file) return;
            setStatus('Uploading video…', 'is-busy');
            setProgress(true, 0);
            trimLoop = false;

            try {
                const formData = new FormData();
                formData.append('action', 'upload_video');
                formData.append('video', file);
                const uploaded = await postForm(formData, {
                    onProgress: (pct) => setProgress(true, pct),
                });
                serverVideoPath = uploaded.path;
                video.src = uploaded.path;
                video.load();
                emptyPreview.style.display = 'none';
                fileName.textContent = uploaded.name;
                setStatus('Video ready — pick a tool to edit', 'is-ok');
                saveState();
                updateSteps();
            } catch (error) {
                setStatus(error.message, 'is-error');
            } finally {
                setProgress(false);
            }
        }

        document.getElementById('toolButtons').addEventListener('click', (event) => {
            const button = event.target.closest('.tool-button');
            if (!button) return;
            setTool(button.dataset.tool, button.querySelector('strong').textContent);
        });

        filterGrid.addEventListener('click', (event) => {
            const chip = event.target.closest('.filter-chip');
            if (!chip) return;
            syncFilterChips(chip.dataset.filter);
            setStatus(`${chip.querySelector('strong').textContent} filter applied`, 'is-ok');
            saveState();
            updateSteps();
        });

        ['dragenter', 'dragover'].forEach((eventName) => {
            videoDropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                videoDropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach((eventName) => {
            videoDropzone.addEventListener(eventName, (event) => {
                event.preventDefault();
                videoDropzone.classList.remove('is-dragover');
            });
        });

        videoDropzone.addEventListener('drop', (event) => {
            const file = event.dataTransfer.files && event.dataTransfer.files[0];
            if (!file || !file.type.startsWith('video/')) {
                setStatus('Please drop a video file', 'is-error');
                return;
            }
            const transfer = new DataTransfer();
            transfer.items.add(file);
            videoInput.files = transfer.files;
            handleVideoFile(file);
        });

        videoDropzone.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                videoInput.click();
            }
        });

        async function exportEditedVideo() {
            const formData = new FormData();
            formData.append('action', 'export_edited');
            formData.append('video_path', serverVideoPath);
            formData.append('trim_start', trimStart.value || '0');
            formData.append('trim_end', trimEnd.value || '0');
            formData.append('text_clips', JSON.stringify(textClips));
            formData.append('muted', video.muted ? '1' : '0');
            formData.append('filter', filterSelect.value);
            formData.append('audio_path', serverAudioPath || '');
            return postForm(formData);
        }

        async function uploadMergeClip(file) {
            const formData = new FormData();
            formData.append('action', 'upload_merge_clip');
            formData.append('video', file);
            return postForm(formData, {
                onProgress: (pct) => setProgress(true, pct),
            });
        }

        async function mergeVideos(clips) {
            const formData = new FormData();
            formData.append('action', 'merge_videos');
            formData.append('clip_paths', JSON.stringify(clips.map((clip) => clip.path)));
            return postForm(formData);
        }

        async function uploadAudio(file) {
            const formData = new FormData();
            formData.append('action', 'upload_audio');
            formData.append('audio', file);
            return postForm(formData, {
                onProgress: (pct) => setProgress(true, pct),
            });
        }

        exportEdited.addEventListener('click', async () => {
            if (!serverVideoPath) {
                exportEditedResult.style.display = 'block';
                exportEditedResult.classList.add('is-error');
                exportEditedResult.textContent = 'Upload a main video before exporting.';
                return;
            }

            exportEdited.disabled = true;
            exportEdited.textContent = 'Exporting…';
            exportEditedResult.style.display = 'block';
            exportEditedResult.classList.remove('is-error');
            exportEditedResult.textContent = 'Rendering your edit. Keep this page open.';
            setStatus('Exporting edited video…', 'is-busy');
            setProgress(true, null);
            saveState();

            try {
                const result = await exportEditedVideo();
                hasExported = true;
                exportEditedResult.innerHTML = resultLinks(result.path, result.name);
                setStatus('Edited video ready', 'is-ok');
                updateSteps();
            } catch (error) {
                exportEditedResult.classList.add('is-error');
                exportEditedResult.textContent = error.message;
                setStatus('Export failed', 'is-error');
            } finally {
                exportEdited.disabled = !serverVideoPath;
                exportEdited.textContent = 'Export edited video';
                setProgress(false);
            }
        });

        videoInput.addEventListener('change', async () => {
            const file = videoInput.files[0];
            if (!file) return;
            await handleVideoFile(file);
        });

        video.addEventListener('loadedmetadata', () => {
            durationText.textContent = `Duration: ${formatTime(video.duration)}`;
            if (!restoredState) {
                trimStart.value = 0;
                trimEnd.value = video.duration ? video.duration.toFixed(1) : 0;
                textStart.value = 0;
                textEnd.value = video.duration ? Math.min(3, video.duration).toFixed(1) : 0;
            }
            updateTimeline();
            renderTextClips();
            saveState();
            updateSteps();
        });

        document.getElementById('previewTrim').addEventListener('click', () => {
            const start = Number(trimStart.value);
            const end = Number(trimEnd.value);
            if (!video.src || !Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
                trimNote.textContent = 'Load a video and choose an end time greater than the start time.';
                return;
            }
            trimLoop = true;
            video.currentTime = start;
            video.play();
            trimNote.textContent = `Looping ${formatTime(start)} to ${formatTime(end)}.`;
        });

        video.addEventListener('timeupdate', () => {
            updateTimeline();
            if (!trimLoop) return;
            const start = Number(trimStart.value);
            const end = Number(trimEnd.value);
            if (Number.isFinite(end) && video.currentTime >= end) {
                video.currentTime = Number.isFinite(start) ? start : 0;
                video.play();
            }
        });

        video.addEventListener('seeking', updateTimeline);
        trimStart.addEventListener('input', () => {
            updateTimeline();
            updateSteps();
        });
        trimEnd.addEventListener('input', updateTimeline);

        scrubber.addEventListener('pointerdown', (event) => {
            if (!video.src || !video.duration) return;
            const handle = event.target.closest('.trim-handle, .playhead');
            activeDrag = handle === startHandle ? 'start' : handle === endHandle ? 'end' : 'seek';
            scrubber.setPointerCapture(event.pointerId);
            const time = timeFromPointer(event);
            if (activeDrag === 'start') setActiveStart(time);
            if (activeDrag === 'end') setActiveEnd(time);
            if (activeDrag === 'seek') seekTo(time);
            saveState();
        });

        scrubber.addEventListener('pointermove', (event) => {
            if (!activeDrag) return;
            const time = timeFromPointer(event);
            if (activeDrag === 'start') setActiveStart(time);
            if (activeDrag === 'end') setActiveEnd(time);
            if (activeDrag === 'seek') seekTo(time);
            saveState();
        });

        scrubber.addEventListener('pointerup', (event) => {
            activeDrag = null;
            if (scrubber.hasPointerCapture(event.pointerId)) {
                scrubber.releasePointerCapture(event.pointerId);
            }
        });

        scrubber.addEventListener('keydown', (event) => {
            if (!video.duration) return;
            const step = event.shiftKey ? 1 : 0.1;
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                seekTo(video.currentTime - step);
            }
            if (event.key === 'ArrowRight') {
                event.preventDefault();
                seekTo(video.currentTime + step);
            }
        });

        document.getElementById('markStart').addEventListener('click', () => {
            setTrimStart(video.currentTime || 0);
            trimNote.textContent = `Start set at ${formatTime(Number(trimStart.value))}.`;
            saveState();
        });

        document.getElementById('markEnd').addEventListener('click', () => {
            setTrimEnd(video.currentTime || 0);
            trimNote.textContent = `End set at ${formatTime(Number(trimEnd.value))}.`;
            saveState();
        });

        mergeInput.addEventListener('change', async () => {
            const files = Array.from(mergeInput.files);
            if (!files.length) return;
            mergeInput.value = '';
            mergeResult.style.display = 'none';
            mergeNote.textContent = 'Uploading merge clip(s)…';
            setStatus('Uploading merge clip(s)…', 'is-busy');
            setProgress(true, 0);

            try {
                for (const file of files) {
                    const uploaded = await uploadMergeClip(file);
                    mergeQueueItems.push({ name: uploaded.name, path: uploaded.path });
                    renderMergeQueue();
                    saveState();
                }
                setStatus(`${mergeQueueItems.length} clip(s) queued`, 'is-ok');
                mergeNote.textContent = mergeQueueItems.length < 2
                    ? 'Add one more clip before exporting.'
                    : 'Ready to export merged video.';
            } catch (error) {
                mergeNote.textContent = error.message;
                setStatus('Merge clip upload failed', 'is-error');
            } finally {
                setProgress(false);
            }
        });

        document.getElementById('clearMergeQueue').addEventListener('click', () => {
            mergeQueueItems = [];
            renderMergeQueue();
            mergeResult.style.display = 'none';
            mergeNote.textContent = 'Queue cleared. Add at least two clips to export.';
            setStatus('Merge queue cleared');
            saveState();
        });

        exportMerge.addEventListener('click', async () => {
            mergeNote.textContent = `Export clicked. ${mergeQueueItems.length} clip(s) in queue.`;
            mergeResult.style.display = 'none';
            mergeResult.classList.remove('is-error');

            if (mergeQueueItems.length < 2) {
                mergeNote.textContent = 'Choose at least two clips before exporting. You can add them one at a time.';
                return;
            }

            exportMerge.disabled = true;
            exportMerge.textContent = 'Merging…';
            mergeNote.textContent = 'Merging clips. Keep this page open.';
            setStatus('Merging videos…', 'is-busy');
            setProgress(true, null);

            try {
                const result = await mergeVideos(mergeQueueItems);
                mergeNote.textContent = 'Merge complete.';
                mergeResult.style.display = 'block';
                mergeResult.classList.remove('is-error');
                mergeResult.innerHTML = resultLinks(result.path, result.name);
                setStatus('Merged video ready', 'is-ok');
            } catch (error) {
                mergeNote.textContent = error.message;
                mergeResult.style.display = 'block';
                mergeResult.classList.add('is-error');
                mergeResult.textContent = 'Merge failed. Check the message above.';
                setStatus('Merge failed', 'is-error');
            } finally {
                exportMerge.disabled = false;
                exportMerge.textContent = 'Export merged video';
                setProgress(false);
            }
        });

        document.getElementById('textStartHere').addEventListener('click', () => {
            setTextStart(video.currentTime || 0);
            saveState();
        });

        document.getElementById('textEndHere').addEventListener('click', () => {
            setTextEnd(video.currentTime || 0);
            saveState();
        });

        textInput.addEventListener('input', saveState);
        textStart.addEventListener('input', () => {
            updateTimeline();
            saveState();
        });
        textEnd.addEventListener('input', () => {
            updateTimeline();
            saveState();
        });

        document.getElementById('addTextClip').addEventListener('click', () => {
            const text = textInput.value.trim();
            const start = Number(textStart.value);
            const end = Number(textEnd.value);

            if (!text) {
                textNote.textContent = 'Type some text first.';
                return;
            }

            if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
                textNote.textContent = 'Choose an end time greater than the start time.';
                return;
            }

            textClips.push({
                id: crypto.randomUUID ? crypto.randomUUID() : String(Date.now() + Math.random()),
                text,
                start,
                end,
                x: 50,
                y: 82,
            });
            textInput.value = '';
            textNote.textContent = `Text added from ${formatTime(start)} to ${formatTime(end)}. Drag it on the preview to move.`;
            renderTextClips();
            updateTimedText();
            saveState();
        });

        overlayText.addEventListener('pointerdown', (event) => {
            if (!activeTextClipId) return;
            overlayDragPointer = event.pointerId;
            overlayText.setPointerCapture(event.pointerId);
            moveActiveTextOverlay(event);
        });

        overlayText.addEventListener('pointermove', (event) => {
            if (overlayDragPointer !== event.pointerId) return;
            moveActiveTextOverlay(event);
        });

        overlayText.addEventListener('pointerup', (event) => {
            if (overlayDragPointer !== event.pointerId) return;
            overlayDragPointer = null;
            if (overlayText.hasPointerCapture(event.pointerId)) {
                overlayText.releasePointerCapture(event.pointerId);
            }
            saveState();
        });

        audioInput.addEventListener('change', async () => {
            const file = audioInput.files[0];
            if (!file) return;
            setStatus('Uploading music…', 'is-busy');
            setProgress(true, 0);
            try {
                const uploaded = await uploadAudio(file);
                serverAudioPath = uploaded.path;
                music.src = uploaded.path;
                music.load();
                musicNote.textContent = `Music ready: ${uploaded.name}. Included on export${video.muted ? ' (video muted)' : ' (mixed with original audio)'}.`;
                setStatus('Music uploaded — preview in Music tool', 'is-ok');
                saveState();
                updateSteps();
            } catch (error) {
                serverAudioPath = '';
                music.src = URL.createObjectURL(file);
                music.load();
                musicNote.textContent = `Preview only (upload failed): ${error.message}`;
                setStatus(error.message, 'is-error');
            } finally {
                setProgress(false);
            }
        });

        document.getElementById('playMusic').addEventListener('click', () => {
            if (!music.src) {
                setStatus('Select an audio file in Library first', 'is-error');
                return;
            }
            music.currentTime = 0;
            music.play();
            if (video.src) video.play();
            setStatus('Music preview playing', 'is-ok');
        });

        document.getElementById('stopMusic').addEventListener('click', () => {
            music.pause();
            music.currentTime = 0;
            setStatus('Music stopped');
        });

        toggleMute.addEventListener('click', () => {
            video.muted = !video.muted;
            toggleMute.textContent = video.muted ? 'Turn sound back on' : 'Mute video';
            muteNote.textContent = video.muted ? 'The preview video is muted.' : 'Preview audio is currently on.';
            if (serverAudioPath) {
                musicNote.textContent = video.muted
                    ? 'Music will replace video audio on export.'
                    : 'Music will be mixed with original audio on export.';
            }
            setStatus(video.muted ? 'Video muted' : 'Sound restored', 'is-ok');
            saveState();
        });

        undoTextClip.addEventListener('click', () => {
            if (!textClips.length) return;
            textClips.pop();
            renderTextClips();
            updateTimedText();
            textNote.textContent = 'Removed the last text overlay.';
            saveState();
            updateSteps();
        });

        clearProject.addEventListener('click', () => {
            if (!window.confirm('Clear this project? Saved edits and local state will be removed.')) return;
            localStorage.removeItem(saveKey);
            serverVideoPath = '';
            serverAudioPath = '';
            textClips = [];
            mergeQueueItems = [];
            hasExported = false;
            restoredState = null;
            video.removeAttribute('src');
            video.load();
            music.removeAttribute('src');
            music.load();
            emptyPreview.style.display = '';
            fileName.textContent = 'No file selected';
            durationText.textContent = 'Duration: 0:00';
            trimStart.value = 0;
            trimEnd.value = 0;
            textStart.value = 0;
            textEnd.value = 0;
            textInput.value = '';
            videoInput.value = '';
            audioInput.value = '';
            video.muted = false;
            syncFilterChips('none');
            renderTextClips();
            renderMergeQueue();
            exportEditedResult.style.display = 'none';
            mergeResult.style.display = 'none';
            musicNote.textContent = 'No music file loaded yet.';
            muteNote.textContent = 'Preview audio is currently on.';
            toggleMute.textContent = 'Mute video';
            setTool('trim', 'Cut', false);
            setStatus('Project cleared — drop a video to begin');
            updateSteps();
        });

        restoreState();
    </script>
</body>
</html>
