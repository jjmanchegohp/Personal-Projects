# YouTube to MP3

A tiny local web app: paste a YouTube link (video or public playlist), get MP3s back.
Runs entirely on your machine. Built with Flask, [yt-dlp](https://github.com/yt-dlp/yt-dlp) and ffmpeg.

## Quick start

Requires `python3` and `ffmpeg`.

```sh
sudo xbps-install -S python3 ffmpeg   # Void Linux (use your package manager otherwise)
git clone https://github.com/YOUR_USER/yt-mp3-app.git
cd yt-mp3-app
./start.sh
```

`start.sh` creates the virtual environment on first run, installs dependencies, starts the app and opens http://127.0.0.1:5000.

| Command | What it does |
|---|---|
| `./start.sh` | Start the app |
| `./start.sh --update` | Update yt-dlp, then start (do this if downloads suddenly fail) |
| `./start.sh --install-launcher` | Add "YouTube to MP3" to your applications menu |

Prefer doing it by hand?

```sh
python3 -m venv venv && . venv/bin/activate
pip install -r requirements.txt
python3 app.py
```

## Usage

- **Single video:** paste the URL, click *Look up*, edit the file name if you want, click *Download MP3*.
- **Playlist:** paste a public playlist URL, click *Look up*, untick what you don't want, rename tracks, click *Download selected*. Tracks you already downloaded are listed separately and unticked.

MP3s are saved in `downloads/`. The `/status` page shows system checks (ffmpeg, yt-dlp, disk space) and the last 10 downloads.

## Configuration

Set these environment variables when starting the app:

| Variable | Default | Description |
|---|---|---|
| `PORT` | `5000` | Port to listen on |
| `HOST` | `127.0.0.1` | Use `0.0.0.0` to expose it on your LAN (no authentication, trusted networks only) |
| `MP3_QUALITY` | `192` | Bitrate in kbps (e.g. `320`) |
| `MAX_PARALLEL` | `3` | Simultaneous downloads |
| `FORCE_IPV4` | unset | Set to `1` to fix SSL / connection errors |

Example: `MP3_QUALITY=320 PORT=8080 ./start.sh`

## Project layout

```
app.py            Flask backend (lookup, download queue, status)
static/           Frontend: index.html, status.html, app.js, style.css
start.sh          Launcher (venv, dependencies, browser)
requirements.txt  Python dependencies
```

<details>
<summary>Start at boot with runit (Void Linux)</summary>

Run `./start.sh` once first so `venv/` exists. Replace `YOUR_USER` and the path:

```sh
sudo mkdir -p /etc/sv/yt-mp3
sudo tee /etc/sv/yt-mp3/run > /dev/null << 'EOF'
#!/bin/sh
exec chpst -u YOUR_USER /home/YOUR_USER/yt-mp3-app/venv/bin/python /home/YOUR_USER/yt-mp3-app/app.py 2>&1
EOF
sudo chmod +x /etc/sv/yt-mp3/run
sudo ln -s /etc/sv/yt-mp3 /var/service/
```

Control it with `sudo sv stop yt-mp3` / `sudo sv start yt-mp3`.
</details>

## Legal

Only download content you have the right to download: your own uploads, Creative Commons / public domain material, or anything you have explicit permission for. Downloading copyrighted material from YouTube without permission violates YouTube's Terms of Service and copyright law in most places.
