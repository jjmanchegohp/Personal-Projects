@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\b5213a096b165e56_%(title)s.%(ext)s" "https://youtu.be/PuwWj2_wL2E" > "C:\xampp\htdocs\yt-mp3\downloads\b5213a096b165e56.log" 2>&1
