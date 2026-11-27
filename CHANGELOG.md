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

- 2026-11-25 — Design "L" is the final look: design "W" with a navy header and footer, a gold rule and a gold laurel logo.
- 2026-11-25 — New look, design "W" from the design canvas: plain reference-site style (white, grey rules, blue
  links, serif headings, system fonts, no decoration). Replaces "only colours from legalquery.in" (owner).
- 2026-11-16 — Show the 21 `partial` Acts (no section or schedule text on India Code) with what exists and the PDF (owner).
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
- 2026-11-19: readable URLs for an Act's own PDFs: `/acts/central/{act}.pdf`, `{act}-hindi.pdf` … instead of
  `/files/{sha256}/A2026-18.pdf`; only the URL changes, not the files (owner). PDF question (`pdf_captcha`) stays on for now.
- 2026-11-18: PDF in several languages → no direct language buttons; clicking the PDF (thumbnail or "View") opens a
  popup beside it with one button per language (English, हिन्दी, English + हिन्दी …); the one clicked opens (owner;
  replaces 2026-11-04 "one View button, English PDF only"). One PDF → opens directly as before.
- 2026-11-03: no "Important references" block and no india.gov.in link on the site, so it doesn't look like a
  government-portal clone (owner).
- 2026-11-02: `content/` folders follow the pages: `central/acts/<act>/` with `act.json`, `sections/`,
  `schedules/`, one file per tab, and the Act's PDFs in `pdf/` (content format 2).

## Pending

- SEO, scraper export (found 2026-11-19): (1) some Acts have **another Act's summary** (e.g.
  `rajiv-gandhi-national-institute-of-youth-development-act-2012` shows the POCSO Act summary,
  `maintenance-and-welfare-of-parents-and-senior-citizens-act-2007` the Indian Maritime University one) → wrong text and
  duplicate descriptions; (2) `insurance-act-1938` section HTML has footnote links with bad `href`s (`1[`, `2`, `7*` …) →
  11 broken internal links; (3) two Acts with the same title in one jurisdiction (`constitution-of-india` /
  `constitution-of-india-act-1` under Andaman and Nicobar, two Ladakh "Natural Calamities" Acts) → duplicate titles.
- SEO, owner decision: a Central Act listed under a UT (`original`) has the same sections as the Central page. Titles now
  differ, but the text is a duplicate; a `rel=canonical` to the Central page would merge them in Google (the UT page then
  is not shown in results).
- Deploy the SEO fixes to act.legalquery.in, then resubmit `/sitemap.xml` in Google Search Console and check
  Pages → "Why pages aren't indexed" there.
- Scraper export (not this project): schedule `body_text` is raw PDF text: 364 of 402 start with the PDF page number
  (e.g. "7"), 16 contain the broken India Code watermark ("In / diaC / ode"). Clean it in the export.
- Before all States / the full data set: content format 3 (split `files.json` by sha256 prefix; it is read on every
  PDF request). See the scraper's CHANGELOG (2026-11-03). The code is jurisdiction-generic since 2026-11-16; adding a
  State still needs the owner's URL choice (`/acts/<code>`; CONTENT.md planned `state/<state>/` folders) and a
  decision on how home and the category pages show State Acts.
- 7 PDFs now have a new sha256 (the scraper's store copy differs from its old download): their old
  `/files/<old sha256>/…` URLs 404. Only matters if those URLs were shared.

- Real PDF thumbnails (first page) to replace the demo image: needs a thumbnail field from the
  scraper export (+ `docs/CONTENT.md`).
- Scraper export: about 56 PDFs are Hindi (file name `H…`) but labelled `language: en`, e.g.
  `recycling-of-ships-act-2019` → `h201949.pdf`. The site shows them as हिन्दी by file name (`pdf_choices()`); fix the
  label in the export, then that workaround can go.
- Owner review of the pages in the browser (home redone 2026-11-02).
- Category pages show Acts only, without counts of rules/notifications per Act: that needs tab counts
  in `index.json` (scraper export change + `docs/CONTENT.md`).
- "Browse by ministry" (India Code has it) would need new URLs: owner to decide.
- Push to a GitHub repo (`legalquery-web`) — needs the owner's OK and the repo.
- Production (act.legalquery.in, Apache): content format 3 uploaded 2026-11-18 (FileZilla); later updates: rsync.
- SEO, needs the scraper export: a `content_changed_at` per Act (date the text last really
  changed) for `<lastmod>` in the sitemap and `dateModified`; `last_checked_at` is only the crawl
  date, so it is not used (Google ignores lastmod that changes on every crawl).
- SEO, owner: final `https://` domain in `site_url` before launch, then submit the sitemap in Google
  Search Console / Bing Webmaster Tools. Check the share image `public/assets/img/share.png`.
- Later: full-text search inside sections (today: Act titles/numbers/years and section headings),
  PDF thumbnails, State Acts when the scraper has them.

## History

### 2026-11-27 — `translations/` holds only the AI translations' PDFs; their text JSON moved to the ledger (owner)
- The site only reads `document.pdf` (`Content::aiNewest`); `translation.json` and `document.rag.json` are for
  legalquery-ai's `load-translations`. The 951 JSON files of the 517 staged translations (517 `translation.json`,
  434 `document.rag.json`, each SHA-256 checked) moved to `../translation-ledger/text/<sha>/<lang>/vN/`.
  `translations/` is now 97 MB of PDFs (was 179 MB); 66 Finder `.DS_Store` files left as they were.
- `../translation-ledger/ledger.py`: `stage` copies the PDF to the site and the JSON to `text/` (`--text DIR`);
  `unstage` removes both and ignores Finder's `.DS_Store`.
- `dev.sh`: `TRANSLATIONS_TEXT_DIR` (default `../translation-ledger/text`) replaces `TRANSLATIONS_DIR` for the load.
  README updated.
- Checks: `bash -n dev.sh` ok; `./dev.sh load` → 517 seen, 517 unchanged, 0 deleted (PostgreSQL still 517);
  all 517 `/pdf?…&v={lang}-ai` → 200 with the staged PDF's SHA-256 = the ledger's; `unstage` then `stage` of 101 in a
  scratch copy of the ledger → PDF back in the site folder, 2 JSON in `text/`. Not committed.

### 2026-11-27 — Review of the Karnataka export (owner) — no code change
- Export of 2026-11-27T09:34Z (manifest: 6,271 Acts, 31 jurisdictions). Karnataka: 353 Acts, all without section text
  (0 sections, 0 schedules: the text is in the PDFs), 446 Act PDF entries (354 `en`, 92 `kn`), tabs on 342 Act entries
  (rules 181, notifications 88, ordinances 54, regulations 11, amendments 8).
- Clean: 353 folders = 353 index entries; every slug is in `slugs.json`, lowercase, no duplicates (longest 120
  characters); every Act has a number, year and enactment date; no "India Code" in summaries (all empty).
  `php -d memory_limit=4G tests/audit-pdfs.php --only=karnataka --hash --text=60` → 445 Act PDFs + 1,116 tab documents
  (1,435 distinct files), "No problems found" (sha256 of every file, 52 texts checked, 8 scanned). All 353 Act pages
  200; `/acts/karnataka` and `/page/18` 200, `/page/19` 404; 1,143 Karnataka URLs in the sitemap; `/states` shows
  "353 Acts"; search finds them; the language menu offers English and ಕನ್ನಡ on an Act with both PDFs.
- Found, all in the source data (scraper export or India Code), none blocks the site:
  1. 326 of 353 titles are ALL CAPITALS (other States: Odisha 180 of 343, Haryana 31, Gujarat 3): headings and the
     `<title>` shout, one title tag is over 200 characters.
  2. 7 Acts are there twice (two India Code records, same number and year; the second slug ends in `-act-<no>`):
     Shree Renuka Yellamma Kshetra Tourism Development Board 2023, Krantiveera Sangolli Rayanna Kshetra Development
     Authority 2016, Karnataka Industries (Facilitation) 2002, Cauvery Basin Irrigation Protection 1991, Agricultural
     Credit Operations 1974, Agricultural Produce Marketing 1966, Societies Registration 1960 → duplicate titles.
  3. `karnataka-state-universities-act-2000`: the "(k)" PDF is byte for byte the English one (same sha256), so the
     Act has no Kannada PDF; the site shows one PDF.
  4. `vellore-institute-of-technology-bangalore-act-2012`: no PDF on India Code; the page says "No text or PDF available."
  5. The State list's description says "with full text, sections and official PDFs" though Karnataka has no sections.

### 2026-11-27 — SEO check; PDFs stay out of Google (owner)
- Checked 17 kinds of page on the local site (home, States, lists, paged and year lists, filtered list, category, Act
  Sections / page 2 / Details / Schedules, policy pages, search, 404): each has one `<h1>`, a title (24 to 76
  characters), a canonical to itself (none on search and 404), valid JSON-LD and no image without `alt`; filtered lists
  and search are `noindex, follow`, 404 is `noindex`. Sitemap: 20,281 URLs, none of `/pdf`, `/files/`, `.pdf`, `/search`.
- PDFs, already in place: `robots.txt` Disallow `/pdf` and `/files/`; every PDF link is `rel="nofollow"`; the download
  page and the PDF itself send `X-Robots-Tag: noindex, nofollow`; `.htaccess` answers bots 403; the sum question.
  Live (act.legalquery.in) as Googlebot: `/pdf?…` → 403, `/acts/central/….pdf` → 403, the Act page → 200.
- Fixed: `robots.txt` also has `Disallow: /*.pdf$` (the old `/acts/<j>/<slug>.pdf` URLs were not covered by the two
  prefixes; they were only stopped by the 403).
- Fixed: the three policy pages are `noindex, follow` and no longer in the sitemap: their text is a copy of
  legalquery.in's pages (duplicate content on another domain).
- Not changed, owner to decide: (1) the home `<h1>` is "Welcome to LegalQuery" (no "laws" / "Acts of India" in it since
  the rename); (2) the site's name in `og:site_name` and the WebSite JSON-LD is "LegalQuery", the same as legalquery.in.
  The SEO items under *Pending* (wrong summaries, duplicate titles, UT copies of Central Acts, `lastmod`) still stand.
- Checks: `php -l`; `php tests/run.php` → 209 checks passed (new: the three `robots.txt` PDF rules).

### 2026-11-27 — Home boxes: equal height per row, not all four (owner)
- Owner: all four boxes at the height of the tallest one did not look right (the lower two were half empty).
  `grid-auto-rows:1fr` removed: the two boxes of a row are still equally tall, but each row is only as tall as its
  own content. `site.css?v=51`.
- Checks: `php tests/run.php` → 208 checks passed; browser at 1024 px: box heights 343, 343, 235, 235.

### 2026-11-27 — Home: the four boxes have the same height (owner)
- `.home-grid`: `grid-auto-rows:1fr` and no `align-items:start`, so all four boxes are as tall as the tallest one
  (above 900px; one column below that, each box its own height). `site.css?v=50`.
- Checks: `php tests/run.php` → 208 checks passed; browser: the four boxes have one height at desktop width, their own
  heights at 375 px.

### 2026-11-27 — Home: fourth box "Browse by State" (owner)
- The fourth home box is now "Browse by State": the 10 States / Union Territories with the most Acts (most first), each
  with its Act count and a link to its Act list, then "All N States and Union Territories →" (`/states`). It replaces
  "What each Act page has" (still shown while no State is exported); the welcome line above already says what the pages
  have. The "Acts of States and Union Territories →" link under "Browse by category" is gone (same target as the new box).
- `HomeController::states()` (`STATES_SHOWN = 10`), `templates/home.php`. No new URL, no CSS change.
- Checks: `php -l`; `php tests/run.php` → 208 checks passed; home page in the browser at desktop and 375 px.

### 2026-11-27 — Footer phone number changed (owner)
- Footer "Phone:" is now +91-8272000923 (was +91-8869850001), text and `tel:` link (`templates/partials/footer.php`).
- Checks: `php tests/run.php` → 208 checks passed; the home page's HTML has the new number twice (text, link) and the old one nowhere.

### 2026-11-27 — Header: no line under the name (owner)
- The line "भारत के अधिनियम · The Acts of India" under "LegalQuery" in the header is removed
  (`templates/partials/header.php`; `.site-tag` styles removed, `site.css?v=49`). It was already hidden on phones.
- Checks: `php tests/run.php` → 208 checks passed; header checked in the browser at desktop and 375 px.

### 2026-11-27 — Footer contact lines: bold labels, more line height (owner)
- "Email:" and "Phone:" are bold and white (`<strong>` in `templates/partials/footer.php`); the two lines have line
  height 2.1 (was 1.6; 1.9 on phones) on every screen size (`site.css?v=48`).
- Checks: `php tests/run.php` → 208 checks passed; footer checked in the browser at 375 px and desktop.

### 2026-11-27 — Footer redesigned for phones and tablets (owner)
- Owner: the footer did not look right on a phone (logo and text at the left, Laws and Policies in two columns,
  Official sources alone at the left under them, then a centred disclaimer).
- Phones (up to 640px): everything centred, in bands parted by thin rules: logo, the site's line, e-mail and phone;
  Laws and Policies side by side; Official sources with its two links on one row; the disclaimer (12.5px) and the ©
  line under it (white, its own line: `.foot-copy`, was a `<br>`). Links are taller to tap (7px padding).
- Tablets (641 to 900px): logo and the site's line on a row of their own, the three link lists side by side below
  (was a 2 × 2 grid).
- Laptops: unchanged, except that the © line is white.
- `templates/partials/footer.php` (© line in a `div`), `site.css?v=47`.
- Checks: `php tests/run.php` → 208 checks passed; browser, `/states`: 320, 375 and 768 px and desktop, no horizontal scroll.

### 2026-11-27 — Refund Policy page removed (owner)
- This site takes no payments, so `/refund-policy` is gone before it was ever live: out of `PageController::PAGES`
  (so out of the footer, the router and the sitemap), `pages/refund-policy.html` deleted, `docs/URLS.md` updated.
  The other three policy pages stay. Their text still reads as written for legalquery.in (the Terms of Service have a
  payments and refunds part): owner to read before launch.
- `/lib/icc-common.js` in `templates/layout.php` stays (owner): the file exists on the live server; it is only missing
  locally, where it gives a 404 and "ICC is not defined" in the console.
- Checks: `php tests/run.php` → 208 checks passed (new: `/refund-policy` has no route); curl: `/refund-policy` 404, the
  other three 200, sitemap lists three policy pages; footer "Policies" shows three links.

### 2026-11-27 — Contents bar on phones: edge to edge (owner)
- Owner: the bar's side padding looked wrong (a grey box set in from the screen edges, with the page text showing
  beside it). The bar and its list now run from edge to edge (negative side margin = the page padding: 24px, 16px up
  to 640px), with a rule above and below only; their text lines up with the page's text (`site.css?v=45`).
- The bar says "Contents" until it is stuck to the top of the screen; before, it already named Section 1 while still
  sitting above it (`site.js?v=26`).
- Not done, on purpose: a sticky site header on phones (owner left it to us). The phone header is two rows (logo and
  language, then search), about 95px; with the 46px bar it would cover a quarter of a small screen. The bar alone at the
  top gives the same "where am I" as the reference page. Easy to add later if wanted (owner decision 2026-11-26 made the
  header sticky on laptops only).
- Checks: `php tests/run.php` → 207 checks passed; browser, Kerala (Alteration of Name) Act, 2026: 320 px: bar left 0, right
  320, page width 320, "Contents" before it sticks, "3. Alteration of name of State of Ker…" at section 3, list opens
  full width; 768 px: bar 0 to 753 (the scrollbar takes the rest), page width 753.

### 2026-11-27 — Contents bar: a long section name no longer widens the page on a phone (owner's screenshot, 320 px)
- Bug from the entry below: the bar's name is one line (no wrap), and the Sections grid column was `1fr`, which grows
  to its content. A long name ("1. The Kerala (Alteration of Name) Act, 2026") made the whole column 381 px wide on
  a 320 px screen: text cut off at the right, the bar's arrow off screen.
- Fix: `.act-sections { grid-template-columns:minmax(0, 1fr); }` up to 900px (`site.css?v=44`); the name now ends in "…".
- Checks: `php tests/run.php` → 207 checks passed; browser at 320 px, Kerala (Alteration of Name) Act, 2026 `#section-6`:
  page width 320, no element past the right edge, bar "6. Amendment of First Schedul…" with its arrow at 301 px; Indian
  Contract Act page 3 with every section open: width 320.

### 2026-11-27 — Contents on phones: a bar that follows the section being read (owner)
- Owner: the Contents of an Act's Sections tab showed on a desktop but were hidden on a phone; show them as on
  legalquery.in's guide pages (a bar under the header that names the current heading and opens the list).
- Up to 900px the Contents column (`templates/act-sections.php`, same markup, no second list) is now a bar that stays
  at the top of the screen: "Contents" until a section passes it, then e.g. "7. Acceptance must be absolute"; a tap
  opens the list of this page's sections (and "Next sections →"), a tap on a section closes it, opens that section and
  scrolls to it below the bar. Outside tap or Esc closes the list. Without JavaScript: the plain list above the sections.
- Laptops: the column is as before ("Contents"), and the link of the section being read is now bold.
- `site.js?v=25` (block "Contents of an Act's Sections tab"), `site.css?v=43` (`.toc-bar`, `html.has-toc-bar`
  scroll padding 56px so a section lands below the bar). The site header is still not sticky on phones, so the bar
  sits at the very top there.
- Checks: `node --check site.js`, `php -l`, `php tests/run.php` → 207 checks passed; browser, Indian Contract Act, 375 px:
  bar sticks at top 0, shows "7. Acceptance must be absolute" at section 7, list opens, "#section-15" opens the section
  and the bar says "15. "Coercion" defined", back at the top it says "Contents", no horizontal scroll; 1024 px: heading
  stays "Contents", toggle hidden, active link "#section-15". No console errors from `site.js`.

### 2026-11-27 — Footer: contact details and a full sentence about the site (owner)
- `templates/partials/footer.php`, under the logo: "LegalQuery brings together the Central Acts and the Acts of the States
  and Union Territories of India, section by section, with their schedules, rules, notifications and official PDFs."
  (was a sentence fragment), then "Email: contact@legalquery.in" (mailto) and "Phone: +91-8869850001" (tel).
- `.foot-contact` (`site.css?v=42`).
- Checks: `php tests/run.php` → 207 checks passed; footer checked in the browser at desktop and 375 px.

### 2026-11-27 — Policy pages in the footer (owner)
- Four pages of this site's own, linked in a new footer column "Policies": `/privacy-policy`, `/terms-of-service`,
  `/legal-disclaimer`, `/refund-policy` (new permanent URLs, in `docs/URLS.md` and the sitemap). Not links to
  legalquery.in: the text is copied from legalquery.in's Privacy Policy, Terms of Service, Legal Disclaimer and Refund
  Policy pages (node 126, 125, 397, 398; fetched 2026-11-27) into `pages/<slug>.html` (in git), word for word.
  Cleaned only the markup: inline styles and classes removed, Cloudflare-hidden e-mail addresses written as mailto
  links, the two links to legalquery.in and one "#" link kept as plain text, the page's own "Contents" box left out.
- New: `src/Controller/PageController.php` (`PAGES` slug → title), `templates/page.php`, `Url::page()`, route in
  `Router.php`, `.policy` styles and a 4-column footer grid (`site.css?v=41`). `CLAUDE.md` rule 3 names the new
  trusted-HTML exception.
- Open (owner to read): the text is written for legalquery.in (clients, lawyers, payments, refunds, mobile app) and
  names "LegalQuery.in" and its e-mail addresses; this site takes no payments. Reword for this site if wanted.
- If legalquery.in changes its policies, copy them again: the pages here do not update by themselves.
- Checks: `php -l` clean; `php tests/run.php` → 207 checks passed (3 new: route, URL, every page has its file); curl:
  the four URLs 200, `/Privacy-Policy/` → 301 `/privacy-policy`, `/nope-policy` 404, all four in `/sitemap.xml`; browser:
  Privacy Policy page and footer at desktop and 375 px, no horizontal scroll.

### 2026-11-27 — Footer disclaimer reworded and centred (owner)
- `templates/partials/footer.php`: "Disclaimer: Content sourced from India Code, Government of India. Original legal text is
  presented without modification. This content is provided for informational purposes only and does not constitute legal
  advice. LegalQuery is not an official government website." then "© <year> LegalQuery. All rights reserved." on its own line.
  This closes the two open points of the entry below ("not legal advice" is back; "original legal text" is exact).
- `.source-note` centred (`site.css?v=40`).
- Checks: `php tests/run.php` → 204 checks passed; footer checked in the browser at desktop and 375 px width.

### 2026-11-27 — Footer disclaimer; site name "LegalQuery" (owner)
- Footer note (`templates/partials/footer.php`): "Source: India Code, Government of India. Shown for information; this is
  not the official certified copy and not legal advice." → "Disclaimer: All legal content is sourced from India Code,
  Government of India, and is presented as received without any modifications or alterations." The "© <year> LegalQuery.
  All rights reserved." line stays. Still plain text, no India Code URL; "India Code" still only in the footer.
- Name "LegalQuery Laws" → "LegalQuery": header logo, footer brand, home heading ("Welcome to LegalQuery"), Act heading
  ("From LegalQuery"), `og:site_name`, home JSON-LD WebSite name. `CLAUDE.md` rule 5 reworded to match.
- Open (owner to decide): the old "not the official certified copy and not legal advice" words are gone; and "without
  any modifications" is not exact (the export sanitises HTML and removes India Code links; AI translations are staged).
- Checks: `php -l` on every PHP file clean; `php tests/run.php` → 204 checks passed; home page in the browser (localhost:8800): header and
  footer say "LegalQuery", footer shows the new disclaimer and "© 2026 LegalQuery. All rights reserved."

### 2026-11-26 — "India Code" only in the footer (owner)

- Removed from the site's own text, kept only in the footer's "Source: India Code, Government of India.":
  the Act heading's "· Source: India Code, Government of India"; the Details tab's Source row and "Last
  checked on India Code" (now "Last checked"); the download page's note (now "Rely only on the official
  record."); the intros of `/acts/central`, State Act lists, `/states` and category pages ("as listed on
  India Code, with sections where India Code has them" → "with sections where available"); the meta
  descriptions of `/states` and category pages. CLAUDE.md rule 5 updated.
- Left: India Code's own words in the exported content (Details tab History of 836 Central Acts, "Source:
  Official Records …, India Code, and Ministry …"; "Act ID (India Code)"; 3 summaries) and PDF file names
  (`indiacode30.pdf`, `indiacode32.pdf`, an MP file "…new india code portal.pdf"; renaming them would change
  their `/pdf` URLs). The content text needs the scraper's export.
- Checks: `php tests/run.php` 204 checks pass; no "India Code" outside comments in `templates/`, `src/`,
  `public/` but the footer; 11 pages fetched (home, States, lists, category, search, Act, Details, tab, PDF
  page): only the footer line, plus the content's History / summary on Details tabs.

### 2026-11-26 — Shorter note on Acts without section text (owner)

- Sections tab of an Act with no section text but a PDF: "India Code publishes no section-wise text for this
  Act. The full text is in the Act's PDF." → "The full text is in the Act's PDF." (`partials/provision-list.php`).
  Then the note for an Act with neither text nor PDF (22 State / UT Acts): "India Code publishes no section-wise
  text and no PDF for this Act." → "No text or PDF available." `php tests/run.php` passes; both checked on a page.

### 2026-11-26 — Language menu lists only the languages of the page's PDFs, English alone elsewhere (owner)

- Home, States, Act lists, search and other pages without PDFs listed every language. Now `site.js` keeps only
  the languages of the PDFs on the page: an Act page (and its tabs) the Act's own PDFs, as before; a download page
  that PDF and its other PDFs, as before; any other page the PDF links shown (`data-lang` added to
  `partials/file-links.php`), plus English; a page with no PDF: English only. The saved choice
  (`localStorage`) is not changed by this, so the next Act page still opens in it.
- Checks: `php tests/run.php` passes; in the browser with Hindi saved: `/`, `/states`, `/acts/central`,
  `/acts/gujarat`, `/search?q=rules` → English only; MP Rights of Persons with Disabilities Act page and its
  Rules tab → English, हिन्दी (Hindi picked).

### 2026-11-26 — Documents linked from sections get a /pdf URL, like the Act's PDFs (owner)

- Before, a document linked only from sections or schedules (in none of the Act's tabs) opened at
  `/files/{sha256}/{name}.pdf`. Now `/pdf?j={jurisdiction}&a={act}&s={provision}&f={file}`, e.g.
  `/pdf?j=madhya-pradesh&a=rights-of-persons-with-disabilities-act-2016&s=section-3&f=rules_notified_15.06`
  (`Url::provisionPdf`). `s` = the first section (else schedule) linking it, `f` = file name without `.pdf`,
  lowercased (folder in front when two files of one provision share a name). One linked from a tab too keeps
  the tab's URL. Same download page, PDF question, title, date, language and back link to that section.
- `Content::pdfLinks()` (checksum → `/pdf` URL, for tabs and provisions, once per Act and request) replaces
  `linkedHref()` / `linkingProvision()`; the Sections and Schedules tabs link through it (`ActController::withText`).
- Old `/files/` URLs of those documents → **301** (POST 307) to the new URL. `/files/` is still served only
  for a PDF linked from nowhere. `docs/URLS.md` updated.
- Checks: `php tests/run.php` 204 checks pass (new: URLs by first section, same name in another folder, tab
  URL kept, Act page has no `/files/` link, old URLs' targets, unknown `s` / `f` → 404). Audit
  `--hash --text=400`: 20,541 section PDF links, every URL leads back to its file, every old `/files/` URL to a
  `/pdf` page, all back / other links open; only the 189 ".pdf" titles left. Checked in the browser.

### 2026-11-26 — Download-page tests, a PDF audit of all content, and two bugs it found (owner)

- `tests/run.php`: download pages end to end on a small temporary content/ with tiny real PDFs (each
  request in its own PHP process, as `send()` flushes every output buffer): the Act PDF; a tab notification
  in English + Hindi (title, date, language, other-language link, back); `/files/` documents linked only
  from sections (title, date, language `bi`, "Other PDFs of this document", back to the first linking
  section, a rule in `pdf/rules/` with no Rules tab, a PDF linked nowhere); every back and "Other PDFs"
  link opens a page; the served PDF is the file plus the site's title (PdfTitle). 199 checks.
- New `tests/audit-pdfs.php` (README, CLAUDE.md): every PDF the site offers in the real content/, through
  `FileController::info()`: file in the index, `%PDF-` … `%%EOF`, size as exported (`--hash`: sha256),
  tab links lead to the same file, title not junk, language set, back / "Other PDFs" links open a page,
  PdfTitle applies, section `docs` counts; `--text=N` compares language with the text's script.
- Bug 1: `Content::pdfPath()` refused any path containing `..`, so 16 PDFs whose names have dots in a row
  (`…_dt_18..11.2019.pdf`, `…30.05.2018...hindi.pdf`) gave 404. Now only a `.` / `..` folder is refused.
- Bug 2: tab documents' download pages never showed their Date (`linkedFile()` did not pass `date`).
- Audit result (51,538 PDF links, 28,792 files, `--hash --text=400`): all files present, valid and with the
  right sha256; all links and back links open; 391 texts match their language (9 scanned); left: 189 titles
  from India Code that end in ".pdf" (e.g. "SO 28 dt 2.1.2019 (Appointment of …, CCI).pdf").

### 2026-11-26 — /files/ download pages: language menu, title and date from the section that links the PDF (owner)

- 996 PDFs have no /pdf URL (they are in none of the Act's tabs, only linked from a section or schedule)
  and are served at `/files/{sha256}/…`. Their download page had no `data-pdf-lang`, so the header language
  menu listed every language (e.g. `amendment_guidelines__09.09.2020.pdf`, a bilingual Gazette: Hindi
  page 1, English page 2).
- `Content::linkingProvision()` now returns `slug` and `doc` (the linked document). `FileController::info()`
  uses it for `/files/` pages: the PDF's language (`pdf_choices`), the document's other PDFs ("Other PDFs of
  this document"), its title (also the served PDF's title) and date, and the back link to that section
  (before the folder's tab, which does not list this PDF).
- Checks: `php -l`; `php tests/run.php` (183 passed); all 996 `/files/` pages fetched: every one has
  `data-pdf-lang` (en 563, bi 410, hi 20, unknown 3) and a back link to a section (59 pages, all 200);
  24 list other PDFs; browser: menu shows English + हिन्दी on the bilingual Gazette, English only on
  Electricity Act rule `16.pdf`.

### 2026-11-26 — PDFs open with the site's title, not their junk Title metadata (owner)

- Many source PDFs carry junk Title metadata, which the browser shows in the tab and PDF viewer, e.g.
  `[£ÉÉ®iÉ BÉEä ®ÉVÉ{ÉjÉ, …]` (legacy-font Hindi) on `Rules_notified_15.06.pdf`. Of 33,525 PDFs, 10,143
  have a Title: "Hkkjr ds jkti= ds vlk/kkj" (208), "Microsoft Word - H2007-56 à¤®…", `C:\Users\Acer\…`,
  "117gi.p65", "Untitled" (462) …
- New `src/PdfTitle.php`: when a PDF is sent, a PDF incremental update is appended to the served bytes: a new
  Info dictionary with the download page's title (UTF-16 hex, so Devanagari works) and, when the catalog
  has XMP `/Metadata` (Firefox shows its dc:title first), a copy of the catalog without it. A classic
  xref table or, after a cross-reference stream, an uncompressed xref stream with `/Prev` to the file's own.
  The file on disk, its checksum and its URL are unchanged; the page text and pages are untouched.
  Gives up (PDF served as is) for encrypted PDFs or when `startxref`/trailer can't be read safely.
- `FileController::send()`: Content-Length includes the suffix; the ETag includes it too.
- Checks: `php -l`; `php tests/run.php` (183 passed, 7 new PdfTitle checks); 600 random PDFs (350 with a
  Title) + 40 with long xref tables: pdfinfo shows the new title, same page count, no new errors; Chrome:
  the tab shows "Rules notified 15.06" and all 46 pages render.

### 2026-11-26 — Other-State documents no longer listed under sections (owner)

- India Code links some sections of Central Acts to another State's notification (e.g. the UP notification
  279/65-3-2018-40-2018 under Section 14 of the Rights of Persons with Disabilities Act, 2016, shown on the
  Central, Delhi and MP pages as "PDF · PDF 2"). India Code's live page does not list them.
- Fixed in the scraper's export (`src/web_export.py`, `_linked_items`): section documents with
  `shown_on_site = 0` are left out (313 items: UP 190, Maharashtra 121, Rajasthan 2); State unknown is
  kept. No site code changed.
- Re-exported `content/` (the export also added the newly scraped Acts' slugs to `slugs.json`, additions only).
- Checks: scraper tests (322 passed); `php tests/run.php` (176 passed); the UP notification is in no
  content file; browser: MP Section 14 has no "docs" count and no document list.

### 2026-11-26 — "Back to the page" goes to the section that links the PDF, not a 404 (owner)

- A `/files/{sha256}/…` PDF shared by several Acts (e.g. `amendment_guidelines__09.09.2020.pdf`: Central,
  Delhi, Madhya Pradesh) is indexed under one of them. For MP it sits in `pdf/notifications/`, linked only
  from Sections 1 and 56; MP's Act has no Notifications tab, so the back link went to a 404.
- `FileController::info()` now links to the tab only when the Act has it; else to the first section (then
  schedule) whose linked documents include that checksum (`Content::linkingProvision()`, reads only
  provisions with `docs`), e.g. `/acts/madhya-pradesh/rights-of-persons-with-disabilities-act-2016#section-1`;
  else the Act page (owner: "Section 1 par back jana chahiye tha").
- Checks: `php -l`; `php tests/run.php` (176 passed); browser: the back link opens Section 1 with the
  notification listed under it.

### 2026-11-26 — Sticky header on laptops and desktops (owner)

- Wider than 900px the header (logo, search, language, menu) stays at the top while the page scrolls
  (`position:sticky`). Phones and tablets keep the normal header, so it takes no screen space there.
- site.js keeps the header's height in `--head-h`. `html { scroll-padding-top }` uses it, so a link to
  `#section-N` opens the section below the header, and the sticky contents column (`.toc`) starts below it too.
- `site.css?v=39`, `site.js?v=20`. Checked at 1280 and 1024px (section links, contents column) and 375px (not sticky).
  176 checks pass.

### 2026-11-26 — Header fix: side margins back, language at the right edge (owner)

- `.topbar { padding:10px 0 }` overrode the `.container` side padding, so on desktop the logo touched the left
  edge and the menu ("Search") was cut off on the right. It now sets only top and bottom padding (also on phones).
- The language menu sits at the right edge, in line with the menu below it. `site.css?v=37`.
- Checked at 1400, 1024, 768 and 375px: no horizontal scroll. 176 checks pass.

### 2026-11-26 — No type label per row in the Rules / Notifications / Amendments … tabs (owner)
- `act-tab.php`: the small "RULE" / "NOTIFICATION" / "AMENDMENT" label above each document title is gone: every item
  of a tab has the tab's type (checked over all of `content/`: none differs). The label stays in the mixed list of
  documents under a section (`provision-body.php`).
- Checks: `php -l` ok; `php tests/run.php` → 176 checks passed; browser: UAPA Rules, Notifications, Amendments tabs
  show title only; a section's linked-document list still shows its labels. Not committed.
### 2026-11-26 — Download page of a rule / notification / amendment: language menu limited to its PDFs
- Owner: on the download page of a linked document (rules, notifications, amendments …) the header language menu listed
  every language although the PDF is in English. Now it lists only the languages of that document's PDFs, as on Act
  PDF pages: the PDF's own language (`pdf_choices()`, so `bi` = English + हिन्दी counts for both and `H…` file names
  count as Hindi), plus its other-language PDFs, which are listed under "Other PDFs of this document" and opened by
  picking that language. `Content::linkedFile()` adds `item_files`; `FileController::info` gives `lang` and
  `others` for linked documents and `others_title`; `pdf-check.php`; `site.js?v=18`.
- Data: linked-document files are mostly `en` (19,977), then `bi` (4,080), `hi` (404), `unknown` (232), `pa` (32);
  about 100 documents have PDFs in two languages.
- Checks: `php tests/run.php` → 176 checks passed; `php -l`, `node --check` ok; browser: UAPA amendment `1986-4` →
  menu English only; UAPA notification `notifciation18individualterrorist_27102020` (labelled `bi`; `pdftotext`: a
  bilingual Gazette, 32,785 Devanagari letters) → English, हिन्दी; Private Security Agencies Central Model Rules 2020
  (English and Hindi PDFs) → English, हिन्दी, picking हिन्दी opens the Hindi PDF's download page. Not committed.
### 2026-11-26 — Fonts and font sizes as on Wikipedia (owner)
- Measured on en.wikipedia.org (Vector 2022, "Standard" text size, 1024 px wide): text in the browser's `sans-serif`,
  16px, line-height 26px; h1 28.8px and h2 24px in "Linux Libertine", Georgia, Times, "Source Serif 4", serif, weight
  400, line-height 1.375; h3 19.2px bold sans; infobox 14.08px; TOC and tabs 14px. (Earlier in chat Claude had said
  Wikipedia's text is 14px: wrong, the measured size is 16px.)
- `site.css`: `--sans` is now `sans-serif` (was the system-UI stack -apple-system / Segoe UI / Roboto …), `--serif` is
  Wikipedia's stack (+ "Noto Serif Devanagari" before `serif` for Hindi); body and section / schedule text 16px with
  line-height 1.625 (was 1.6 / 1.7; schedule text was 15px); h1 of Act, list, home and error pages 28.8px (was 32 /
  30px); h2 24px (was 23px); heading line-height 1.375. Unchanged: infobox and Contents 14px, home box headings 19px,
  phone h1 26px. `site.css?v=35`.
- Checks: computed styles on the Post Office Act page: body and section text `sans-serif` 16px / 26px, h1 28.8px /
  39.6px in the serif stack, infobox 14px, Contents 14px; home h1 28.8px; 375 px: h1 26px, no sideways scroll.
  `php tests/run.php` not affected (CSS only). Not committed.
### 2026-11-26 — Header language menu: "Language" instead of the 文A icon (owner)
- The 文A icon before the header language menu is replaced by the word "Language" (`partials/header.php`,
  `.lang-label`); on phones (≤ 640 px) the word is hidden from view but still names the menu for screen readers.
  `site.css?v=34`.
### 2026-11-26 — PostgreSQL load of the staged AI translations; AI Hindi of the Police Act, 1949 removed
- `./dev.sh load`: 4,669 Acts unchanged; all 518 staged translations were already in PostgreSQL (unchanged), 1
  skipped there as official Hindi: 241 hi, The Police Act, 1949. Its English PDF belongs to the Central Act (which has
  an official Hindi PDF, `H1949-64.pdf`) and to the Dadra and Nagar Haveli and Daman and Diu copy (English only);
  `content/files/` names only the copy, so the ledger matched it there and this site showed AI Hindi on the UT page.
- `../translation-ledger`: `match` now checks every Act whose PDFs include the source PDF for an official Hindi PDF
  (same test as legalquery-ai's `has_official_hindi`: language hi/bi or a file named H<digits>…). No other staged
  translation has this case (35 staged PDFs belong to several Acts, none with Hindi). 241 unstaged and blocked
  `official_hindi_pdf_exists`; the UT page no longer lists `hi-ai`, its `/pdf?…&v=hi-ai` → 404.
- `./dev.sh load` again: 517 seen, 517 unchanged, 1 deleted. PostgreSQL: 517 active translations, 23,560 chunks.
  Ledger: 517 staged, 0 live, 1,224 unmatched, 608 blocked (554 State/UT, 40 official Hindi, 14 India Code text).

### 2026-11-26 — Act page: PDF button with an icon
- Owner: show the PDF as a button with an icon. `partials/act-pdfs.php`: the link at the right of the tab row is
  now a button: a page icon with a red "PDF" band (inline SVG, aria-hidden), "PDF" and the language
  ("PDF English", "PDF हिन्दी (AI अनुवाद)"). Grey border, light grey background; on hover a blue border and blue
  icon. Same link (the /pdf download page), same language switching by site.js. `site.css?v=33`.
- Checked on desktop and on a 375px phone (no horizontal scroll). 176 checks passed.

### 2026-11-26 — Logo animation: "Hawa ka jhonka"
- Owner picked option 2 "Hawa ka jhonka" from the wind demos; it replaces the grow + shimmer of 2026-11-25.
  `public/assets/img/logo.svg` (now `?v=3` in header and footer): every 6 s a gust runs up both laurel branches
  from the bottom (leaf pairs 0.07 s apart); each leaf bends on its stalk by its own angle (5–8°, ×2 at the peak,
  then swings back) and blinks (opacity down to 0.55). The scales and stems stay still. CSS inside the SVG, no
  JavaScript; still with "reduce motion". Each leaf is wrapped in a `<g>`, so the CSS transform does not replace the
  leaf's own `rotate()`.
- Checked in the browser: leaves at rest at first, at 4.3 s rotated about 9–13° with opacity about 0.55–0.6.
  176 checks passed.

### 2026-11-25 — Every matched AI translation staged (518 in all, 423 Acts)
- Asked for 500 more; the ledger had only 416 matched (bn 352, hi 64), so all of them: 414 staged, 2 blocked by the
  stage check for "India Code" text in the PDF (2782 hi, 3053 bn). Now staged: bn 384, hi 97, mr 6, gu 5, kn 5, ml 5,
  or 5, pa 5, ta 3, te 3. Ledger: 518 staged, 0 matched, 1,224 unmatched (Act not in content/ yet), 607 blocked (554
  State/UT scope review, 39 official Hindi, 14 India Code text), 0 live. More need either more Acts exported or the
  owner's decision on State/UT drafts. `translations/` is 179 MB (not in git).
- Checks: for all 518, the Act page lists the `{lang}-ai` button, `/pdf?…&v={lang}-ai` → 200 with the AI notice, the
  staged PDF's SHA-256 = the ledger's, no "India Code" text, no `.part` left; `php tests/run.php` → 176 passed. Not
  committed, not on the server, PostgreSQL load not run.

### 2026-11-25 — 50 more AI translations staged (104 in all, 53 Acts)
- `ledger.py stage` (`../translation-ledger`): the next matched ones of each language and AI. The smaller languages are
  almost used up among matched drafts, so most are hi/bn. Now staged: hi 34, bn 33, mr 6, gu 5, kn 5, ml 5, or 5,
  pa 5, ta 3, te 3. None of the 50 had "India Code" text (the stage check blocked none). Ledger: 104 staged, 416
  matched, 1,224 unmatched, 605 blocked, 0 live. `translations/` is 42 MB (not in git).
- Checks: for all 104, the Act page lists the `{lang}-ai` button, `/pdf?…&v={lang}-ai` → 200 with the AI notice, the
  staged PDF's SHA-256 = the ledger's, no "India Code" text; `php tests/run.php` → 176 passed. Not committed, not on
  the server, PostgreSQL load not run.

### 2026-11-25 — Animated laurel in the logo
- Owner: the logo's leaves should be animated. `public/assets/img/logo.svg` (header and footer, now `?v=2`) has its
  own CSS: when the page loads the two gold stems draw from the bottom and the leaves grow one pair at a time up
  to the top (about 1.5 s); after that the leaves shimmer softly in a slow wave (opacity and size, 4.8 s loop).
  Nothing moves with "reduce motion" switched on. The scales do not move. No JavaScript.
- Checked in the browser: grows, then shimmers. 176 checks passed.

### 2026-11-25 — Design "L" on phones
- Owner: "मोबाइल पर डिजाइन सही करो". Up to 640px, as in the canvas's LegalMobile:
  - header: ☰ at the left, then the logo and "LegalQuery Laws", the language menu at the right, all on one row
    (the language and ☰ were on a second row); the tagline is hidden and the search box sits under the row; below 360px
    the logo is hidden as well;
  - Act list: the filter box is full width and the table is tighter (14px, smaller padding), so the
    Sections column no longer runs off the screen;
  - footer: the link columns sit side by side.
- `site.css?v=32`. Checked at 320, 360, 375 and 414px (home, Act page, Act list, search, PDF page): one header
  row, no horizontal scroll, menu opens. 176 checks passed.

### 2026-11-25 — Final look: design "L" (navy header and footer)
- Owner: "L wala design final karke site par implement karo". Design "W" stays for the page body; from the
  design canvas (LegalHome / LegalAct / LegalMobile):
  - header: navy bar (#1b2a41) with a 3px gold rule (#c2a460), new logo `public/assets/img/logo.svg` (scales in a
    gold laurel), tagline "भारत के अधिनियम · The Acts of India", white search box with a gold Search button, menu
    links light grey-blue with the active one white and underlined in gold; language menu and ☰ button on navy;
  - footer: navy with a gold rule, logo + name, gold uppercase column titles, white links;
  - active Act/list tab underlined in gold; the big Search buttons (home, search, 404) navy; `theme-color` #1b2a41.
- `site.css?v=30`. Checked in the browser: home, Act page, Central Acts list, mobile 375px (menu opens, no
  horizontal scroll). The only console error is the old `/lib/icc-common.js` 404. 176 checks passed.

### 2026-11-25 — Translated section text removed again (owner)
- Owner: the translated content is not to be shown on the site. The example of the entry "Example: AI-translated
  section text on the Act page" below is removed: `Content::aiSections()` / `sectionKey()`, the `translations`
  view variable, the hidden `data-tr` headings and texts in `act-sections.php`, `provision-list.php`,
  `provision-body.php`, the "AI translation" note with "Show English", its `site.js` code, the `.tr-text` /
  `.tr-note` styles, the config comment and its 3 tests. Sections are English only, whatever language is picked.
  AI translations stay as PDFs ("PDF: हिन्दी (AI अनुवाद)", download page) as before. The `document.rag.json`
  files staged in `translations/` are not read by the site any more (left there; they come from the ledger).
  `site.css?v=29`, `site.js?v=17`. The language-menu changes of today (download page, hidden languages) stay.
- Checks: `php tests/run.php` → 176 checks passed; `php -l` all ok; `node --check` ok; browser, Post Office Act 2023
  with हिन्दी picked: section headings and Contents in English, no translated text or note in the page, PDF link
  still "PDF: हिन्दी (AI अनुवाद)"; download page menu English, हिन्दी only. Not committed.

### 2026-11-25 — No bot gets a PDF (.htaccess)
- Owner: Google or any bot must not download a PDF directly. `public/.htaccess` now answers 403 to crawlers
  (Googlebot, Bingbot …), scripts (curl, wget, python, headless browsers …) and requests with no User-Agent
  on `/pdf?…`, `/files/…` and any `.pdf`; a real `.pdf` file under `public/` is never served. People's
  browsers are not affected. README has the same rule for Nginx.
- Already in place: robots.txt disallows `/pdf` and `/files/`, PDFs and the download page are `noindex,
  nofollow`, and a PDF opens only after the sum question (PdfGate), so Googlebot got the question page, not the PDF.
- Tested with a throwaway Apache 2.4 (no PHP): Googlebot / curl / empty UA → 403 on those URLs, a browser
  UA → passes; Act pages and CSS unchanged for all. The PHP dev server (`router.php`) ignores .htaccess.

### 2026-11-25 — Language menu: languages without a PDF are hidden, not greyed
- Owner: on an Act page and on the download page of an Act PDF the header menu lists only the languages the Act
  has a PDF in (the others are removed from the menu, not disabled). Replaces "every language listed, the ones
  without a PDF disabled" of earlier today. Still always shown, also with one language; other pages list every
  language. A saved language that is not in the menu is kept for the next page. `site.js?v=16`.
- Checks: `node --check` ok; `php tests/run.php` → 179 checks passed; browser: Post Office Act 2023 page and its
  download page → only English, हिन्दी; Indian Ports Act → only English; home → all 11. Not committed.

### 2026-11-25 — PDF download page: language menu limited to the Act's PDFs
- Owner: on the download page the header language menu had every language enabled although the Act has PDFs in
  two. Now, on the download page of an Act PDF, the menu works as on the Act page: languages the Act has no PDF in
  are disabled, the menu shows the language of the PDF on the page, and picking another language opens that PDF's
  download page (official PDF first, else the AI translation). `FileController::info` gives `lang` / `ai`,
  `pdf-check.php` prints them (`data-pdf-lang`, `data-lang` on the "Other PDFs" links), `site.js?v=15`.
  Download pages of rules, notifications … (not Act PDFs) are unchanged: every language enabled, choice only saved.
- Checks: `php tests/run.php` → 179 checks passed; `php -l`, `node --check` ok; browser, Post Office Act 2023
  download page: only English and हिन्दी enabled (9 disabled), menu shows English; picking हिन्दी opens
  `/pdf?…&v=hi-ai` with the menu on हिन्दी. Not committed.

### 2026-11-25 — Example: AI-translated section text on the Act page (Post Office Act, 2023 in Hindi)
- Owner asked whether the translated content (not only the translated PDF) can be shown on the site, with one Act
  as an example. It can, for the translations that have the handover's RAG sidecar (`document.rag.json`, staged
  beside the PDF by `../translation-ledger`): it has the text per section. `Content::aiSections()` reads it
  (sections by number; heading split off the first line "3. Heading.-"); `act-sections.php`, `provision-list.php`
  and `provision-body.php` print the translated heading and text hidden beside the English (`data-tr`,
  `data-tr-orig`); `site.js` shows them when that language is picked in the header menu. Above the list: "हिन्दी (AI
  अनुवाद): this text was translated by AI. It is not official and not yet reviewed; the English text is the
  authoritative one. Show English" (the button switches this page to English and back). Same URL, same HTML for
  everyone; without JavaScript the English text shows. Text is escaped with `e()`, shown `pre-wrap`.
  `site.css?v=28`, `site.js?v=14`.
- Covered now: the 11 staged translations with a sidecar (9 Acts: Post Office 2023, RTI 2005 bn, Legal Services
  Authorities bn, Official Languages bn, Protection of Human Rights hi/bn, Sati (Prevention) hi/bn, CEI (Reservation
  in Admission) hi/bn, King of Oudh's Estate 1887 hi); in the whole handover 482 of 2,349 translations have one.
  The others have only page-wise text (`translation.json` → `pages[]`), which does not map to sections: not shown.
- Not done (owner to decide): schedules, long title / preamble, Act Details and footnotes stay English; footnote
  marks are plain digits in the translated text ("तारीख1"); the page title, description and JSON-LD stay English,
  so search engines see only the English text; the note above the list (the owner removed the PDF note from Act
  pages earlier today, this one is about the page text itself).
- Checks: `php tests/run.php` → 179 checks passed (new: no sidecar → none, sections by number, heading split, text
  kept raw for escaping); `php -l` all ok; `node --check site.js` ok; browser, Post Office Act 2023 with हिन्दी
  picked: 16 section headings (list and Contents) and 16 texts in Hindi, "PDF: हिन्दी (AI अनुवाद)", note shown,
  "Show English" brings the English back. Console: only the existing `/lib/icc-common.js` 404. Not committed.

### 2026-11-25 — No AI-translation note on Act pages
- Owner: removed "This is an AI translation: not official and not yet reviewed…" under the Act tab row (template,
  `site.js`, CSS). The AI link still says "(AI अनुवाद)" etc.; the download page is unchanged. `site.css?v=27`,
  `site.js?v=13`. Checks: `node --check` ok; `php tests/run.php` → 176 checks passed; Post Office Act 2023 page has no
  such text (curl). Not committed.

### 2026-11-25 — Download page of an AI translation: "unofficial translation" notice (owner)
- Above the question, in a yellow box: "Please note: you are about to open or download the Gujarati translation of
  this Act. It was made by AI: it is not official, not issued or certified by the Government of India, and not yet
  reviewed. Only the official PDF of the Act is the authoritative text." (`FileController::aiNotice`, language name
  from `Content::AI_LANGUAGES`; `info['notice']`, `templates/pdf-check.php`; CSS `.pdf-check-ai`, stylesheet `?v=27`).
  Official PDFs show no notice.
- Checks: `php tests/run.php` → 176 passed (new: notice text, Odia name); `php -l` ok; the `bn-ai` and `ta-ai`
  download pages show it (seen in the browser), the English PDF's page does not.

### 2026-11-25 — Language menu: every language listed, the ones without a PDF disabled
- Owner: the header menu lists every language again; on an Act page the languages the Act has no PDF in are
  disabled (greyed, tooltip "No PDF of this Act in this language"). The menu is always shown (also on pages without an
  Act PDF, where every language is enabled and the choice is only saved). Replaces the "only the page's languages,
  hidden under two" rule of the entry below. A saved language that is disabled on a page is kept for the next page.
  `site.js?v=12`.
- Checks: `node --check` ok; `php tests/run.php` → 173 checks passed; browser: Ahmedabad City Courts Act → English and
  ગુજરાતી enabled, 9 disabled; Indian Ports Act → only English enabled; home → menu shown, none disabled; Gujarati
  saved → Post Office Act shows English with English/हिन्दी enabled, the Gujarat Act then shows "PDF: ગુજરાતી".
  Not committed.

### 2026-11-25 — Language menu lists only the languages the page has a PDF in
- Owner: the header language menu shows only the languages of the Act's PDFs on that page (English + हिन्दी counts
  as both; AI translations count for their language), and is hidden when there are fewer than two (pages without an
  Act PDF, Acts with one language). A saved language that the page has no PDF in is kept for the next page; the page
  shows its first PDF (English). The "(no PDF in …)" note is gone. `site.js?v=11`.
- Checks: `node --check` ok; `php tests/run.php` → 173 checks passed; browser: Ahmedabad City Courts Act → English,
  ગુજરાતી; Post Office Act 2023 → English, हिन्दी; Indian Ports Act and home → menu hidden; Gujarati saved → Post Office
  Act shows English (Gujarati still saved), back on the Gujarat Act it shows "PDF: ગુજરાતી". Not committed.

### 2026-11-25 — One language menu for the whole site; "PDF: English" in the Act tab row
- Owner: the language choice is global, not inside the PDF box. Header (`partials/header.php`): a language menu
  (文A English / हिन्दी / State languages from `PDF_LANGUAGES`), kept in the browser (`localStorage` `lq-lang`,
  `site.js`); hidden without JavaScript. Pages stay the same for everyone, so caching and ETags are unchanged.
- Act pages: no PDF in the infobox any more (the "Download PDF" link and "文A N languages" menu of the entry below
  are gone). The PDF is at the right of the tab row as in design "W": "PDF: English". `partials/act-pdfs.php` prints
  every PDF of the Act (official first, AI last); `site.js` shows the official PDF in the chosen language (English +
  हिन्दी counts for English and Hindi), else the AI translation in it (with the "not official" note under the tab
  row), else the first PDF with "(no PDF in ગુજરાતી)". Without JavaScript the first PDF (English) shows.
  The thumbnail stays on the download page.
- `site.css?v=26`, `site.js?v=10`. Checks: `node --check site.js` ok; `php -l` all ok; `php tests/run.php` → 173
  checks passed; browser: Gujarati picked → Ahmedabad City Courts Act shows "PDF: ગુજરાતી", Indian Ports Act shows
  "PDF: English (no PDF in ગુજરાતી)", the choice is kept from page to page; Hindi picked → Post Office Act 2023 shows
  "PDF: हिन्दी (AI अनुवाद)" with the note; 375 px: tab row wraps, no sideways scroll. Not committed.

### 2026-11-25 — Act infobox: PDF language menu; thumbnail moved to the download page
- Owner: the PDF thumbnail is shown on the download page (`pdf-check.php`, beside the title, with the file name), not
  on the Act page. The infobox now has a "Download PDF" link (the first PDF) and, for more than one language, a
  language menu like an encyclopedia's ("文A 2 languages ▾") with one link per language (`partials/act-pdfs.php`). It is a
  `<details>` menu, so it works without JavaScript; each link goes to that PDF's download page as before.
- Act intro and infobox leave out the "NO MINISTRY" placeholder in the data ("administered by the Legal Department").
- `site.css?v=25`. Checks: `php -l` all ok; `php tests/run.php` → 173 checks passed; browser: Ahmedabad City Courts Act
  (Gujarat) menu opens with English and ગુજરાતી, its download page shows the thumbnail; Indian Ports Act shows only
  "Download PDF". Not committed.

### 2026-11-25 — New look: design "W" (plain reference-site style)
- Owner chose design "W" from the design canvas. `public/assets/css/site.css` rewritten (same class names): white page,
  grey rules, blue links, serif headings (Georgia stack), system fonts only (nothing loaded from outside).
- Header (`partials/header.php`): small grey scales mark + "LegalQuery Laws / The Acts of India", search box with a
  Search button, menu on the same bar (menu button and full-width search under 900px). Footer: plain grey text.
- Home: welcome box with the counts, search, then four plain boxes (Recent Central Acts, years, categories, what an
  Act page has). The coloured stat cards are gone.
- Act pages (`partials/act-head.php`): title, "From LegalQuery Laws · Source: India Code…", a one-line introduction
  made from the Act's own fields, and an infobox on the right (PDF thumbnail + language links as before, Act No.,
  enacted, in force from, jurisdiction, ministry, sections, schedules). The ministry box is gone (now in the infobox).
  Sections tab: a sticky "Contents" column with this page's sections (links to `#slug`, hidden under 900px); each
  section row shows "[show]" / "[hide]".
- Act list (`act-list.php`): filter bar above a table (Year, Act No., Short title, Sections) instead of the filter
  sidebar and Act cards. Search and category pages keep `act-rows`, restyled as plain rows. `/states`: plain link list.
- CLAUDE.md rule 5 updated with the owner's decision. `site.css?v=24`, `theme-color` #ffffff. No URL changed.
- Checks: `php -l` all ok; `php tests/run.php` → 173 checks passed; running server: /, /acts/central, /acts/central/2025,
  filter, Indian Ports Act (sections, schedules, details), /states, /acts/kerala, search, /rules → 200, missing page →
  404; no PHP warning on the first 400 Central Act pages and 50 Kerala Act pages; looked at home, Act page (section
  opened), list, search, /states in the browser at desktop width and home + Act page at 375 px (no sideways scroll).
  Console: only the existing `/lib/icc-common.js` 404 ("ICC is not defined"), not from this change. Favicon and share
  image not changed. Not committed.

### 2026-11-25 — AI translations from the owner's handover, through a ledger; 4 test translations
- Owner's handover package `IndiaCode_Translation_Handover_2026-11-24` (2,349 AI drafts, 2,202 source PDFs) is
  the source of AI translations now, not `../indiacode/translations` (an older copy: 45 PDFs differ from the handover).
- New project `../translation-ledger` (`ledger.py`, Python stdlib, `ledger.sqlite`): records every handover
  translation with its key `indiacode-translation:<id>:v<ver>`, source `record_uuid`, `act_id` and hashes, and
  its status received → matched / unmatched / blocked → staged → live (history in `events`). `stage` copies
  PDF + `translation.json` (+ RAG as `document.rag.json`) into this site's `translations/` (not in git) and
  checks SHA-256. See its README.
- Ledger after import + match: 532 matched, 1,224 unmatched (Act not in content/ yet), 554 blocked State/UT
  (state-language scope review), 39 blocked AI Hindi (official Hindi PDF exists). 4 staged for testing:
  198 hi The Post Office Act, 2023 (Codex); 2929 bn The Official Languages Act, 1963 (Codex);
  385 hi The King of Oudh's Estate Act, 1887 (Claude); 932 mr Extending certain Acts to Straits Settlement, 1852 (Claude).
- Code: `Content::AI_LANGUAGES` now has all 10 handover languages (`mr-ai`, `gu-ai` … with labels in their own
  script, `ActPdf::AI_LABEL`); the newest `vN` folder is used (the handover has v2 translations), not only `v1`.
- `config.local.php` (this machine): `ai_translations_dir` = `__DIR__ . '/translations'`, no checksum list. The
  2026-11-21 demo Acts (Bengal Indigo, DPDP) are not staged, so they no longer show their AI PDFs.
  `dev.sh`: `TRANSLATIONS_DIR` default `./translations` (PostgreSQL load not run in this change).
  Not on the server: going live still needs the owner's OK (then rsync `translations/` and `ledger.py live`).
- Checks: `verify_package.py` → passed (10,548 files); `php tests/run.php` → 173 checks passed (new: Tamil/Marathi
  labels, newest version, unknown language ignored); `php -l` all ok; `bash -n dev.sh` ok; running server: the 4 Act
  pages show हिन्दी / বাংলা / हिन्दी / मराठी (AI …) buttons + note, `v=mr-ai` download page 200, `v=ta-ai` (not staged)
  404; each URL resolves to the staged file whose SHA-256 equals the ledger's; `pdftotext` finds no India Code text in
  the 4 PDFs. The PDF question itself was not answered in this check. Labels for the 8 new languages need a native check.
- 50 more staged (54 in all, 21 Acts; hi 13, bn 11, ml 5, or 5, pa 5, kn 4, mr 4, gu 3, ta 2, te 2): the first matched
  ones of each language and AI, more of hi/bn. 12 drafts carry the source page footer "India Code" in their PDF text
  (6, 7 DPDP gu/kn; 90–98 Senior Citizens Act; 100): the ledger now blocks those (`india_code_text_in_pdf`, checked with
  `pdftotext` before every copy; new `ledger.py unstage`), the 11 already copied were removed and replaced. Checks: all
  54 Act pages list their `{lang}-ai` button, each `/pdf?…&v={lang}-ai` → 200, each staged PDF's SHA-256 = the
  ledger's, no "India Code" text in any of the 54; `php tests/run.php` → 173 passed. Not committed, not on the server.

### 2026-11-23 — /states: States first, then Union Territories; slugs for Gujarat, Nagaland, Andhra Pradesh
- `/states` lists two groups like India Code, each A to Z with a count: States, then Union Territories
  (`Jurisdiction::isUnionTerritory()`, list of the 8 UTs; owner, 2026-11-23). New CSS `.jur-group`, `.jur-divider`;
  stylesheet `?v=20`.
- `content/slugs.json`: 749 new slugs from the exports up to Andhra Pradesh (3,597 Acts); 0 changed or removed
  (compared as JSON with the last commit).
- Checks: `php tests/run.php` → 145 passed (new: union territories); `php -l` all ok; `/states` → 200 with
  "States 14" and "Union Territories 7".

### 2026-11-21 — dev.sh: export also loads PostgreSQL (legalquery-ai)
- Owner: after `export-web` and after translating in the indiacode project, the data should reach PostgreSQL.
- `dev.sh export` and `dev.sh` now run, after the export, `lqai load` (content/ → chunks) and `lqai load-translations`
  (indiacode AI translations) of `../legalquery-ai` (`LQAI_DIR`, `TRANSLATIONS_DIR`); new `./dev.sh load` runs only
  those. Not set up / no translations folder → a note, no failure; `LQAI_SKIP_LOAD=1` skips. In `./dev.sh` a failed
  load warns and still serves. The site does not read PostgreSQL; nothing else here changed.
- Checks: `bash -n dev.sh` → ok; `./dev.sh load` → 3,095 Acts unchanged, 2,135 translations unchanged, 0 written,
  3.6 s; `LQAI_SKIP_LOAD=1`, `LQAI_DIR=/nonexistent`, `TRANSLATIONS_DIR=/nonexistent` → skip notes; `./dev.sh bogus` →
  usage, exit 2. The scraper export itself was not run.

### 2026-11-21 — No AI Hindi PDF when the Act has an official Hindi PDF (owner)
- Owner: if the Act already has a Hindi PDF, don't show the Hindi AI translation. `ActPdf::choices()` drops the AI Hindi
  when an official choice is Hindi or English + Hindi (`bi`); its `-hindi-ai.pdf` URL is then 404. AI Bengali is unaffected.
- Demo now has two Acts (`config.local.php`, this machine only): Bengal Indigo Contracts Act, 1836 (official Hindi →
  English, हिन्दी, বাংলা (AI অনুবাদ)) and Digital Personal Data Protection Act, 2023 (`a2023-22.pdf`, sha256 `dda561b9…`,
  English only → English, हिन्दी (AI अनुवाद), বাংলা (AI অনুবাদ)). Its two PDFs (42 and 41 pages): no India Code URL or text.
- Checks: `php -l` → no errors; `php tests/run.php` → 149 checks passed (3 new); browser: both popups show those buttons +
  the note; POST with the question's answer → DPDP `-hindi-ai.pdf` 200 (307,934 bytes), `-bengali-ai.pdf` 200 (288,797),
  Bengal Indigo `-bengali-ai.pdf` 200, `-hindi-ai.pdf` 404.

### 2026-11-21 — Demo: AI Hindi + Bengali PDFs of one Act in the PDF popup (owner)
- Owner asked for one example to test: the AI translations made in the indiacode project
  (`/Users/user/Nikesh/indiacode/translations/<source sha256>/<lang>/v1/document.pdf`) as extra buttons in the Act's PDF
  popup. Example Act: The Bengal Indigo Contracts Act, 1836 (`a1836-10.pdf`, sha256 `74e92d5f…`), which also has an
  official Hindi PDF, so the popup shows English, हिन्दी, हिन्दी (AI अनुवाद), বাংলা (AI অনুবাদ).
- Off by default. New settings `ai_translations_dir` ('' = off) and `ai_translations_only` (source checksums; [] = all) in
  `config.php`; turned on only in this machine's `config.local.php` for that one checksum (remove on the server).
- `Content::act()` adds `ai_files` (read only from that folder, never from `content/`); `ActPdf::choices()` adds them after
  the official PDFs with `ai => true`, URLs `/acts/central/{act}-hindi-ai.pdf`, `{act}-bengali-ai.pdf`; the popup shows
  "AI translations are not official and not yet reviewed…" and the tooltip says so; JSON-LD lists only official PDFs.
- Checked the two PDFs: no India Code URL or text (`pdftotext`). Translation files say "AI draft; independent linguistic and
  human legal review pending".
- Temporary: the lasting way is the scraper's export-web copying translations into `content/` (+ `docs/CONTENT.md`); then
  this reads `act.json` instead of the indiacode folder. **Pending owner decision** before going live.
- Checks: `php -l` on all PHP files → no errors; `php tests/run.php` → 146 checks passed (6 new); browser on
  `/acts/central/bengal-indigo-contracts-act-1836`: popup shows the 4 buttons and the note; POST with the PDF question's answer
  → `-hindi-ai.pdf` 200 application/pdf 86,767 bytes, `-bengali-ai.pdf` 200 91,787 bytes, `-hindi.pdf` 200 (unchanged),
  `-xyz-ai.pdf` 404.

### 2026-11-21 — Fix (2nd try): Chrome viewer download failed every time ("Check Internet connection" → Resume)
- Owner's screenshot: every download from the viewer fails with "Check Internet connection", "Resume" works. So Chrome
  sent no request at all: for a PDF that came from a POST it downloads only from its own cache, and the PDF was `no-store`.
  (The previous entry's cause, a re-sent POST, was not what Chrome does here; that change is harmless and stays.)
- Fix: the PDF sent after the question is `Cache-Control: private, max-age=300` (PASS_TTL) instead of `no-store`:
  shared caches (Cloudflare, proxies) still never keep it; the question page stays `no-store`.
- Checks: `php -l` → no errors; `php tests/run.php` → 144 checks passed; curl: answer POST → `private, max-age=300`,
  application/pdf; GET without answer → question page `no-store`. Owner checked in Chrome (2026-11-21): the viewer's
  download now works the first time, no "Resume".

### 2026-11-21 — Fix: first download from Chrome's PDF viewer failed, "Resume" worked
- Owner: `/acts/central/bengal-indigo-contracts-act-1836-hindi.pdf` opens; download first fails, Chrome shows "Resume",
  which then downloads. Cause: Chrome's viewer downloads a PDF that came from a POST by re-sending the same POST; its
  token is already used, so the server answered 422 with the question page. "Resume" sends a GET, which the pass let through.
- Fix (`FileController::send`): a valid pass cookie for that URL now sends the PDF for POST as well as GET.
- Checks: `php -l` → no errors; `php tests/run.php` → 144 checks passed; curl: answer POST → PDF 282,930 bytes; same POST
  again with the cookie → 200 PDF, identical to `H1836-10.pdf` (was 422 HTML); same POST without cookie → 422; cookie on the
  English PDF → 422; GET without cookie → question page. Browser pane (fetch): first POST and repeat POST both 200 PDF.

### 2026-11-21 — Fix: PDF opened but could not be downloaded (PDF question on)
- Owner: `/acts/central/central-armed-police-forces-general-administration-act-2026.pdf` opens but does not download.
  Cause: with `pdf_captcha` on, the PDF comes only as the answer to a POST; the browser's PDF viewer downloads by
  GETting the same URL again (`no-store`, nothing cached), and a GET gets the question page (HTML), so the download fails.
- Fix: a right answer also sets a pass cookie `lq_pdf_pass` (`PdfGate::pass()`: expiry + HMAC over the URL path), HttpOnly,
  SameSite=Lax, Secure on HTTPS requests, `Path` = that one PDF URL, 5 minutes (`PASS_TTL`). A GET of that URL (also
  `?download=1`) with a valid pass sends the PDF. Other PDFs still ask; the popup still asks every time.
- Checks: `php -l` → no errors; `php tests/run.php` → 144 checks passed (4 new: same path, other path, expired, forged);
  curl: GET without answer → HTML; POST with answer → PDF inline + `Set-Cookie`; then GET and `?download=1` → 200
  application/pdf, 288,181 bytes, `attachment` for download, identical to `A2026-9.pdf`; same cookie on another Act's PDF
  → question page. Browser pane: download URL HTML before the answer, PDF `attachment` after it.

### 2026-11-19 — PDF name under the thumbnail: 12 characters + "…" (owner)
- Owner: like India Code, the name under the Act's PDF thumbnail shows at most 12 characters, then "…"
  (`custom_tariff_act,_1975.pdf` → `custom_tarif…`); the full name is in the tooltip.
- `short_text()` in `src/helpers.php` (counts whole letters, so Devanagari vowel signs stay with their consonant);
  used in `partials/act-pdfs.php` (link and popup thumbnail).
- Checks: `php tests/run.php` → 139 checks passed; browser: `/acts/central/customs-tariff-act-1975` shows `custom_tarif…`.

### 2026-11-19 — Search: Central / State chip on each result
- Owner asked why "The Epidemic Diseases Act, 1897" gives 2 results: they are two India Code records, Central (12
  sections) and Puducherry (1 section, Regulations; not linked as a copy). Search covers every jurisdiction, but the row
  did not say which, so it looked like a duplicate.
- `partials/act-rows.php`: optional `show_where` → green chip first ("Central", "Puducherry"; `Jurisdiction::short()`),
  used by `search.php`; section/schedule hits show "Act title · Puducherry". "1 sections" → "1 section" (also schedules
  and the `/states` cards).
- Checks: `php tests/run.php` → 138 checks passed; `php -l` clean; browser: the search shows "Central" and "Puducherry"
  chips and "1 section".

### 2026-11-19 — SEO audit: every indexable page in the sitemap
- Owner asked that every page be indexable and SEO-correct. Crawled all sitemap URLs on localhost:8800 (script: status,
  canonical, robots, title, description, h1, internal links). Before: 5,204 URLs, all 200 with a self canonical and no
  noindex, but **1,258 indexable pages were missing**: Sections `/page/N` of long Acts (section text is only there) and
  tab pages 2+.
- `SeoController`: sitemap adds every Sections page and every page of each linked-document tab (counts from `act.json`,
  read only for Acts that have tabs; sitemap still ~0.07 s). Now 7,197 URLs; 0 indexable pages missing.
- `ActController`: State/UT Act titles and descriptions add the State/UT name (68 duplicate titles/descriptions between a
  Central Act and its UT copy are gone); Act Details gets its own description (summary / long title if longer than the
  title, else Act No., enactment date, ministry) instead of repeating the Sections page's (≈180 duplicates gone, 60
  too-short ones gone); all Act page descriptions capped at 160 characters.
- Left (content, see Pending): 18 duplicate descriptions, 9 duplicate titles, broken links in the Insurance Act text.
  ~4,900 titles are over 70 characters only because the Act names are long (Google shortens them; not an error).
- Production (act.legalquery.in) checked: robots.txt and sitemap.xml 200 with https URLs, http → https 301, uppercase /
  trailing slash → 301, canonical https. It has the old code until deployed.
- Checks: `php tests/run.php` → 137 checks passed; `php -l` clean; crawl after the change: 7,197 sitemap URLs all 200,
  canonical = URL, no noindex, 0 indexable pages outside the sitemap.

### 2026-11-19 — Readable Act PDF URLs (owner)
- New `src/ActPdf.php`: names an Act's PDFs from `pdf_choices()`: `{act}.pdf` (English/unknown), `{act}-hindi.pdf`,
  `{act}-english-hindi.pdf`, then `-english-2`, `-hindi-2` …; `ActPdf::candidates()` maps a URL name back to Act slugs.
- `Router`: `/acts/{jurisdiction}/{name}.pdf` → `FileController::actPdf`. `App`: POST allowed there (PDF question).
  `Url::actPdf()`, `Url::pdf()` (readable URL when the file has one, else `/files/…`).
- `FileController`: shared `send()`; Act PDFs send `Content-Disposition` `{act}.pdf`, ETag + `max-age=86400` (no-store
  behind the question), canonical = the original Act's PDF URL for copies (`original`). GET of an Act's own PDF at
  `/files/{sha256}/…` → 301 to the readable URL (keeps `?download=1`); linked documents keep `/files/`.
- Templates `act-head`/`act-pdfs` (thumbnail + popup), `pdf-menu`, `file-links` and JSON-LD `encoding.contentUrl` use the new URLs.
- `docs/URLS.md` updated. No `content/` or scraper change.
- Note: with `pdf_captcha` on, crawlers still can't open the PDFs; the SEO gain needs the owner's call on the question.
- Checks: `php tests/run.php` → 137 checks passed; `php -l` on all PHP files clean. On localhost:8800 (curl): Act page
  posts to `/acts/central/kerala-alteration-of-name-act-2026.pdf`; old `/files/b00c…/A2026-18.pdf?download=1` → 301 to
  `…act-2026.pdf?download=1`; after the right answer: 200 `application/pdf`, `attachment; filename="kerala-alteration-of-name-act-2026.pdf"`;
  `national-service-act-1972` shows `.pdf`, `-english-2.pdf`, `-hindi.pdf`; unknown names 404; UT copy
  (`andaman-and-nicobar-islands/right-of-children-…-2009.pdf`) sends canonical to the `central` PDF URL.

### 2026-11-18 — PDF language popup instead of direct buttons (owner)
- Owner: no language buttons on the page; clicking the PDF shows a popup beside it with Hindi / English … options.
- New `partials/pdf-menu.php` (the popup, `<details class="pdf-pick">`, works without JS). `act-pdfs.php`: one PDF →
  thumbnail link as before; more → the thumbnail opens the popup. `file-links.php`: one PDF → "View" (+ ⤓); more →
  "View ▾" opens the popup (⤓ per language inside it outside tables).
- `site.js?v=4`: popup placed beside the PDF (left, else right, else below; `position:fixed` so scrolling tables don't
  clip it), one open at a time, closes on outside click, Escape, scroll, resize or after a pick. `site.css?v=14`.
- Checked: `node --check` ok, `php -l` ok, `php tests/run.php` → 115 checks passed. Browser (local, desktop + mobile):
  fugitive-economic-offenders-act-2018 thumbnail → popup English / हिन्दी with the right PDF links; Escape and an
  outside click close it; disaster-management-act-2005/notifications "View ▾" → popup beside the button. Not committed.

### 2026-11-18 — PDF language buttons (owner: show all available languages)
- `pdf_choices()` (`src/helpers.php`, replaces `english_file()`): every archived PDF once (by sha256), ordered English,
  English + हिन्दी (`bi`), हिन्दी, PDF (`unknown`); same label twice → "English 2". A file labelled `en` whose name is
  `H<digit>…` (H2018-17.pdf, h201949.pdf) is shown as हिन्दी: display-only workaround for the export's mislabel.
- Act head (`partials/act-pdfs.php`): thumbnail opens the first PDF; with more than one, a button per language below it,
  each opening its PDF in a new tab. Document tables/lists (`partials/file-links.php`): one PDF → "View" as before;
  more → one button per language (+ ⤓ download each outside tables). CSS `.pdf-langs`, `site.css?v=13`.
- Checked: `php -l` ok; `php tests/run.php` → 115 checks passed. Browser (local): fugitive-economic-offenders-act-2018
  shows English + हिन्दी, both links 200 `application/pdf`; disaster-management-act-2005/notifications shows two
  "English + हिन्दी" buttons for a document with two bilingual PDFs; mobile layout ok. Not committed.

### 2026-11-18 — Production on act.legalquery.in; PDF icon cache-busting
- Server: code pulled (fb2d078), `content/` (format 3) uploaded with FileZilla; `systemctl restart php8.3-fpm` was
  needed (opcache kept the old format-2 code → 503 "No content yet"). Now 200. act.legalquery.in's vhost uses
- `partials/act-pdfs.php`: icon URL is `pdf-thumb-demo.svg?v=2`, so browsers drop the cached old icon (SVGs are
  cached 7 days by `.htaccess`). Bump `?v=` whenever the image changes.
- After a deploy: restart `php8.3-fpm` if pages still show old behaviour.
- Checked: `php -l` ok; `php tests/run.php` → 110 checks passed.

### 2026-11-18 — "Original Act" note removed (owner: "Hata dena")
- The note is gone (`partials/act-head.php`, `.original-note` CSS removed, `site.css?v=12`). State copies still show the
  original's sections, PDF and section documents; the page no longer says where they come from. `act.json` still
  carries `original` (unused by the templates).

### 2026-11-18 — "Original Act" note on State copies of a Central Act
- `act.json` field `original` (scraper export, see `docs/CONTENT.md`): for a State Act that copies another Act,
  the page shows the original's sections, PDF and section documents (as India Code does) and the note
  "Original Act: <link> (Central (Parliament of India)). The Act PDF, sections and … are shown from the original Act,
  as on India Code." (`templates/partials/act-head.php`, `.original-note`, `site.css?v=11`).
- 5 Andaman Acts have it (e.g. `/acts/andaman-and-nicobar-islands/motor-vehicles-act-1988`: 257 sections, `A1988-59.pdf`).
- `php tests/run.php` passed. Committed (with the removal below).

### 2026-11-17 — States page and menu entry (owner: "Keep, add States menu")
- Owner review of the pilot States: keep State data as India Code has it (Central Acts listed under a UT, repeated
  titles, "NO MINISTRY"); add a way to reach the States.
- New `/states` (`src/Controller/StatesController.php`, `templates/states.php`, `Url::states()`, route): every
  exported jurisdiction except `central`, A to Z, with Acts and sections from `manifest.json`; 404 while none exists.
- Menu "States" and footer "States and UTs" (only when a State is exported). State list/Act pages highlight
  "States" (`Controller::nav()`); breadcrumbs and JSON-LD are Home › States › <State> Acts › … (Central unchanged).
- Sitemap lists `/states`. `tests/run.php`: 2 new checks, **110 passed**.
- Checked on localhost:8800: `/states` shows Andaman and Nicobar Islands (70 Acts · 179 sections) and Ladakh (64);
  Ladakh list and Act details: menu "States", crumbs Home › States › Ladakh Acts › …; Central list/Act unchanged.
- Docs: `docs/URLS.md`.

### 2026-11-16 — First States on the site: Andaman and Nicobar Islands, Ladakh (scraper pilot)
- Content re-exported with `andaman-and-nicobar-islands/` (70 Acts) and `ladakh/` (64); `slugs.json` +134 Acts,
  +179 sections, 0 changed. `/acts/andaman-and-nicobar-islands` and `/acts/ladakh` work (every page and tab 200).
- `templates/act-list.php`: a State list says "Acts of <State> as listed on India Code, with sections where India
  Code has them …" (was "Acts of Parliament …" on every list).
- `templates/partials/provision-list.php`: an Act with neither sections nor a PDF says "India Code publishes no
  section-wise text and no PDF for this Act." (1 Ladakh Act).
- `php tests/run.php` 108 checks passed. Not committed.
- How States are reached: done 2026-11-17 (`/states`, menu entry).

### 2026-11-16 — Content format 3: one folder per jurisdiction, split file index (scraper Phase 5d step 2)
- `src/Content.php`: `FORMAT = 3`; PDFs found through `files/<first two hex chars>.json` (not `files.json`);
  new `jurisdictions()` (code => name from `manifest.json`) and `counts($code)`.
- `src/Jurisdiction.php`: `register()` adds the exported States/UTs (label "<Name> Acts", details row "<Name>");
  `central` keeps its names. `src/App.php` registers them before routing, so `/acts/<code>/…` works for every
  exported jurisdiction and is a 404 for any other.
- `src/Controller/HomeController.php`: home section/schedule counts from `jurisdictions.central` (home stays
  Central-only); before, they were the manifest totals.
- Fixtures moved to format 3 (`files/b0.json`, manifest `jurisdictions`). `tests/run.php`: 7 new checks (shard
  lookup, old `files.json` not read, registered codes, State routes, unregistered State 404). **108 checks passed**.
- Docs: `docs/CONTENT.md` (format 3), `docs/URLS.md` (State URLs; no States page yet), `CLAUDE.md`, `README.md`.
- Content re-exported (848 Central Acts): every Central JSON identical except `format`, `slugs.json` unchanged;
  checks on localhost:8800 in the scraper's CHANGELOG. Committed on `main`.
- Open (owner): how States appear on the site (home, menu, a States page).

### 2026-11-16 — Committed as `6e374b9` (branch `central-complete-2026-11-16`, fast-forwarded into `main`)
- The 2026-11-16 entries below (jurisdiction-generic code, `SITE_URL`, `partial` Acts) and `content/slugs.json`
  (848 Acts). Left out: the untracked root `.htaccess` (2026-11-04, redirects to `public/`); it is not described
  in this file, so the owner decides.

### 2026-11-16 — `partial` Acts shown (owner: "Show them"); content re-exported with all 848 Central Acts
- The scraper's `export-web` now also exports `crawl_status = 'partial'` Acts (contract: `docs/CONTENT.md`; `CLAUDE.md`
  rule updated). 21 Acts: 18 with no section text on India Code (`sections` empty; text only in the PDF) and 3 with
  schedules that have no text (CPC 1908, Bankers’ Books Evidence 2026, Coal Bearing Areas 1957).
- `templates/partials/provision-list.php`: an Act with no sections says "India Code publishes no section-wise text for
  this Act. The full text is in the Act's PDF." (was "No sections yet."); no schedules → "No schedules.".
- `src/Controller/ActController.php`: meta description "… – full text in the official PDF." when there are no
  sections (was "0 sections with full text …").
- Checked (localhost:8800): home "848 Central Acts"; all 21 pages 200, 18 show the note, their PDFs 200 `%PDF-` with
  matching SHA-256; the 3 Acts' schedule tabs show "No text is published for this schedule." (5 + 2 + 1); slugs:
  0 of 36,814 changed, 37,067 now; `php tests/run.php` 101 checks passed. Not committed.

### 2026-11-16 — `SITE_URL` override for local serving
- The local `config.local.php` is the production copy (`site_url` = `https://indiacode.legalquery.in`), so locally the
  CSS/JS loaded from the live domain (unresolvable here: unstyled pages). `config.php` now lets a `SITE_URL`
  environment variable (`http(s)://host[:port]`, no path) replace `site_url`. `./dev.sh serve` sets it to the URL it
  serves (LAN URL with `HOST=0.0.0.0`, which fixes the note of 2026-11-04); `.claude/launch.json` sets
  `http://localhost:8800`. The server doesn't set it, so production is unchanged.
- Checks run: `php -l config.php` ok, `bash -n dev.sh` ok; preview on :8800: home styled, every loaded resource from
  `http://localhost:8800`.

### 2026-11-16 — Jurisdiction-generic code (no URL or page change)
- New `src/Jurisdiction.php`: the known jurisdictions (only `central`) with their names ("Central Acts",
  "Central (Parliament of India)") and the router pattern. `Jurisdiction::DEFAULT` = `central`.
- `Content`: `acts()`, `act()`, `provision()`, `tab()`, `years()`, `categories()`, `searchRows()` take the jurisdiction
  first and read `<code>/…`; an unknown code reads as empty; Act rows carry `jurisdiction` (the folder read).
- `Url::actList($j, $year)`, `Url::act($j, $slug, $tab)`, `Url::provision($j, $act, …)`; `Router` matches
  `/acts/(?<jurisdiction>central)…` and passes it to `ActListController`/`ActController`. Labels in the Act list, Act
  header breadcrumb, menu, footer and Act Details row come from `Jurisdiction`.
- Home and category pages (`/rules` …) stay on `Jurisdiction::DEFAULT`; search and the sitemap loop over every
  jurisdiction. Unused `Controller::crumbs()` (hard-coded "Central Acts") removed.
- Docs: `docs/URLS.md`, `docs/CONTENT.md`.
- Checks run: `php -l` on all PHP files clean; `php tests/run.php` → **101 checks passed** (7 new: unknown jurisdiction
  routes/reads, Url builders, `Jurisdiction`). 87 URLs (home, lists, year, `?search`/`?year`, categories, search,
  sitemap, robots, 9 Acts with every tab, details, schedules, page 2, old section/schedule URLs, 404s) fetched before
  and after on a local `php -S`: same status and `Location` for all, **all 87 bodies byte-identical**; no PHP warnings
  in the server log. Not checked in the browser (no visible change). Not committed.

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
