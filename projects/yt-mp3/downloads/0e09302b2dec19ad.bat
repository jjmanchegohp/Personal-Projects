@echo off
chcp 65001 > nul
"C:\xampp\htdocs\projects\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\projects\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\projects\yt-mp3\downloads\0e09302b2dec19ad_%(title)s.%(ext)s" "https://youtu.be/O0Cw1SLdxxE" > "C:\xampp\htdocs\projects\yt-mp3\downloads\0e09302b2dec19ad.log" 2>&1
