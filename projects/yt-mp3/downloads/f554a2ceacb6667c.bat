@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\f554a2ceacb6667c_%(title)s.%(ext)s" "https://youtu.be/KWYTVrMVw58" > "C:\xampp\htdocs\yt-mp3\downloads\f554a2ceacb6667c.log" 2>&1
