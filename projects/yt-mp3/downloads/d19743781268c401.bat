@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\d19743781268c401_%(title)s.%(ext)s" "https://youtu.be/gJYjbDnyx-o" > "C:\xampp\htdocs\yt-mp3\downloads\d19743781268c401.log" 2>&1
