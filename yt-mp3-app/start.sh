#!/bin/sh
# YouTube -> MP3 launcher.
#   ./start.sh                     start the app (sets itself up on first run)
#   ./start.sh --update            update yt-dlp, then start
#   ./start.sh --install-launcher  add "YouTube to MP3" to your applications menu
#   FORCE_IPV4=1 ./start.sh        start with IPv4 only (fixes some SSL errors)

set -e
cd "$(dirname "$(readlink -f "$0")")"
APP_DIR="$(pwd)"
PORT="${PORT:-5000}"; export PORT
URL="http://127.0.0.1:$PORT"

if [ "$1" = "--install-launcher" ]; then
  DESKTOP_DIR="${XDG_DATA_HOME:-$HOME/.local/share}/applications"
  mkdir -p "$DESKTOP_DIR"
  cat > "$DESKTOP_DIR/yt-mp3.desktop" << EOF
[Desktop Entry]
Type=Application
Name=YouTube to MP3
Comment=Local YouTube to MP3 downloader
Exec="$APP_DIR/start.sh"
Path=$APP_DIR
Terminal=true
Icon=audio-x-generic
Categories=AudioVideo;Audio;
EOF
  echo "Launcher installed: $DESKTOP_DIR/yt-mp3.desktop"
  echo "Look for 'YouTube to MP3' in your applications menu."
  exit 0
fi

# --- system dependencies -----------------------------------------------------
missing=""
command -v python3 >/dev/null 2>&1 || missing="$missing python3"
command -v ffmpeg  >/dev/null 2>&1 || missing="$missing ffmpeg"
if [ -n "$missing" ]; then
  echo "Missing system packages:$missing"
  echo "Install them with:  sudo xbps-install -S$missing"
  exit 1
fi

# --- virtual environment + python packages -----------------------------------
if [ ! -d venv ]; then
  echo "First run: creating virtual environment..."
  python3 -m venv venv
fi
. venv/bin/activate

if [ ! -f venv/.deps_installed ] || [ requirements.txt -nt venv/.deps_installed ]; then
  echo "Installing Python packages..."
  pip install -q -r requirements.txt
  touch venv/.deps_installed
fi

if [ "$1" = "--update" ]; then
  echo "Updating yt-dlp..."
  pip install -q -U yt-dlp
fi

# --- already running? just open the browser ----------------------------------
if python3 -c "import socket,sys; s=socket.socket(); s.settimeout(0.5); sys.exit(0 if s.connect_ex(('127.0.0.1', $PORT))==0 else 1)"; then
  echo "App is already running at $URL"
  command -v xdg-open >/dev/null 2>&1 && xdg-open "$URL" >/dev/null 2>&1 &
  exit 0
fi

# --- start --------------------------------------------------------------------
echo "Starting at $URL  (Ctrl+C to stop)"
if command -v xdg-open >/dev/null 2>&1; then
  ( sleep 2; xdg-open "$URL" >/dev/null 2>&1 ) &
fi
exec python3 app.py
