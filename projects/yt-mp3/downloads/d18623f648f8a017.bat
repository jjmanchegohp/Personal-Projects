@echo off
chcp 65001 > nul
"C:\xampp\htdocs\yt-mp3\bin\yt-dlp.exe" "--extract-audio" "--audio-format" "mp3" "--audio-quality" "192K" "--ffmpeg-location" "C:\xampp\htdocs\yt-mp3\bin" "--newline" "--no-playlist" "-o" "C:\xampp\htdocs\yt-mp3\downloads\d18623f648f8a017_%(title)s.%(ext)s" "https://youtu.be/_KztNIg4cvE" > "C:\xampp\htdocs\yt-mp3\downloads\d18623f648f8a017.log" 2>&1
