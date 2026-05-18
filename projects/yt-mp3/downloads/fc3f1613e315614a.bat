@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\fc3f1613e315614a_%(title)s.%(ext)s" "https://youtu.be/Qty6reoLDKY" > "C:\xampp\htdocs\yt-mp3\downloads\fc3f1613e315614a.log" 2>&1
