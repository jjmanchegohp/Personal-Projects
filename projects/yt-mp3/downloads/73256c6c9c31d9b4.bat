@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\73256c6c9c31d9b4_%(title)s.%(ext)s" "https://youtu.be/Ma8NR_EQXlU" > "C:\xampp\htdocs\yt-mp3\downloads\73256c6c9c31d9b4.log" 2>&1
