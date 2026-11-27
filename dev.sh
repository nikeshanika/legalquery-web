#!/usr/bin/env bash
# Local development.
#   ./dev.sh          export completed Acts from the scraper, load them into PostgreSQL, then serve
#                     http://localhost:8800
#   ./dev.sh export   export (scraper → content/), then load into PostgreSQL (legalquery-ai)
#   ./dev.sh load     only the PostgreSQL load (legalquery-ai: lqai load, then lqai load-translations)
#   ./dev.sh serve    only serve
#   ./dev.sh tunnel   serve through the dev tunnel TUNNEL (default legalquery-web): the same public URL
#                     every day, used as SITE_URL. Creates the tunnel (anonymous access, port PORT) on
#                     first use; Microsoft deletes a tunnel after 30 days without use.
# SCRAPER_DIR (default ../indiacode-scraper), LQAI_DIR (default ../legalquery-ai), TRANSLATIONS_TEXT_DIR
# (default ../translation-ledger/text: the AI translations' translation.json + document.rag.json; the site's own
# ./translations folder holds only their PDFs), HOST (default localhost) and PORT (default 8800) can be
# overridden. LQAI_SKIP_LOAD=1 skips the PostgreSQL load.
# HOST=0.0.0.0 ./dev.sh serve  makes it reachable from other machines on the network.
# The site itself never reads PostgreSQL: the load only feeds the legalquery-ai chunk store.
set -euo pipefail
cd "$(dirname "$0")"
ROOT="$(pwd)"
SCRAPER_DIR="${SCRAPER_DIR:-$ROOT/../indiacode-scraper}"
LQAI_DIR="${LQAI_DIR:-$ROOT/../legalquery-ai}"
TRANSLATIONS_TEXT_DIR="${TRANSLATIONS_TEXT_DIR:-$ROOT/../translation-ledger/text}"
HOST="${HOST:-localhost}"
PORT="${PORT:-8800}"
TUNNEL="${TUNNEL:-legalquery-web}"

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
  # Text of the AI translations staged by ../translation-ledger (read only; their PDFs are in ./translations).
  # Translations of Acts not exported yet are kept and attach on a later run. A translation whose folder is gone
  # is deleted from PostgreSQL, so this must be the folder with the JSON files.
  if [ -d "$TRANSLATIONS_TEXT_DIR" ]; then
    (cd "$LQAI_DIR" && .venv/bin/lqai load-translations --dir "$TRANSLATIONS_TEXT_DIR")
  else
    echo "no translation text at $TRANSLATIONS_TEXT_DIR: translation load skipped" >&2
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

# The public URL of PORT on the dev tunnel, e.g. https://9p2m5sgn-8800.inc1.devtunnels.ms ('' if none).
tunnel_url() {
  devtunnel show "$TUNNEL" --json 2>/dev/null | python3 -c '
import json, sys
port = int(sys.argv[1])
try:
    ports = json.load(sys.stdin)["tunnel"].get("ports", [])
except (ValueError, KeyError):
    ports = []
print(next((p.get("portUri", "").rstrip("/") for p in ports if p.get("portNumber") == port), ""))
' "$PORT"
}

tunnel() {
  if ! command -v devtunnel >/dev/null; then
    echo "devtunnel not installed (brew install devtunnel), then: devtunnel user login" >&2
    exit 1
  fi
  local url
  url="$(tunnel_url)"
  if [ -z "$url" ]; then
    echo "Creating dev tunnel $TUNNEL (anonymous access, port $PORT)" >&2
    devtunnel show "$TUNNEL" >/dev/null 2>&1 || devtunnel create "$TUNNEL" --allow-anonymous >/dev/null
    devtunnel port create "$TUNNEL" -p "$PORT" >/dev/null
    url="$(tunnel_url)"
    [ -n "$url" ] || { echo "could not get the URL of tunnel $TUNNEL (devtunnel user login?)" >&2; exit 1; }
  fi
  devtunnel host "$TUNNEL" >/dev/null 2>&1 &
  local pid=$!
  trap 'kill "$pid" 2>/dev/null' EXIT
  echo "Public URL: $url"
  SITE_URL="$url" serve
}

case "${1:-all}" in
  export) export_content && load_db ;;
  load) load_db ;;
  serve) serve ;;
  tunnel) tunnel ;;
  # A failed PostgreSQL load does not keep the site from serving the new export.
  all) export_content && { load_db || echo "warning: PostgreSQL load failed (see above); serving anyway" >&2; } && serve ;;
  *) echo "usage: ./dev.sh [export|load|serve|tunnel]" >&2; exit 2 ;;
esac
