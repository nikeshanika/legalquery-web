# legalquery-web

The LegalQuery **Laws** site: Central Acts of India, section by section, with schedules, rules,
notifications and official PDFs. Plain PHP, **no database, no framework, no Composer**. Pages are
rendered from JSON files that the [indiacode-scraper](../indiacode-scraper) project exports.

```
indiacode-scraper (SQLite + PDFs)  --export-web-->  legalquery-web/content/ (JSON + PDFs)  -->  PHP pages
```

## Run locally

Needs PHP 8.1+ (`brew install php`) and the scraper project next to this one.

```bash
./dev.sh            # export from the scraper, load PostgreSQL, then serve http://localhost:8800
./dev.sh export     # refresh content/, then load PostgreSQL
./dev.sh load       # only the PostgreSQL load (after a translation batch in ../indiacode)
./dev.sh serve      # only serve
HOST=0.0.0.0 ./dev.sh serve   # also reachable from the LAN, e.g. http://192.168.1.124:8800
./dev.sh tunnel     # serve on the public dev tunnel legalquery-web (same URL every day, = SITE_URL)
```

"Load PostgreSQL" = `lqai load` (content/ → chunks) and `lqai load-translations` (the text of the AI translations
in `../translation-ledger/text/`; their PDFs, the only part this site uses, are in `translations/`, both staged by
`../translation-ledger`) of the separate `../legalquery-ai` project. Only changed Acts and translations are
written. Skipped with a note when `../legalquery-ai` is not set up; `LQAI_SKIP_LOAD=1` skips it. The site
itself never reads PostgreSQL.

Example page: http://localhost:8800/acts/central/kerala-alteration-of-name-act-2026

Scrape and refresh the site (in the scraper project; small limited crawls only):

```bash
cd ../indiacode-scraper
scripts/archive_next_pages.sh 1
cd ../legalquery-web && ./dev.sh export
```

## Tests

```bash
php tests/run.php
```

Offline, against `tests/fixtures/` and small temporary content (incl. download pages end to end).

After an export, audit every PDF the site offers against the real `content/` (file, links, titles,
language, back links; about a minute):

```bash
php -d memory_limit=4G tests/audit-pdfs.php
```

`--hash` also checks each file's sha256 (reads every byte), `--text=N` the language of N PDFs against
their text (needs `pdftotext`), `--csv=report.csv` writes every problem, `--only=central` one jurisdiction.

## Layout

| Path | What |
|---|---|
| `public/` | Web root: `index.php` (front controller), `.htaccess`, `assets/` |
| `router.php` | Router for `php -S` (local) |
| `config.php` | Settings; override in `config.local.php` (not in git) |
| `src/` | `App` (request), `Router`, `Url`, `Content` (reads JSON), `Pager`, `Search`, `View`, `Controller/` |
| `templates/` | `layout.php` (the one `<head>`, header and footer) + page templates; `partials/` (header, footer, pager, PDF cards, …) |
| `pages/` | Text of the policy pages (`privacy-policy.html` …; in git): plain HTML shown by `PageController` at `/<slug>` |
| `content/` | Export output (not in git, except `slugs.json`): `files/<ab>.json`, `<jurisdiction>/index.json`, then one folder per Act (`central/acts/<slug>/`: `act.json`, `sections/`, `schedules/`, one file per tab, `pdf/`). Format: [docs/CONTENT.md](docs/CONTENT.md) |
| `docs/URLS.md` | Every URL and the URL rules |

## Deploy

1. Copy the code (`git pull`) to the server; point the web root at `public/`.
2. Create `config.local.php`: `<?php return ['site_url' => 'https://your-domain'];`
3. Copy the content: `rsync -avH --delete content/ user@server:/path/legalquery-web/content/`
   (`-H` keeps a PDF that several Acts share as one file on the server).
4. Apache: `public/.htaccess` does the routing. Nginx:

```nginx
root /path/legalquery-web/public;
# No bot gets a PDF (same as public/.htaccess).
set $is_bot 0;
if ($http_user_agent ~* "(bot|crawl|spider|slurp|archiver|facebookexternalhit|curl|wget|python|scrapy|httpclient|libwww|java/|go-http|okhttp|axios|node-fetch|headless|phantomjs|puppeteer|playwright|selenium)|^-?$") { set $is_bot 1; }
location ~* (^/pdf$|^/files/|\.pdf$) { if ($is_bot) { return 403; } rewrite ^ /index.php last; }
location / { try_files $uri /index.php$is_args$args; }
location = /index.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/index.php; fastcgi_pass unix:/run/php/php-fpm.sock; }
gzip on; gzip_types text/css application/javascript application/xml text/xml text/plain image/svg+xml;
location ~* \.(css|js)$ { add_header Cache-Control "public, max-age=31536000, immutable"; }
location ~* \.(png|svg|ico)$ { add_header Cache-Control "public, max-age=604800"; }
```

5. Set `site_url` to the final `https://` domain **before** search engines see the site (canonical
   links, Open Graph, structured data and the sitemap are built from it), then submit
   `https://your-domain/sitemap.xml` in Google Search Console and Bing Webmaster Tools.
