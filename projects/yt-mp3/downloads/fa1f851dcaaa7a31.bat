@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\fa1f851dcaaa7a31_%(title)s.%(ext)s" "https://youtu.be/vQ0u09mFodw" > "C:\xampp\htdocs\yt-mp3\downloads\fa1f851dcaaa7a31.log" 2>&1
