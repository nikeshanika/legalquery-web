#!/usr/bin/env bash
# Local development.
#   ./dev.sh          export completed Acts from the scraper, load them into PostgreSQL, then serve
#                     http://localhost:8800
#   ./dev.sh export   export (scraper → content/), then load into PostgreSQL (legalquery-ai)
#   ./dev.sh load     only the PostgreSQL load (legalquery-ai: lqai load, then lqai load-translations)
#   ./dev.sh serve    only serve
# SCRAPER_DIR (default ../indiacode-scraper), LQAI_DIR (default ../legalquery-ai), TRANSLATIONS_DIR
# (default ../indiacode/translations), HOST (default localhost) and PORT (default 8800) can be
# overridden. LQAI_SKIP_LOAD=1 skips the PostgreSQL load.
# HOST=0.0.0.0 ./dev.sh serve  makes it reachable from other machines on the network.
# The site itself never reads PostgreSQL: the load only feeds the legalquery-ai chunk store.
set -euo pipefail
cd "$(dirname "$0")"
ROOT="$(pwd)"
SCRAPER_DIR="${SCRAPER_DIR:-$ROOT/../indiacode-scraper}"
LQAI_DIR="${LQAI_DIR:-$ROOT/../legalquery-ai}"
TRANSLATIONS_DIR="${TRANSLATIONS_DIR:-$ROOT/../indiacode/translations}"
HOST="${HOST:-localhost}"
PORT="${PORT:-8800}"

export_content() {
  (cd "$SCRAPER_DIR" && .venv/bin/python indiacode_scraper.py export-web --out "$ROOT/content")
}

# content/ → PostgreSQL. Only changed Acts are written; a no-change run takes seconds. Skipped (with a
# note) when legalquery-ai is not set up here.
load_db() {
  if [ "${LQAI_SKIP_LOAD:-}" = "1" ]; then
    echo "LQAI_SKIP_LOAD=1: PostgreSQL load skipped" >&2
    return 0
  fi
  if [ ! -x "$LQAI_DIR/.venv/bin/lqai" ]; then
    echo "legalquery-ai not set up at $LQAI_DIR: PostgreSQL load skipped" >&2
    return 0
  fi
  (cd "$LQAI_DIR" && LQAI_CONTENT_DIR="$ROOT/content" .venv/bin/lqai load)
  # AI translations made in the indiacode project (read only). Translations of Acts not exported yet are
  # kept and attach on a later run.
  if [ -d "$TRANSLATIONS_DIR" ]; then
    (cd "$LQAI_DIR" && .venv/bin/lqai load-translations --dir "$TRANSLATIONS_DIR")
  else
    echo "no translations at $TRANSLATIONS_DIR: translation load skipped" >&2
  fi
}

serve() {
  if [ "$HOST" = "0.0.0.0" ]; then
    echo "Serving http://localhost:$PORT and http://$(ipconfig getifaddr en0 2>/dev/null || hostname):$PORT  (Ctrl+C to stop)"
  else
    echo "Serving http://$HOST:$PORT  (Ctrl+C to stop)"
  fi
  local url="http://$HOST:$PORT"
  [ "$HOST" = "0.0.0.0" ] && url="http://$(ipconfig getifaddr en0 2>/dev/null || hostname):$PORT"
  SITE_URL="${SITE_URL:-$url}" php -S "$HOST:$PORT" -t public router.php
}

case "${1:-all}" in
  export) export_content && load_db ;;
  load) load_db ;;
  serve) serve ;;
  # A failed PostgreSQL load does not keep the site from serving the new export.
  all) export_content && { load_db || echo "warning: PostgreSQL load failed (see above); serving anyway" >&2; } && serve ;;
  *) echo "usage: ./dev.sh [export|load|serve]" >&2; exit 2 ;;
esac
