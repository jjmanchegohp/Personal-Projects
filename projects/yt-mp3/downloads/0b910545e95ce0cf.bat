@echo off
chcp 65001 > nul
"C:\xampp\htdocs\projects\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\projects\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\projects\yt-mp3\downloads\0b910545e95ce0cf_%(title)s.%(ext)s" "https://youtu.be/Ttwvz7QY4cg" > "C:\xampp\htdocs\projects\yt-mp3\downloads\0b910545e95ce0cf.log" 2>&1
