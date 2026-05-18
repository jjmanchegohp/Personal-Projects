@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\05396dd5ecec22ed_%(title)s.%(ext)s" "https://youtu.be/-fU6VLVbA2A" > "C:\xampp\htdocs\yt-mp3\downloads\05396dd5ecec22ed.log" 2>&1
