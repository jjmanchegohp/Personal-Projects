@echo off
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" -x --audio-format mp3 --audio-quality 192K --ffmpeg-location "C:\xampp\htdocs\yt-mp3\bin" --newline -o "C:\xampp\htdocs\yt-mp3\downloads\2caea7d65ea6f570_%(title)s.%(ext)s" "https://youtu.be/0_M2JX-Olv8" > "C:\xampp\htdocs\yt-mp3\downloads\2caea7d65ea6f570.log" 2>&1
