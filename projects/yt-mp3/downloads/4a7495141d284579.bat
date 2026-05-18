@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\4a7495141d284579_%(title)s.%(ext)s" "https://youtu.be/L8gGHqPBuZM" > "C:\xampp\htdocs\yt-mp3\downloads\4a7495141d284579.log" 2>&1
