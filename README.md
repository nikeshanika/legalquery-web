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
./dev.sh            # export from the scraper, then serve http://localhost:8800
./dev.sh export     # only refresh content/
./dev.sh serve      # only serve
HOST=0.0.0.0 ./dev.sh serve   # also reachable from the LAN, e.g. http://192.168.1.124:8800
```

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

## Layout

| Path | What |
|---|---|
| `public/` | Web root: `index.php` (front controller), `.htaccess`, `assets/` |
| `router.php` | Router for `php -S` (local) |
| `config.php` | Settings; override in `config.local.php` (not in git) |
| `src/` | `App` (request), `Router`, `Url`, `Content` (reads JSON), `Pager`, `Search`, `View`, `Controller/` |
| `templates/` | `layout.php` (the one `<head>`, header and footer) + page templates; `partials/` (header, footer, pager, PDF cards, …) |
| `content/` | Export output (not in git, except `slugs.json`): `central/index.json`, then one folder per Act (`central/acts/<slug>/`: `act.json`, `sections/`, `schedules/`, one file per tab, `pdf/`). Format: [docs/CONTENT.md](docs/CONTENT.md) |
| `docs/URLS.md` | Every URL and the URL rules |

## Deploy

1. Copy the code (`git pull`) to the server; point the web root at `public/`.
2. Create `config.local.php`: `<?php return ['site_url' => 'https://your-domain'];`
3. Copy the content: `rsync -avH --delete content/ user@server:/path/legalquery-web/content/`
   (`-H` keeps a PDF that several Acts share as one file on the server).
4. Apache: `public/.htaccess` does the routing. Nginx:

```nginx
root /path/legalquery-web/public;
location / { try_files $uri /index.php$is_args$args; }
location = /index.php { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $document_root/index.php; fastcgi_pass unix:/run/php/php-fpm.sock; }
gzip on; gzip_types text/css application/javascript application/xml text/xml text/plain image/svg+xml;
location ~* \.(css|js)$ { add_header Cache-Control "public, max-age=31536000, immutable"; }
location ~* \.(png|svg|ico)$ { add_header Cache-Control "public, max-age=604800"; }
```

5. Set `site_url` to the final `https://` domain **before** search engines see the site (canonical
   links, Open Graph, structured data and the sitemap are built from it), then submit
   `https://your-domain/sitemap.xml` in Google Search Console and Bing Webmaster Tools.
