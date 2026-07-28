@echo off
chcp 65001 > nul
"C:\xampp\htdocs\Personal\projects\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\Personal\projects\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\Personal\projects\yt-mp3\downloads\c3770e06f7794676_%(title)s.%(ext)s" "https://youtu.be/KjymkWV09nc?si=VD6q9MxvMyuPYk4E" > "C:\xampp\htdocs\Personal\projects\yt-mp3\downloads\c3770e06f7794676.log" 2>&1
