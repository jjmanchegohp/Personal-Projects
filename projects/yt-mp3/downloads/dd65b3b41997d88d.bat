@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\dd65b3b41997d88d_%(title)s.%(ext)s" "https://youtu.be/0AiexSrQFvs" > "C:\xampp\htdocs\yt-mp3\downloads\dd65b3b41997d88d.log" 2>&1
