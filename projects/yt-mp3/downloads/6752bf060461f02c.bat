@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\6752bf060461f02c_%(title)s.%(ext)s" "https://youtu.be/YJ84U_30GRM" > "C:\xampp\htdocs\yt-mp3\downloads\6752bf060461f02c.log" 2>&1
