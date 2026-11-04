# Changelog & handoff log

Read *Current state* and *Pending* first. Add an entry at the top of *History* after every change
(date, what changed, checks actually run with their real output).

## Current state (2026-11-03)

- 2026-11-03: content re-exported by the scraper (400 Acts, 19,407 sections, 375 schedules, 8,812 distinct PDFs, all
  from the scraper's file store `data/files`; 0 not downloaded). **No India Code URL anywhere** on the site.

- Project created. Not committed yet (`git init` done, no commits).
- Content is format 2 (Act folders, `docs/CONTENT.md`), last exported 2026-11-02: 360 Acts,
  18,062 sections, 344 schedules, 8,228 distinct PDFs (all hard links to the scraper's `data/`),
  63 linked PDFs not downloaded yet (India Code links), no mismatched files.
- Local server: `./dev.sh serve` → http://localhost:8800 (`HOST=0.0.0.0 ./dev.sh serve` to share it on the LAN).

## Owner decisions

- 2026-11-02: separate project `legalquery-web` (not inside the scraper, not Drupal); plain PHP pages
  from exported JSON, no database in this project; header/footer written once; no QR codes.
- Same URL rules as the LegalQuery Drupal module plan (lowercase slugs, `/page/N`, permanent slugs).
- 2026-11-02: layout and content like India Code; only colours from legalquery.in. This site has its
  own pages only: no legalquery.in menu items, footer links, Login or "Ask a lawyer" button.
- 2026-11-03: **no page per section or schedule**: they are shown only inside the Act's Sections / Schedules
  tabs; old `…/section-2a` and `…/first-schedule` URLs 301 there (with `#slug`). **No schedule PDFs**
  (`a2025-37_shed._l.pdf` …): the schedule text is shown (owner).
- 2026-11-03: **no India Code URLs** anywhere: not in `content/` (the export rewrites/removes them), not in the
  templates. "Source: India Code, Government of India" stays as plain text (owner).
- 2026-11-04: PDFs show one "View" button and the English PDF only; no language buttons for now (owner).
- 2026-11-03: no "Important references" block and no india.gov.in link on the site, so it doesn't look like a
  government-portal clone (owner).
- 2026-11-02: `content/` folders follow the pages: `central/acts/<act>/` with `act.json`, `sections/`,
  `schedules/`, one file per tab, and the Act's PDFs in `pdf/` (content format 2).

## Pending

- Scraper export (not this project): schedule `body_text` is raw PDF text: 364 of 402 start with the PDF page number
  (e.g. "7"), 16 contain the broken India Code watermark ("In / diaC / ode"). Clean it in the export.
- Before all States / the full data set: content format 3 (split `files.json` by sha256 prefix; it is read on every
  PDF request) and jurisdiction-generic code (`central` is hard-coded in Content/Url/Router/Seo). See the scraper's
  CHANGELOG (2026-11-03).
- 7 PDFs now have a new sha256 (the scraper's store copy differs from its old download): their old
  `/files/<old sha256>/…` URLs 404. Only matters if those URLs were shared.

- Real PDF thumbnails (first page) to replace the demo image: needs a thumbnail field from the
  scraper export (+ `docs/CONTENT.md`).
- Scraper export: 24 Acts have a Hindi PDF (file name `H…`) labelled `language: en`, e.g.
  `recycling-of-ships-act-2019` → `h201949.pdf`. The site hides it (one PDF per language), so it can't
  be picked as Hindi until the export labels it `hi`.
- Owner review of the pages in the browser (home redone 2026-11-02).
- Category pages show Acts only, without counts of rules/notifications per Act: that needs tab counts
  in `index.json` (scraper export change + `docs/CONTENT.md`).
- "Browse by ministry" (India Code has it) would need new URLs: owner to decide.
- Push to a GitHub repo (`legalquery-web`) — needs the owner's OK and the repo.
- Production (indiacode.legalquery.in, Apache, live 2026-11-04): `rsync` of `content/` still to do.
- SEO, needs the scraper export: a `content_changed_at` per Act (date the text last really
  changed) for `<lastmod>` in the sitemap and `dateModified`; `last_checked_at` is only the crawl
  date, so it is not used (Google ignores lastmod that changes on every crawl).
- SEO, owner: final `https://` domain in `site_url` before launch, then submit the sitemap in Google
  Search Console / Bing Webmaster Tools. Check the share image `public/assets/img/share.png`.
- Later: full-text search inside sections (today: Act titles/numbers/years and section headings),
  PDF thumbnails, State Acts when the scraper has them.

## History

### 2026-11-04 — Server: indiacode.legalquery.in on Apache
- Apache 2.4 + PHP 8.3-FPM; code in `/var/www/html/indiacode.legalquery.in` (git pull). DocumentRoot is
  `…/public` in both vhosts (`indiacode.legalquery.in.conf` :80 redirect and `…-le-ssl.conf`); before that the
  repo root was the web root: CSS 404 and `.git`, `config.php`, `src/`, `content/` downloadable.
- `config.local.php` on the server: `site_url` = `https://indiacode.legalquery.in`.
- `content/` not uploaded yet (only `central/index.json` etc.), so every Act page 404s: rsync it (README Deploy).
- `dev.sh`: `serve()` had been commented out locally (`./dev.sh serve` → "serve: command not found"); restored.
- Checked (live, curl): `/`, `/assets/css/site.css`, `/assets/js/site.js`, `/favicon.png`, `/robots.txt`,
  `/sitemap.xml`, `/acts/central`, `/acts/central/2026`, `/search?q=act` 200; `/.git/config`, `/config.php`,
  `/config.local.php`, `/content/slugs.json`, `/dev.sh` 404; `/acts/central/<act>` 404 (no content yet).
  Local: `bash -n dev.sh` ok, `PORT=8812 ./dev.sh serve` → 200, `php -l` ok, `php tests/run.php` → 94 checks passed.

### 2026-11-04 — Asset links built from `site_url`
- Owner: CSS/JS/image links use `site_url`. New `View::asset()` (= `absolute()` for files under `public/`);
  `layout.php` (favicons, `site.css`, `site.js`, share image) and `partials/act-pdfs.php` (thumbnail) use it.
- Note: `HOST=0.0.0.0 ./dev.sh serve` from another machine now needs `site_url` set to the LAN URL in
  `config.local.php`, or the CSS loads from `localhost`.
- Server (indiacode.legalquery.in): DocumentRoot must still be `…/public` (both :80 and the SSL vhost), else
  `/assets/…` and page URLs 404 and the repo root (`config.local.php`, `.git`, `content/`) is public.
- Checked: `php -l` on all files ok; `php tests/run.php` → 94 checks passed; local server: home page links
  `http://localhost:8800/assets/css/site.css?v=10`, `…/site.js?v=3`, favicons, og:image; Act page thumbnail
  `http://localhost:8800/assets/img/pdf-thumb-demo.svg`; `site.css` 200.

### 2026-11-04 — `dev.sh`: HOST option to share the dev server on the LAN
- `HOST` (default `localhost`) next to `PORT`; `HOST=0.0.0.0 ./dev.sh serve` binds all interfaces and prints the
  LAN URL (e.g. http://192.168.1.124:8800). README run block updated.
- Checked: `bash -n dev.sh` ok; `HOST=0.0.0.0 PORT=8899 ./dev.sh serve` → `curl http://192.168.1.124:8899/` 200,
  `curl http://localhost:8899/` 200.

### 2026-11-04 — PDFs: one "View" button, English only, no language buttons (owner)
- Owner: no "Bilingual" / language buttons for now; buttons say "View"; show English only.
- New `english_file()` (`src/helpers.php`) picks the one PDF shown: en, then bi, then other, then hi (69 linked
  documents have only a Hindi PDF; they keep their "View" rather than losing the PDF). Not-archived files skipped.
- `partials/file-links.php` (Rules, Notifications, … tabs and section docs): one "View" (+ ⤓ when not compact), no
  language label (was "Bilingual" on 2,941 documents, "English"/"हिन्दी" on 16).
- `partials/act-pdfs.php`: the Act header shows one English PDF; the English/हिन्दी switch (markup, `.pdf-langs`
  CSS, `site.js` block) is removed. It is in git history (bca7d3a) for when Hindi comes back.
  `language_label()` removed (unused). CSS `?v=10`, JS `?v=3`.
- Checks run: `php -l` clean; `php tests/run.php` → **94 checks passed** (new `english pdf` tests); GST Act
  Sections/Details/Rules/Notifications and Private Security Agencies Act Rules: 0 "Bilingual"/"English"/"हिन्दी"
  labels, every button "View", the en+hi Model Rules 2020 shows only the English PDF, Act header shows `a2005-29.pdf`;
  browser: no console errors, no horizontal scroll.

### 2026-11-03 — Schedules show their text ("No PDF is published" gone)
- Cause: the scraper stopped exporting schedule PDFs (same day, owner), but `partials/schedule-item.php` still showed
  only a PDF panel + metadata and hid the text, so all 402 schedules said "No PDF is published for this schedule."
- `partials/provision-body.php`: schedules now show `body_text` like sections (tidied for display by the new
  `tidy_text()` in `src/helpers.php`: trailing spaces cut, blank-line runs collapsed; words untouched).
  `partials/schedule-item.php` and its `.item-*` CSS removed; CSS `?v=9`.
- Checks run: `php -l` clean; `php tests/run.php` → **92 checks passed** (new `tidy text`); `/schedules` of three Acts
  → 200, no "No PDF" text; Central Armed Police Forces Act schedules open with text in the browser, no console errors,
  no horizontal scroll.

### 2026-11-03 — Breadcrumbs checked on every page; Act tabs fixed
- Checked the visible trail and the `BreadcrumbList` JSON-LD on home, Central Acts (+ page 2, year, `?search=`),
  category pages (+ page 2), search (+ page 2), the Act's Sections tab (+ page 2), Schedules, Act Details,
  linked-document tabs and 404s. All were right except the Act tabs.
- Bug: Schedules / Act Details / Rules / Notifications / … showed `Home › Central Acts › <Act>` (same as the Sections
  tab: Act not a link, tab not named). `templates/partials/act-head.php` now gives
  `Home › Central Acts › <Act (link)> › <Tab>` on every tab but Sections; JSON-LD follows (same partial).
- Unchanged on purpose: paged lists keep the page-1 trail; error pages and home have no breadcrumb; old section URLs
  301 to the Act page, so they have none.
- Checks run: `php -l` clean; `php tests/run.php` → **91 checks passed**; curl of each page type above (trail + JSON-LD
  as expected); Act Details of a long Hindi-title Act at 375 px: trail wraps, no horizontal scroll, no console errors.

### 2026-11-03 — No section/schedule pages; no schedule PDFs (owner)
- `src/Url.php`: `Url::provision($act, $slug, $kind, $page)` now gives the place inside the Act page:
  `/acts/central/{act}[/page/N]#section-2a` or `/acts/central/{act}/schedules#first-schedule`.
- `ActController::provision`: the old single-page URLs answer **301** to that place (404 for an unknown slug);
  `templates/provision.php` and `Seo::provision` are removed. `Controller::provisionUrl()` works out page N
  (`per_page`).
- `partials/provision-list.php`: the "Open Section … on its own page" link is removed. Search results link to the
  anchor (`SearchController` adds `url` for the hits on the shown page only). The sitemap no longer lists sections or
  schedules.
- Schedule PDFs: no longer exported by the scraper (all 387 schedules have their text); `docs/CONTENT.md` updated.
- Content re-exported the same day: 440 Acts; 0 `pdf/schedules/` files left (the 382 schedule-PDF hashes were dropped
  from `files.json`, so their `/files/<sha256>/…` URLs now 404, as intended).
- Docs: `docs/URLS.md` and `docs/CONTENT.md`.
- Checks: `php -l` on all PHP files OK; `php tests/run.php` **91 checks passed** (3 new for the anchor URLs).
  Live on :8800, on the VB-G RAM G Act, 2025 page: `/section-1` → 301 `…#section-1`; `/section-25` → 301
  `…/page/2#section-25`; `/first-schedule` → 301 `…/schedules#first-schedule`; `/section-999` → 404. The Act page has
  0 "own page" links and 20 section anchors; the sitemap has 0 section URLs; search results link to `#section-…`.
  In the browser, the old `/section-25` URL lands on page 2 with Section 25 open at the top of the view.

### 2026-11-03 — Home: "Important references" removed, no india.gov.in link (owner)
- `templates/home.php`: the "Important references" section (ministry, portal and court links, incl. India.gov.in) is
  gone, so the site doesn't look like a copy of a government portal. Its CSS (`.references`, `.ref-grid`) removed;
  CSS `?v=8`. Comments in `HomeController`/footer and `docs/URLS.md` updated. Footer "Official sources" unchanged
  (Legislative Department, e-Gazette). No URL changes.
- Checks run: `php -l` clean; `php tests/run.php` → **89 checks passed**; home in the browser: no `.references`,
  no `india.gov.in` in the HTML, no horizontal scroll, no console errors. `grep india.gov.in` over templates/src/
  public/docs/content → 0.

### 2026-11-03 — No India Code URLs on the site (owner)
- Templates: footer "Official sources" and home "Related portals" lose the India Code link; the footer source note
  and the Act Details "Source" row are plain text ("India Code, Government of India"). The PDF buttons/thumbnails for
  files not archived yet (`remote_url`, "opens India Code") are removed from `file-links.php`, `act-pdfs.php` and
  `schedule-item.php`: such a file is simply not shown.
- `src/Seo.php`: no `isBasedOn` India Code URL in the Act JSON-LD. `src/Url.php`: `indiaCode()` removed.
- Content: the scraper's export now strips India Code URLs (`docs/CONTENT.md` updated with it); re-exported the same day.
- Checks: `php -l` on every PHP file OK; `php tests/run.php` 89 checks passed; 229 rendered pages (home, lists, 40
  random Acts with details/tabs/sections/schedules, search, sitemap) all 200 with **0** `indiacode.gov.in`/`.nic.in`.

### 2026-11-02 — schedules shown like India Code item pages (owner)

- Owner: schedule text started with "7" (PDF page number) and the PDF sat above a long block of
  broken text. All 344 exported schedules have only `body_text` extracted from the PDF (no
  `body_html`), so the text is no longer shown. A schedule now shows, on the Schedules tab and on its
  own page, two panels like indiacode.gov.in/items/<uuid> (no QR code): **Document** (PDF thumbnail
  — demo image —, file name, size, View / Download) and **Item Metadata** (Published date = Act
  enactment date, Act Name, Collection, Ministry Name, Jurisdiction, Title).
- New `templates/partials/schedule-item.php`; `provision-body.php` uses it for schedules; styles
  `.item-*` in `site.css` (one column under 760px); removed unused `.sched-actions`.
- Checks run: `php tests/run.php` → 89 checks passed; `php -l` on all PHP files → no errors;
  browser: `/acts/central/central-armed-police-forces-general-administration-act-2026/schedules`
  (expanded First Schedule) and `/first-schedule` at 375px (no horizontal scroll, 375 = 375).

### 2026-11-02 — re-export after the scraper's case-clash fix

- The scraper (own session) now keeps file names unique ignoring case and re-downloaded
  `a2020-14.pdf` for `national-commission-for-indian-system-of-medicine-act-2020`. Re-ran
  `./dev.sh export`: `files_mismatched: []`, 39 name clashes renamed, 8,228 distinct PDFs, 63 remote.
- Checks run: that Act's `act.json` has both PDFs as local files (`pdf/a2020-14.pdf`,
  `pdf/A2020-14-2.pdf`); both `/files/…` URLs → 200 with the right sizes (368,835 and 462,545 bytes);
  `php tests/run.php` → 89 passed. No site code changed.

### 2026-11-02 — content/ format 2: folders like the pages (owner)

- Owner: `content/` structured like the site — jurisdiction folder (`central/`), one folder per Act,
  one file per section/schedule/tab, and the Act's PDFs inside the Act folder. Contract written in
  `docs/CONTENT.md` (format 2); export change handed to a session in the scraper project.
- `Content`: reads `central/index.json`, `central/search.json`, `central/acts/<slug>/act.json`;
  new `provision()` (one section/schedule file) and `tab()` (one tab file); `pdfPath()` goes through
  `files.json` (paths must stay inside `content/`); `ready()` needs `manifest.json` format 2.
- `ActController`: Sections page loads only the 20 sections it shows; Schedules loads each schedule
  file; tabs read `<tab>.json`; section pages read their own file. Templates use `linked` (documents
  of a section) instead of looking up `act.items`. No URL changed; PDF URLs stay
  `/files/{sha256}/{name}.pdf`.
- Test fixture converted to format 2.
- Found while converting: PDF names in one Act differ only by case (`A2020-14.pdf` / `a2020-14.pdf`),
  which clash on macOS; the contract now compares names case-insensitively (36 renames in 360 Acts).
- Checks run: `php tests/run.php` → **89 checks passed** (9 new: provision/tab files, act.json has no
  text, `files.json` path traversal refused, format 1 not ready); `php -l` clean. On a format-2 copy of
  today's content (converter in the session scratchpad): home, lists, `/rules`, Companies Act 2013 page
  1–2, Schedules, Details, Rules page 1–2, section-2, first-schedule → 200, section-9999 → 404; page 1
  shows 20 section texts and 14 "docs" badges (same as format 1), section-2 lists its 14 documents,
  the Act and rules PDFs download (200, 5.4 MB), sitemap 20,014 URLs; Schedules tab in the browser,
  no console errors.
- Then the scraper's new export (format 2, its own session) ran into `content/` (`./dev.sh export`,
  11 s; old format-1 files removed by it) and the temporary `config.local.php` was deleted. On the real
  content: the same pages → 200 (section-9999 → 404), 20 section texts and 14 badges on Companies Act
  page 1, Act / rules / schedule PDFs → 200, `files.json` 8,102 entries with 0 missing files, sitemap
  20,014 URLs, `php tests/run.php` → 89 passed. Contract notes added from the export: section-only
  documents' PDFs go in their collection's tab folder (or `other/`); `files.json` uses the first Act
  by `record_id`.

### 2026-11-02 — SEO

- `<head>` (`layout.php`): Open Graph + X card tags (`og:type` article on Act/section pages),
  `og:image` = new `share_image` config (`public/assets/img/share.png`, 1200×630), `lang="en-IN"`,
  `theme-color`, favicon (SVG + 48 px PNG + `/favicon.ico`) and apple-touch-icon (made with GD).
- Structured data (`src/Seo.php`, JSON-LD): `WebSite` with search box on home; `Legislation` for
  Acts (identifier "Act No. 18 of 2026", dates, ministry, in force, PDFs, India Code source) on the
  Act page and Act Details; sections/schedules as `Legislation` `isPartOf` the Act;
  `BreadcrumbList` from the crumbs partial on every page with crumbs.
- Unique titles/descriptions: Act page title "… – Sections and full text"; paged lists add
  "Page N." to the description; Schedules and Act Details have their own descriptions (summary or
  long title); section descriptions are cut at a word boundary, with a fallback when the body is
  empty.
- `ETag` + 304 for HTML pages (saves crawl budget); PDFs send `Link: rel="canonical"` (the
  `?download=1` copy points to the plain URL); sitemap becomes an index of `/sitemap-N.xml` above
  50,000 URLs; `.htaccess` gzip + 1-year cache for versioned CSS/JS; README nginx config the same,
  plus deploy step for Search Console.
- No URL changed. No `<lastmod>` yet (see Pending).
- Checks run: `php tests/run.php` → **80 checks passed** (10 new); `php -l` clean; curl: home, lists,
  Act, details, section, `/rules`, search → 200, `/nope` and `/sitemap-1.xml` → 404, favicons and
  share image → 200; JSON-LD and OG tags present; `If-None-Match` with the ETag → 304; PDF
  `?download=1` has the canonical `Link` header; sitemap still 20,014 URLs; Act page in the browser
  unchanged, no console errors.

### 2026-11-02 — PDF thumbnail (demo) and English/हिन्दी switch

- `partials/act-pdfs.php`: the Act's PDF as a thumbnail with its file name under it (India Code
  style). Thumbnail is a demo image `public/assets/img/pdf-thumb-demo.svg` for now (owner will
  replace with real thumbnails; change `$thumb`). Still no QR code.
- One PDF per language: English (or bilingual/unknown) shows; the Hindi PDF is hidden until the
  reader clicks हिन्दी in the English | हिन्दी switch (`site.js`; switch only when the Act has both).
  Without JavaScript only the English PDF shows. Later files of the same language are skipped.
  Download link and size moved into the thumbnail's tooltip. CSS `?v=7`, JS `?v=2`.
- Checks run: `php tests/run.php` → **70 checks passed**; `php -l` clean; curl of 4 Acts (English
  only; English + Hindi; English + Hindi labelled `en`; Hindi + `unknown`) shows the right files;
  in the browser at 1400 px: clicking हिन्दी shows `H2014-06H.pdf` and hides `a2014-6.pdf`; at 375 px no
  horizontal scroll; no console errors.

### 2026-11-02 — Act page like India Code (owner's screenshot)

- `act-head.php`: breadcrumb, large title, "Administered Ministry" box, the Act's PDFs as plain links
  on the right (`partials/act-pdfs.php`: name, language, size, Download). No PDF preview and no QR
  code (owner). Removed from the header: chips, long title, facts grid, "Summary & history" and
  "India Code" buttons (Act number, dates, summary are on Act Details; India Code link added there as
  "Source"). `partials/pdf-cards.php` deleted.
- Tabs: India Code style (grey active tab, gold underline, no counts).
- Sections / Schedules: toolbar with expand/collapse icon buttons, "Showing 1 to 10 of 10 results" and
  the pager on top (shown even for one page: `pager` partial `always`), table look with a
  "Section"/"Schedule" header row, navy badges, chevron button. Rows still expand in place. CSS `?v=6`.
- Checks run: `php tests/run.php` → **70 checks passed**; `php -l` clean; Act, page 2, Schedules,
  Rules, Details and section pages → 200 with no PHP warnings; in the browser at 1400 px and 375 px:
  matches the screenshot layout, "Expand all" opens 10 of 10, no horizontal scroll, no console errors.

### 2026-11-02 — Browse by category (owner OK)

- New URLs `/rules`, `/notifications`, `/regulations`, `/orders`, `/ordinances`, `/statutes`, `/circulars`,
  `/appendices`, `/amendments` (+ `/page/N`): Central Acts that have that kind of document, newest
  first; the button opens the Act's tab. `CategoryController`, `templates/category.php`,
  `Url::category()`, `Content::categories()`; added to the sitemap and `docs/URLS.md`.
- Home: "Browse by category" cards (Acts + each category with its number of Acts). CSS `?v=4`.
- Checks run: `php tests/run.php` → **70 checks passed** (5 new: routes, `Url::category`,
  `Content::categories`); `php -l` clean; curl: `/rules`, `/notifications`, `/amendments`,
  `/appendices`, `/rules/page/2` → 200, `/rules/page/1` and `/Rules` → 301 `/rules`, `/rules/page/99` and
  `/whatever` → 404; sitemap 20,014 URLs (+34 category pages); all 20 buttons on `/rules` open the
  Rules tab; pages seen in the browser at 1280 px and 375 px (no horizontal scroll, no console errors).

### 2026-11-02 — India Code style home, no legalquery.in pages

- Header menu is now Home / Central Acts / Search (`templates/partials/header.php`); Login removed.
- Footer: this site's links, official sources (India Code, Legislative Department, e-Gazette) and the
  source note; legalquery.in Services/Company/Legal columns removed.
- Act header: "Ask a lawyer about this Act" (legalquery.in/query/add) removed; `main_site_url`
  removed from `config.php`.
- Home rebuilt like India Code's home: blue search band with hint, four count cards (Acts, sections,
  schedules, years), "Recent Central Acts" and "Browse by year" panels, "Important references"
  (ministry, portal and court links). No new URLs. CSS `?v=2`.
- Checks run: `php tests/run.php` → **65 checks passed**; `php -l` clean; home and Act page contain no
  `legalquery.in` link (curl + grep → 0); home checked in the browser at 1280 px and ~466 px (no
  horizontal scroll, no console errors).

### 2026-11-02 — project created

- `public/index.php` front controller, `.htaccess`, `router.php` (php -S), `config.php`.
- `src/`: `App` (canonical-URL 301s, errors), `Router`, `Url`, `Content` (JSON reader), `Pager`,
  `Search`, `View`; controllers Home, ActList, Act (sections, schedules, details, tabs, single
  provision), Search, File (PDF by checksum), Seo (sitemap, robots).
- `templates/layout.php` + partials (header, footer, crumbs, pager, PDF cards, file links, Act
  header, section list/body, Act rows) and page templates; `public/assets/css/site.css` from the
  scraper's LegalQuery mockups; small `site.js` (menu, expand/collapse, `#section-N`).
- `tests/run.php` + `tests/fixtures/content` (one Act from the real export).
- Checks run: `php -l` on every PHP file (clean); `php tests/run.php` → **65 checks passed**;
  curl of 24 URLs (200 / 301 / 302 / 404 as designed, e.g. `/acts/central/page/1` → 301,
  `/page/28` of a 27-page Act → 404); PDF download headers + SHA-256 matched; sitemap 19,980 URLs;
  pages rendered in the browser (home, list, Act, tab, section, mobile 375 px with no horizontal
  scroll, no console errors).
