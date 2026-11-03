#!/usr/bin/env bash
# Local development.
#   ./dev.sh          export completed Acts from the scraper, then serve http://localhost:8800
#   ./dev.sh export   only export (scraper → content/)
#   ./dev.sh serve    only serve
# SCRAPER_DIR (default ../indiacode-scraper) and PORT (default 8800) can be overridden.
set -euo pipefail
cd "$(dirname "$0")"
ROOT="$(pwd)"
SCRAPER_DIR="${SCRAPER_DIR:-$ROOT/../indiacode-scraper}"
PORT="${PORT:-8800}"

export_content() {
  (cd "$SCRAPER_DIR" && .venv/bin/python indiacode_scraper.py export-web --out "$ROOT/content")
}

serve() {
  echo "Serving http://localhost:$PORT  (Ctrl+C to stop)"
  php -S "localhost:$PORT" -t public router.php
}

case "${1:-all}" in
  export) export_content ;;
  serve) serve ;;
  all) export_content && serve ;;
  *) echo "usage: ./dev.sh [export|serve]" >&2; exit 2 ;;
esac
