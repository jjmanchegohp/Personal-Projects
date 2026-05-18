@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\9fb9fa5197ba9b4f_%(title)s.%(ext)s" "https://youtu.be/4OkTwHMz874" > "C:\xampp\htdocs\yt-mp3\downloads\9fb9fa5197ba9b4f.log" 2>&1
