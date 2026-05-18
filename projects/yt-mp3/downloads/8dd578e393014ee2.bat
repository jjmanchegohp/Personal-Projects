@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\8dd578e393014ee2_%(title)s.%(ext)s" "https://youtu.be/jKT4ArZCkso" > "C:\xampp\htdocs\yt-mp3\downloads\8dd578e393014ee2.log" 2>&1
