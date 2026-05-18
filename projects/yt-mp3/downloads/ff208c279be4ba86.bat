@echo off
chcp 65001 > nul
"C:\xampp\htdocs\projects\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\projects\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\projects\yt-mp3\downloads\ff208c279be4ba86_%(title)s.%(ext)s" "https://youtu.be/ZIF5r4Eb9FY" > "C:\xampp\htdocs\projects\yt-mp3\downloads\ff208c279be4ba86.log" 2>&1
