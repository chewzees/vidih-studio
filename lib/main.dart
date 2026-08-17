import 'package:flutter/material.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:file_picker/file_picker.dart';
import 'package:ffmpeg_kit_flutter_full_gpl_lts/ffmpeg_kit.dart';
import 'package:ffmpeg_kit_flutter_full_gpl_lts/return_code.dart';
import 'package:path_provider/path_provider.dart';
import 'package:gal/gal.dart';
import 'dart:convert';
import 'dart:io';

void main() {
  runApp(const VidihApp());
}

class VidihApp extends StatelessWidget {
  const VidihApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Vidih Studio',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF5B4DD8)),
        useMaterial3: true,
      ),
      home: const VidihWebView(),
    );
  }
}

class VidihWebView extends StatefulWidget {
  const VidihWebView({super.key});

  @override
  State<VidihWebView> createState() => _VidihWebViewState();
}

class _VidihWebViewState extends State<VidihWebView> {
  late final WebViewController _controller;

  @override
  void initState() {
    super.initState();
    _controller = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..addJavaScriptChannel(
        'FlutterBridge',
        onMessageReceived: (JavaScriptMessage message) {
          _handleJsMessage(message.message);
        },
      )
      ..loadFlutterAsset('assets/index.html');
  }

  void _handleJsMessage(String jsonStr) async {
    try {
      final Map<String, dynamic> data = jsonDecode(jsonStr);
      final String method = data['method'];
      final dynamic params = data['data'];

      switch (method) {
        case 'pickVideo':
          _pickFile(FileType.video, 'window.onVideoPicked');
          break;
        case 'pickAudio':
          _pickFile(FileType.audio, 'window.onAudioPicked');
          break;
        case 'pickMergeClips':
          _pickMergeClips();
          break;
        case 'exportEdited':
          _exportEdited(params);
          break;
        case 'exportMerge':
          _exportMerge(params);
          break;
      }
    } catch (e) {
      debugPrint('Error handling JS message: $e');
    }
  }

  void _pickFile(FileType type, String callback) async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(type: type);
    if (result != null && result.files.single.path != null) {
      final file = result.files.single;
      final name = file.name.replaceAll('"', '\\"');
      final path = file.path!.replaceAll('\\', '/');
      _controller.runJavaScript('$callback("$name", "$path")');
    }
  }

  void _pickMergeClips() async {
    FilePickerResult? result = await FilePicker.platform.pickFiles(
      type: FileType.video,
      allowMultiple: true,
    );
    if (result != null) {
      final List<Map<String, String>> clips = result.files
          .where((f) => f.path != null)
          .map((f) => {'name': f.name, 'path': f.path!.replaceAll('\\', '/')})
          .toList();
      _controller.runJavaScript('window.onMergeClipsPicked(\'${jsonEncode(clips)}\')');
    }
  }

  void _exportEdited(Map<String, dynamic> params) async {
    final String inputPath = params['videoPath'];
    final double start = (params['trimStart'] as num).toDouble();
    final double end = (params['trimEnd'] as num).toDouble();
    final List<dynamic> textClips = params['textClips'];
    final bool muted = params['muted'];
    final String filter = params['filter'];

    final Directory tempDir = await getTemporaryDirectory();
    final String outputPath = '${tempDir.path}/edited_${DateTime.now().millisecondsSinceEpoch}.mp4';

    List<String> filters = [];
    if (filter == 'grayscale(1)') filters.add('hue=s=0');
    else if (filter == 'sepia(.85)') filters.add('colorchannelmixer=.393:.769:.189:0:.349:.686:.168:0:.272:.534:.131');
    else if (filter.contains('contrast(1.25)')) filters.add('eq=contrast=1.25:saturation=1.35');
    else if (filter.contains('brightness(1.12)')) filters.add('eq=brightness=0.08:contrast=0.92');

    for (var clip in textClips) {
      final String text = clip['text'].toString().replaceAll("'", "'\\''");
      final double s = (clip['start'] as num).toDouble();
      final double e = (clip['end'] as num).toDouble();
      final double x = (clip['x'] as num).toDouble();
      final double y = (clip['y'] as num).toDouble();
      // Using default font, centering x/y based on percentages
      filters.add("drawtext=text='$text':fontcolor=white:fontsize=42:box=1:boxcolor=black@0.5:x=(w-text_w)*${x/100}:y=(h-text_h)*${y/100}:enable='between(t,$s,$e)'");
    }

    String vf = filters.isNotEmpty ? '-vf "${filters.join(',')}"' : '';
    String ss = start > 0 ? '-ss $start' : '';
    String t = end > start ? '-t ${end - start}' : '';
    String an = muted ? '-an' : '';

    String command = '$ss -i "$inputPath" $t $vf $an -c:v libx264 -preset ultrafast -crf 28 -c:a aac "$outputPath" -y';

    _controller.runJavaScript('window.onExportProgress("Processing with FFmpeg...")');

    FFmpegKit.executeAsync(command, (session) async {
      final returnCode = await session.getReturnCode();
      if (ReturnCode.isSuccess(returnCode)) {
        if (!await Gal.hasAccess()) {
          await Gal.requestAccess();
        }
        try {
          await Gal.putVideo(outputPath);
          _controller.runJavaScript('window.onExportDone(true, "Success", "$outputPath")');
        } catch (e) {
          _controller.runJavaScript('window.onExportDone(false, "Failed to save to gallery: $e", "")');
        }
      } else {
        final logs = await session.getAllLogsAsString();
        debugPrint('FFmpeg Logs: $logs');
        _controller.runJavaScript('window.onExportDone(false, "FFmpeg failed. Check logs.", "")');
      }
    });
  }

  void _exportMerge(Map<String, dynamic> params) async {
    final List<dynamic> clips = params['clips'];
    final Directory tempDir = await getTemporaryDirectory();
    final String listPath = '${tempDir.path}/merge_list.txt';
    final String outputPath = '${tempDir.path}/merged_${DateTime.now().millisecondsSinceEpoch}.mp4';

    final File listFile = File(listPath);
    String content = '';
    for (var clip in clips) {
      content += "file '${clip['path']}'\n";
    }
    await listFile.writeAsString(content);

    String command = '-f concat -safe 0 -i "$listPath" -c copy "$outputPath" -y';

    _controller.runJavaScript('window.onExportProgress("Merging clips...")');

    FFmpegKit.executeAsync(command, (session) async {
      final returnCode = await session.getReturnCode();
      if (ReturnCode.isSuccess(returnCode)) {
        if (!await Gal.hasAccess()) {
          await Gal.requestAccess();
        }
        try {
          await Gal.putVideo(outputPath);
          _controller.runJavaScript('window.onMergeDone(true, "Success", "$outputPath")');
        } catch (e) {
          _controller.runJavaScript('window.onMergeDone(false, "Failed to save to gallery: $e", "")');
        }
      } else {
        _controller.runJavaScript('window.onMergeDone(false, "Merge failed", "")');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: WebViewWidget(controller: _controller),
      ),
    );
  }
}
