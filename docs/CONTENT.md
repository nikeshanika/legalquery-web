# content/ format (scraper → legalquery-web)

`content/` is written **only** by the scraper's export command and read **only** by this site:

```bash
cd /Users/user/Nikesh/indiacode-scraper
.venv/bin/python indiacode_scraper.py export-web --out ../legalquery-web/content
```

The export lives in `indiacode-scraper/src/web_export.py`. A field used by the site must not be
renamed or removed there without updating this file and the templates in the same change.
Format version: `2` (the `format` field in `manifest.json`, `central/index.json` and every `act.json`).
Format 1 (one JSON file per Act, PDFs only under `files/<ab>/<sha256>.pdf`) is no longer read.

## What gets exported

- Acts with `crawl_status = 'completed'` whose own PDFs (Act + schedule PDFs) are all downloaded.
  Schedule PDFs are **not exported** (owner, 2026-11-03): every schedule's text is exported and the Act's PDF
  contains the schedules.
  Anything half-scraped is never exported.
- The scraper DB is opened read-only, in one snapshot.
- **No India Code URL anywhere in `content/`** (owner, 2026-11-03). India Code Act links in that HTML point to
  this site's Act page (`/acts/central/<slug>`); other India Code links keep only their text; the domain
  written as text is removed.
- India Code HTML (section bodies, footnotes, summary, history) is sanitised by the export
  (scripts, styles, iframes, `on*` handlers and `javascript:` URLs removed). Templates print
  `*_html` fields as they are; everything else is escaped with `e()`.
- An Act that is no longer exportable is removed from `central/index.json` and its folder deleted.
  Its slug stays in `slugs.json`, so it gets the same URL if it comes back.

## Layout

The folders follow the site's pages: one folder per jurisdiction (`central/`; State Acts later
under `state/<state-slug>/` with the same layout), one folder per Act, and inside it one file per
section, schedule and tab, with the Act's PDFs next to them.

```
content/
  manifest.json                       format, exported_at, counts
  slugs.json                          in git: published slugs never change
  files.json                          sha256 -> PDF path (for /files/{sha256}/{name}.pdf)
  central/
    index.json                        /acts/central, /acts/central/{year}, /rules, home
    search.json                       /search
    acts/
      companies-act-2013/             /acts/central/companies-act-2013
        act.json                      Act page header, Act Details, section/schedule lists, tab counts
        sections/
          section-1.json              /acts/central/companies-act-2013/section-1
          section-2a.json
        schedules/
          first-schedule.json         /acts/central/companies-act-2013/first-schedule
        rules.json                    /acts/central/companies-act-2013/rules (one file per non-empty tab)
        notifications.json
        pdf/
          A2013-18.pdf                the Act's own PDFs (act.json files[])
          H2013-18.pdf
          rules/<file>.pdf            PDFs of the documents in rules.json (same for every tab; a
                                      document linked only from a section goes in the folder of
                                      its collection's tab, or other/)
```

## Files

| File | Content |
|---|---|
| `manifest.json` | `format` (2), `exported_at` (UTC), `acts`, `sections`, `schedules`, `files` (distinct PDFs) |
| `slugs.json` | `{acts: {record_id: slug}, sections: {section_uuid: slug}}`. **In git**: published URLs never change |
| `files.json` | `{sha256: path}`, path relative to `content/` of one copy of that PDF (e.g. `central/acts/x/pdf/A2026-18.pdf`); when several Acts hold it, the copy in the first Act by `record_id`. Every exported PDF is listed |
| `central/index.json` | `{format, acts: [...]}`, newest enactment first. Each entry: `slug`, `record_id`, `jurisdiction` (`central`), `title`, `act_number` (text, leading zeros kept), `year`, `enactment_date` (ISO or ''), `ministry`, `sections` (count), `schedules` (count), `tabs` (names of non-empty tabs, in the order of the site's tabs), `last_checked_at` |
| `central/search.json` | `[[act slug, provision slug, label, heading], ...]` for every section and schedule |
| `central/acts/<slug>/act.json` | The Act without section/schedule text (below) |
| `…/sections/<slug>.json`, `…/schedules/<slug>.json` | One provision with its text (below) |
| `…/<tab>.json` | One linked-document tab (below). Tabs: `rules`, `notifications`, `regulations`, `orders`, `ordinances`, `statutes`, `circulars`, `appendices`, `amendments`. Only non-empty tabs have a file |
| `…/pdf/…` | The Act's PDFs (below) |

## act.json

| Field | Notes |
|---|---|
| `format` | 2 |
| `record_id`, `slug`, `jurisdiction`, `title`, `long_title`, `act_number`, `year` | |
| `enactment_date`, `enforcement_date` | ISO date or '' |
| `ministry`, `department` | |
| `repeal_status` | `not_repealed` or '' (not crawl state) |
| `summary_html`, `history_html` | sanitised HTML or '' |
| `last_checked_at` | UTC ISO-8601 |
| `links_complete` | false while Rules/Notifications/... are still being collected |
| `files` | the Act's PDFs (see *File entry*), English first |
| `sections[]` | in Act order: `uuid`, `slug` (`section-2a`), `number`, `heading`, `docs` (number of linked documents) |
| `schedules[]` | in Act order: `uuid`, `slug` (`first-schedule`), `heading`, `docs` |
| `tabs` | `{rules: 12, notifications: 3, ...}` number of documents, only non-empty tabs |

## Section / schedule file

`sections/<slug>.json`: `uuid`, `slug`, `number`, `heading`, `body_html`, `footnote_html`, `linked`.
`schedules/<slug>.json`: `uuid`, `slug`, `heading`, `body_html`, `body_text` (plain text when there
is no HTML), `linked`. (No `files`: schedule PDFs are not exported since 2026-11-03.)
Since 2026-11-04 `body_text` is clean text: no PDF page numbers, no "India Code" watermark or link
lines, no trailing spaces, at most one blank line in a row; footnote marks sit before their passage
(`1[THE SECOND SCHEDULE`). It can be `""` (shown as "No text is published").

The section and schedule files feed the Act page's Sections and Schedules tabs; there is no page per
section or schedule (owner, 2026-11-03; old URLs 301 to the tab, see `URLS.md`).

`linked` is the list of documents linked to that provision, each a *Document* (below), newest first.

## Tab file

`<tab>.json`: `{tab, collection, items: [Document, ...]}`, newest first. `collection` is the India
Code collection (`RULE`, `NOTIFICATION`, `REGULATION`, `ORDER`, `ORDINANCE`, `STATUTE`, `CIRCULAR`,
`APPENDIX`, `ACT_AMENDMENT`).

Document: `{uuid, collection, title, date, state, files}`.

## File entry

`{filename, language: en|hi|bi|unknown, size, sha256, path}` when the PDF was exported; `path` is
relative to `content/` (e.g. `central/acts/x/pdf/rules/G-123.pdf`). Or `{filename, language, size}`
when the scraper has not downloaded it yet: no link. `remote_url` is never an India Code URL (since
2026-11-03), so in practice it is absent.

PDFs in an Act's `pdf/` folder:

- Name: the India Code file name made safe (`A-Za-z0-9._-`, ends in `.pdf`); a second, different
  file with the same name in the same folder gets `-2`, `-3` before `.pdf`. Names are compared
  without case (`A2020-14.pdf` and `a2020-14.pdf` clash: macOS disks ignore case).
- The same PDF (same sha256) is stored once per Act, at the first place it is used; every entry
  for it in that Act has that `path`.
- A PDF used by several Acts is in each Act's folder. They are hard links when on the same disk as
  the scraper's `data/`, so they take the space once (`rsync -H` keeps that on the server).
- The site serves PDFs by checksum only (`/files/<sha256>/<name>.pdf`, found through `files.json`);
  these URLs did not change with format 2.

## Slugs

Same rules as the LegalQuery Drupal module:

- Act: short title without a leading "The", ASCII `a-z0-9-` (Hindi and other scripts dropped,
  accents removed): `The Companies Act, 2013` → `companies-act-2013`. Taken already → append
  `-act-<number>`, then `-2`, `-3`. `page` and `search` are reserved.
- Section `2A` → `section-2a`; a repeated number gets `-<order>`; no number → `section-order-<n>`.
- Schedules by position: `first-schedule` … `twentieth-schedule`, then `schedule-21`.
- Once in `slugs.json`, a slug is never changed, even if the title changes.
