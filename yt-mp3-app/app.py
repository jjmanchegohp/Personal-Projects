#!/usr/bin/env python3
"""Local web app: paste a YouTube video or playlist link, get MP3s.

Uses yt-dlp for extraction and ffmpeg for conversion. Run: python3 app.py
"""

import json
import logging
import os
import platform
import re
import shutil
import sys
import threading
import time
import uuid
from collections import deque
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime
from pathlib import Path
from urllib.parse import parse_qs, urlparse

import yt_dlp
from flask import Flask, jsonify, request, send_from_directory

# --- config (override with environment variables) ----------------------------
HOST = os.environ.get("HOST", "127.0.0.1")
PORT = int(os.environ.get("PORT", "5000"))
MP3_QUALITY = os.environ.get("MP3_QUALITY", "192")        # kbps
MAX_PARALLEL = int(os.environ.get("MAX_PARALLEL", "3"))   # simultaneous downloads
FORCE_IPV4 = os.environ.get("FORCE_IPV4") == "1"          # helps with SSL errors

APP_DIR = Path(__file__).resolve().parent
DOWNLOAD_DIR = APP_DIR / "downloads"
DOWNLOAD_DIR.mkdir(exist_ok=True)
INDEX_FILE = DOWNLOAD_DIR / "downloaded.json"  # video_id -> filename

YOUTUBE_HOSTS = {"www.youtube.com", "youtube.com", "m.youtube.com",
                 "music.youtube.com", "youtu.be"}

app = Flask(__name__)
pool = ThreadPoolExecutor(max_workers=MAX_PARALLEL)
lock = threading.Lock()
JOBS = {}                     # job_id -> {"status", "filename", "error"}
ERRORS = deque(maxlen=10)     # recent errors (in memory only)


# --- helpers -----------------------------------------------------------------
def is_youtube_url(url: str) -> bool:
    p = urlparse(url)
    return p.scheme in ("http", "https") and p.netloc in YOUTUBE_HOSTS


def is_playlist_url(url: str) -> bool:
    p = urlparse(url)
    return "list" in parse_qs(p.query) or p.path.startswith("/playlist")


def video_id_from_url(url: str):
    p = urlparse(url)
    if p.netloc == "youtu.be":
        return p.path.lstrip("/") or None
    return (parse_qs(p.query).get("v") or [None])[0]


def safe_filename(name: str, fallback: str) -> str:
    name = re.sub(r'[\\/*?:"<>|]', "_", (name or "").strip()).strip(" .")[:150]
    return name or fallback


def load_index() -> dict:
    try:
        return json.loads(INDEX_FILE.read_text(encoding="utf-8"))
    except Exception:
        return {}


def record_download(video_id, filename: str):
    if not video_id:
        return
    with lock:
        index = load_index()
        index[video_id] = filename
        INDEX_FILE.write_text(json.dumps(index, ensure_ascii=False, indent=1), encoding="utf-8")


def find_downloaded(video_id, suggested: str, index: dict, stems: set):
    """Filename on disk if the track was already downloaded, else None."""
    name = index.get(video_id)
    if name and (DOWNLOAD_DIR / name).exists():
        return name
    if suggested.lower() in stems:  # fallback: match by title
        return suggested + ".mp3"
    return None


def get_url(data: dict):
    url = (data.get("url") or "").strip()
    return url if url and is_youtube_url(url) else None


def system_checks() -> list:
    ffmpeg = shutil.which("ffmpeg")
    checks = [
        {"name": "ffmpeg", "ok": bool(ffmpeg),
         "detail": ffmpeg or "Not found on PATH -- MP3 conversion will fail."},
        {"name": "yt-dlp", "ok": True, "detail": f"version {yt_dlp.version.__version__}"},
    ]
    try:
        test = DOWNLOAD_DIR / ".write_test"
        test.write_text("ok")
        test.unlink()
        checks.append({"name": "downloads folder", "ok": True, "detail": str(DOWNLOAD_DIR)})
    except Exception as e:
        checks.append({"name": "downloads folder", "ok": False, "detail": f"not writable: {e}"})
    free_mb = shutil.disk_usage(DOWNLOAD_DIR).free / (1024 * 1024)
    checks.append({"name": "disk space", "ok": free_mb > 200, "detail": f"{free_mb:.0f} MB free"})
    checks.append({"name": "python", "ok": True,
                   "detail": f"{platform.python_version()} ({sys.platform})"})
    return checks


def recent_downloads(limit=10) -> list:
    """Last downloads read from disk (survives restarts) plus this session's errors."""
    entries = []
    for f in DOWNLOAD_DIR.glob("*.mp3"):
        try:
            ts = f.stat().st_mtime
        except FileNotFoundError:
            continue
        entries.append({"ts": ts, "filename": f.name, "status": "done", "error": None})
    with lock:
        entries.extend(ERRORS)
    entries.sort(key=lambda e: e["ts"], reverse=True)
    return [{**e, "time": datetime.fromtimestamp(e["ts"]).strftime("%Y-%m-%d %H:%M:%S")}
            for e in entries[:limit]]


# --- download worker ---------------------------------------------------------
def run_download(job_id: str, url: str, desired_filename: str):
    job = JOBS[job_id]
    job["status"] = "downloading"
    opts = {
        "format": "bestaudio/best",
        "outtmpl": str(DOWNLOAD_DIR / f"{job_id}.%(ext)s"),
        "postprocessors": [{"key": "FFmpegExtractAudio",
                            "preferredcodec": "mp3", "preferredquality": MP3_QUALITY}],
        "noplaylist": True,
        "quiet": True,
        "no_warnings": True,
        "no_color": True,
        "retries": 10,
        "fragment_retries": 10,
        "socket_timeout": 30,
    }
    if FORCE_IPV4:
        opts["source_address"] = "0.0.0.0"

    try:
        for attempt in range(1, 4):  # retry transient network/SSL failures
            try:
                with yt_dlp.YoutubeDL(opts) as ydl:
                    ydl.extract_info(url, download=True)
                break
            except Exception:
                if attempt == 3:
                    raise
                job["status"] = f"retrying ({attempt + 1}/3)"
                time.sleep(3)

        tmp = DOWNLOAD_DIR / f"{job_id}.mp3"
        if not tmp.exists():
            raise RuntimeError("Conversion finished but MP3 file not found.")

        base = safe_filename(desired_filename, job_id)
        final, n = DOWNLOAD_DIR / f"{base}.mp3", 1
        while final.exists():
            final = DOWNLOAD_DIR / f"{base} ({n}).mp3"
            n += 1
        tmp.rename(final)
        record_download(video_id_from_url(url), final.name)
        job.update(status="done", filename=final.name)

    except Exception as e:
        msg = re.sub(r"\x1b\[[0-9;]*m", "", str(e))
        job.update(status="error", error=msg)
        with lock:
            ERRORS.appendleft({"ts": time.time(), "filename": desired_filename or job_id,
                               "status": "error", "error": msg})


def queue_download(url: str, filename: str) -> str:
    job_id = uuid.uuid4().hex
    JOBS[job_id] = {"status": "queued"}
    pool.submit(run_download, job_id, url, filename)
    return job_id


# --- routes ------------------------------------------------------------------
@app.route("/")
def home():
    return app.send_static_file("index.html")


@app.route("/status")
def status_page():
    return app.send_static_file("status.html")


@app.route("/api/status")
def api_status():
    return jsonify({"checks": system_checks(), "history": recent_downloads()})


@app.route("/lookup", methods=["POST"])
def lookup():
    """Fetch metadata only (no download): single video or playlist."""
    url = get_url(request.get_json(silent=True) or {})
    if not url:
        return jsonify({"error": "Please provide a valid YouTube URL."}), 400

    playlist = is_playlist_url(url)
    opts = {"quiet": True, "no_warnings": True, "skip_download": True}
    opts.update({"extract_flat": "in_playlist"} if playlist else {"noplaylist": True})
    try:
        with yt_dlp.YoutubeDL(opts) as ydl:
            info = ydl.extract_info(url, download=False)
    except Exception as e:
        return jsonify({"error": str(e)}), 400

    if not playlist:
        title = info.get("title", "audio")
        return jsonify({"type": "video", "title": title,
                        "suggested_filename": safe_filename(title, "audio")})

    index = load_index()
    stems = {f.stem.lower() for f in DOWNLOAD_DIR.glob("*.mp3")}
    tracks = []
    for entry in info.get("entries") or []:
        vid = entry and entry.get("id")
        if not vid:
            continue
        title = entry.get("title") or vid
        suggested = safe_filename(title, vid)
        done = find_downloaded(vid, suggested, index, stems)
        tracks.append({
            "url": f"https://www.youtube.com/watch?v={vid}",
            "title": title,
            "suggested_filename": suggested,
            "downloaded": done is not None,
        })
    if not tracks:
        return jsonify({"error": "No tracks found in this playlist (it may be private or empty)."}), 400

    tracks.sort(key=lambda t: t["downloaded"])  # new tracks first (stable sort)
    return jsonify({"type": "playlist", "playlist_title": info.get("title"), "tracks": tracks})


@app.route("/start", methods=["POST"])
def start():
    data = request.get_json(silent=True) or {}
    url, filename = get_url(data), (data.get("filename") or "").strip()
    if not url:
        return jsonify({"error": "Please provide a valid YouTube URL."}), 400
    if not filename:
        return jsonify({"error": "Please provide a filename."}), 400
    return jsonify({"job_id": queue_download(url, filename)})


@app.route("/start_playlist", methods=["POST"])
def start_playlist():
    data = request.get_json(silent=True) or {}
    jobs = []
    for t in data.get("tracks") or []:
        url, filename = get_url(t), (t.get("filename") or "").strip()
        if url and filename:
            jobs.append({"id": t.get("id"), "job_id": queue_download(url, filename)})
    if not jobs:
        return jsonify({"error": "No valid tracks to download."}), 400
    return jsonify({"jobs": jobs})


@app.route("/job/<job_id>")
def job_status(job_id):
    job = JOBS.get(job_id)
    if not job:
        return jsonify({"status": "error", "error": "Unknown job id"}), 404
    return jsonify(job)


@app.route("/file/<path:filename>")
def get_file(filename):
    return send_from_directory(DOWNLOAD_DIR, filename, as_attachment=True)


class QuietStatusFilter(logging.Filter):
    """Hide the periodic /api/status polling from the access log."""
    def filter(self, record):
        return "/api/status" not in record.getMessage()


if __name__ == "__main__":
    logging.getLogger("werkzeug").addFilter(QuietStatusFilter())
    app.run(host=HOST, port=PORT, debug=False)
