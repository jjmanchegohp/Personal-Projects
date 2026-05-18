@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\d5cb5056cdeaf744_%(title)s.%(ext)s" "https://youtu.be/OyNZmKm-d_M" > "C:\xampp\htdocs\yt-mp3\downloads\d5cb5056cdeaf744.log" 2>&1
