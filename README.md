# Vidih Studio

Browser video editor with FFmpeg export (cut, merge, text, mute, filters, music).

## Screenshots

### Studio

![Vidih Studio](docs/screenshots/01-studio.png)

## What you need

- XAMPP (Apache + PHP)
- FFmpeg installed, default path `C:\ffmpeg\bin\ffmpeg.exe`  
  or set environment variable `VIDIH_FFMPEG` to your `ffmpeg.exe`

## Step-by-step setup

1. Install [FFmpeg](https://ffmpeg.org/download.html) and XAMPP.
2. Confirm this file exists: `C:\ffmpeg\bin\ffmpeg.exe` (or set `VIDIH_FFMPEG`).
3. Start **Apache**.
4. Clone into `htdocs`:
   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/chewzees/vidih-studio.git
   ```
5. Raise PHP upload limits if you will import large videos. In `C:\xampp\php\php.ini` set for example:
   ```
   upload_max_filesize=500M
   post_max_size=500M
   ```
   Restart Apache.
6. Open:
   `http://localhost/vidih-studio/`

Original nested path: `http://localhost/everything%20that%20work/vidih/`

## Step-by-step usage

1. Open the studio page.
2. Upload a video into the library.
3. Optional: upload music and select it so the export includes audio.
4. Choose a tool:
   - **Cut** — set start and end
   - **Merge** — join clips
   - **Text** — titles on the timeline
   - **Mute** — remove video sound
   - **Filters** — look presets
5. Preview, then export. The file is processed with FFmpeg through `vidih_api.php`.
6. Download the result from the exports area.

Uploads and exports older than 7 days are cleaned automatically. Max upload defaults to 500 MB (also limited by PHP).

## If something goes wrong

- **Export fails:** FFmpeg is missing or `VIDIH_FFMPEG` points to the wrong file.
- **Upload too large:** raise `upload_max_filesize` / `post_max_size` and restart Apache.
- **404:** Apache must be running and the folder must be inside `htdocs`.
