@echo off
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" -x --audio-format mp3 --audio-quality 192K --ffmpeg-location "C:\xampp\htdocs\yt-mp3\bin" --newline -o "C:\xampp\htdocs\yt-mp3\downloads\a92b384d2310d5f0_%(title)s.%(ext)s" "https://youtu.be/0AiexSrQFvs" > "C:\xampp\htdocs\yt-mp3\downloads\a92b384d2310d5f0.log" 2>&1
