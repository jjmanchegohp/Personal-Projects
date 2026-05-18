@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\1702ffa2842872d3_%(title)s.%(ext)s" "https://youtu.be/D1gl46hh3sQ" > "C:\xampp\htdocs\yt-mp3\downloads\1702ffa2842872d3.log" 2>&1
