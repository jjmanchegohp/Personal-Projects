@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\35bc5911d0dc6531_%(title)s.%(ext)s" "https://youtu.be/FZWHVo-t2Vo" > "C:\xampp\htdocs\yt-mp3\downloads\35bc5911d0dc6531.log" 2>&1
