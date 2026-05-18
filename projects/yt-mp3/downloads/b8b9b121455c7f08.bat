@echo off
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" -x --audio-format mp3 --audio-quality 192K --ffmpeg-location "C:\xampp\htdocs\yt-mp3\bin" --newline -o "C:\xampp\htdocs\yt-mp3\downloads\b8b9b121455c7f08_%(title)s.%(ext)s" "https://youtu.be/FZWHVo-t2Vo" > "C:\xampp\htdocs\yt-mp3\downloads\b8b9b121455c7f08.log" 2>&1
