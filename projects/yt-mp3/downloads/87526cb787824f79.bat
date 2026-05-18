@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\87526cb787824f79_%(title)s.%(ext)s" "https://youtube.com/shorts/Wu2ZlRPm-6U?si=wa6AGnCCVmxMg0A7" > "C:\xampp\htdocs\yt-mp3\downloads\87526cb787824f79.log" 2>&1
