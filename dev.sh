#!/usr/bin/env bash
# Local development.
#   ./dev.sh          export completed Acts from the scraper, then serve http://localhost:8800
#   ./dev.sh export   only export (scraper → content/)
#   ./dev.sh serve    only serve
# SCRAPER_DIR (default ../indiacode-scraper), HOST (default localhost) and PORT (default 8800) can be
# overridden. HOST=0.0.0.0 ./dev.sh serve  makes it reachable from other machines on the network.
set -euo pipefail
cd "$(dirname "$0")"
ROOT="$(pwd)"
SCRAPER_DIR="${SCRAPER_DIR:-$ROOT/../indiacode-scraper}"
HOST="${HOST:-localhost}"
PORT="${PORT:-8800}"

export_content() {
  (cd "$SCRAPER_DIR" && .venv/bin/python indiacode_scraper.py export-web --out "$ROOT/content")
}

serve() {
  if [ "$HOST" = "0.0.0.0" ]; then
    echo "Serving http://localhost:$PORT and http://$(ipconfig getifaddr en0 2>/dev/null || hostname):$PORT  (Ctrl+C to stop)"
  else
    echo "Serving http://$HOST:$PORT  (Ctrl+C to stop)"
  fi
  php -S "$HOST:$PORT" -t public router.php
}

case "${1:-all}" in
  export) export_content ;;
  serve) serve ;;
  all) export_content && serve ;;
  *) echo "usage: ./dev.sh [export|serve]" >&2; exit 2 ;;
esac
