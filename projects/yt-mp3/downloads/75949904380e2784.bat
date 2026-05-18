@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\75949904380e2784_%(title)s.%(ext)s" "https://youtu.be/CyG05z2eX4M" > "C:\xampp\htdocs\yt-mp3\downloads\75949904380e2784.log" 2>&1
