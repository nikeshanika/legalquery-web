# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this project is

**legalquery-web** — the LegalQuery Laws site (Central Acts, sections, schedules, rules,
notifications, PDFs). Plain PHP 8.1+, **no database, no framework, no Composer**. It renders pages
from `content/`, which the scraper project writes.

Not to be confused with `/Users/user/Nikesh/legalquery` (the Drupal site on port 8743 and its
`ag_indiacode` module). That project is separate; don't change it from here.

## Read first

0. [CHANGELOG.md](CHANGELOG.md) — state, pending work, owner decisions. **Update it after every change.**
1. [README.md](README.md) — run, test, layout, deploy.
2. [docs/CONTENT.md](docs/CONTENT.md) — the `content/` format (contract with the scraper).
3. [docs/URLS.md](docs/URLS.md) — every URL and the URL rules.

## Data source

- `/Users/user/Nikesh/indiacode-scraper` scrapes India Code into SQLite + PDFs and exports
  `content/` (format 2: `central/` → one folder per Act with `act.json`, `sections/`, `schedules/`,
  tab files and `pdf/`) with `python indiacode_scraper.py export-web --out ../legalquery-web/content`
  (code: `src/web_export.py` there). Run it with `./dev.sh export`.
- **Never edit `content/` by hand** and never write to the scraper project from here. If the site
  needs a new field, change the export in the scraper project and `docs/CONTENT.md` together.
- Only completed Acts with all their PDFs are exported. Never show half-scraped data.
- The scraper's own rules (polite crawling, `--max-records ≤ 20`, owner OK before scaling up) apply
  whenever you run it.

## Rules

1. **URLs never change once published.** Slugs come from `content/slugs.json` (kept in git). All URLs
   are built in `src/Url.php`; follow `docs/URLS.md` (lowercase, no IDs, `/page/N`, 301s).
2. **One header, one footer.** Shared markup lives in `templates/layout.php` and `templates/partials/`;
   page templates hold only their own content. Styles go in `public/assets/css/site.css`.
3. **Escape everything** with `e()`. The only exception is `*_html` fields from the export, which the
   scraper already sanitised.
4. PDFs are served by checksum only (`/files/{sha256}/{name}.pdf`); the name part is never a path.
5. Page layout and content follow India Code (indiacode.gov.in); only the **colours** come from
   legalquery.in. The site is separate: no links to legalquery.in pages (menu, footer, "Ask a lawyer",
   Login). Show "Source: India Code, Government of India" **as plain text**; no government emblem or
   India Code logo. **No India Code URL anywhere** (pages, JSON-LD, content; owner 2026-11-03).
   No QR codes (owner, 2026-11-02).
6. Keep Devanagari intact: UTF-8 everywhere.
7. Don't commit `content/` (except `slugs.json`) or `config.local.php`.

## Commands

```bash
./dev.sh               # export + serve http://localhost:8800
php tests/run.php      # offline tests
for f in $(find src templates public -name '*.php') router.php config.php; do php -l "$f"; done
```
