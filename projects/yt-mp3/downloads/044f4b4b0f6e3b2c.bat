@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\044f4b4b0f6e3b2c_%(title)s.%(ext)s" "https://youtu.be/ZAz3rnLGthg" > "C:\xampp\htdocs\yt-mp3\downloads\044f4b4b0f6e3b2c.log" 2>&1
