# URLs

All URLs are built in `src/Url.php` and matched in `src/Router.php`.
`central` in `/acts/central…` is a jurisdiction code: `central`, or a State/UT code exported by the
scraper (`/acts/kerala/…`, `/acts/andaman-and-nicobar-islands/…`; owner, 2026-11-16; codes listed in
`content/manifest.json`, see docs/CONTENT.md). Every `/acts/central…` URL below works the same for those
codes; a code that is not exported is a 404. Home and the category pages (`/rules` …) show Central Acts only;
search covers every jurisdiction. `/states` lists the exported States/UTs (owner, 2026-11-17); State pages
highlight "States" in the menu and their breadcrumb is Home › States › <State> Acts › ….

| URL | Page |
|---|---|
| `/` | Home: search, counts, latest Acts, years, categories (`/acts` → 301 here) |
| `/states` | States and Union Territories on the site, A to Z, with Act/section counts (404 while none is exported); in the menu and footer only when one is |
| `/acts/central` · `/acts/central/page/2` | All Central Acts |
| `/acts/central/2020` · `/acts/central/2020/page/2` | Central Acts of one year |
| `/acts/central?search=tax` | Filtered list (`noindex`); the year dropdown's `?year=` → 302 to the year URL |
| `/rules` · `/rules/page/2` | Central Acts that have Rules, newest first, each linking to its tab. Same for `notifications`, `regulations`, `orders`, `ordinances`, `statutes`, `circulars`, `appendices`, `amendments` (404 when no Act has it) |
| `/acts/central/{act}` · `…/page/2` | Act page, Sections tab (sections paged) |
| `/acts/central/{act}/schedules` | Schedules tab (404 when the Act has none) |
| `/acts/central/{act}/details` | Act Details tab (metadata, summary, history) |
| `/acts/central/{act}/rules` · `…/page/2` | Linked-document tab: `rules`, `notifications`, `regulations`, `orders`, `ordinances`, `statutes`, `circulars`, `appendices`, `amendments` (404 when empty) |
| `/acts/central/{act}/section-2a` | **301** to the section inside the Act page: `/acts/central/{act}#section-2a` (or `…/page/N#…` when it is on page N). Sections have no page of their own (owner, 2026-11-03) |
| `/acts/central/{act}/first-schedule` | **301** to `/acts/central/{act}/schedules#first-schedule` (same rule) |
| `/pdf?j={jurisdiction}&a={act}` · `…&v=hi` | An Act's own PDF (owner, 2026-11-24): one URL for its **download page** and the PDF. No `v` = the Act's first English PDF (or unknown language); else `v` = its language code (`hi`, `bi` English + Hindi, `gu`, `ml`, `te` … from `PDF_LANGUAGES`), `-2`, `-3` for a second PDF of a language (`en-2`, `hi-2`); AI translation (demo, only with `ai_translations_dir` set; owner 2026-11-21) `hi-ai` (only when the Act has no official Hindi or English + Hindi PDF), `bn-ai`, served from the indiacode project's `translations/` folder, listed after the official PDFs with a "not official" note, never in JSON-LD (its old demo name `{act}-hindi-ai.pdf` → 301 too). `&download=1` downloads (file name = the old readable name, `{act}-gujarati.pdf`). No `v` and no English PDF → **301** to the first PDF. Built by `Url::actPdf` / `ActPdf::choices`. Query strings here are an exception to the path-only rule (owner, 2026-11-24): every PDF, its translations to come and linked documents use the same `/pdf` entry point |
| `/pdf?j=…&a=…&d={tab}&f={file}` | A linked document's PDF (rules, notifications …), same download page. `d` = the tab that lists it, `f` = its file name on disk without `.pdf`, lowercased; a different file with the same `f` in that tab gets its folder in front (`notifications-…`, `pdf-…`) (`Content::tab`) |
| `/pdf?…` in any other spelling | Parameters in another order, other case or extra parameters (`utm_…`) → **301** to the one form above (order `j`, `a`, `d`, `f`, `v`, `download`). Unknown Act, language or file → 404 |
| `/acts/central/{act}.pdf` · `-hindi.pdf` · `-gujarati.pdf` · `-english-2.pdf` | Old readable PDF URLs (2026-11-19 to 2026-11-24) → **301** to the `/pdf` URL, `?download=1` kept (a POST, an answer from an old page, gets a **307** so the answer is kept). State-language PDFs' older names (`-english-2`, `ActPdf::moved`) go straight to their own `/pdf` URL |
| `/files/{sha256}/{name}.pdf` | Old PDF URLs by checksum → **301** (POST **307**) to the PDF's `/pdf` URL: an Act's own PDF, or a linked document of the Act whose folder holds it. A file with no `/pdf` URL (none known) is still served here |
| `/search?q=…` · `/search/page/2?q=…` | Search (`noindex`) |
| `/sitemap.xml`, `/robots.txt` | Built from `site_url` in config. `robots.txt` disallows `/search`, `/pdf` and `/files/`. The sitemap lists every indexable page: home, `/states`, every page of each Act list, year list and category, and per Act its Sections pages (all `/page/N`, since section text is only there), Schedules, Details and every page of each linked-document tab (counts from `act.json`). Not listed: search, filtered lists, PDFs, redirects. Above 50,000 URLs `/sitemap.xml` becomes an index of `/sitemap-1.xml`, `/sitemap-2.xml`, … (404 until then) |
| `/favicon.ico`, `/favicon.png` | Site icon (static files in `public/`) |

Rules:

- Lowercase, words joined by `-`, no IDs or UUIDs, no trailing slash. Uppercase, trailing slash,
  `//` and `/index.php` get a 301 to the clean URL.
- Pagination is in the path, 1-based. Page 1 is the plain URL; `…/page/1` → 301. A page past
  the last one, `/page/0` and `/page/01` are 404s.
- Every page prints a canonical link (from `site_url`); paged lists print `rel="prev"`/`rel="next"`.
- A section or schedule is linked as `Url::provision()`: its Act's Sections page N or Schedules tab plus
  `#<slug>`; the page script opens that block. The sitemap lists no section/schedule URLs.
- Slugs never change once published (see `CONTENT.md` › Slugs).
- Titles and descriptions of a State/UT Act's pages add the State/UT name (`The X Act, 2005 – Ladakh – Sections and full
  text`), so a Central Act listed under a UT doesn't share them with the Central page. Act Details uses the summary or
  long title (if it says more than the short title), else the Act's facts: never the Sections page's description.
- SEO in the `<head>` (`templates/layout.php`): title, description, canonical, prev/next, Open Graph
  and X card (image `share_image` in config), favicon. JSON-LD from `src/Seo.php`: `WebSite` +
  search box (home), `Legislation` (Act page and Act Details) and `BreadcrumbList` (printed by
  `partials/crumbs.php`).
- HTML pages send an `ETag` (304 on `If-None-Match`); PDFs send `Link: rel="canonical"` to the URL
  without `download=1`. `/pdf` PDFs can change with an export, so they send `max-age=86400` and an `ETag` (the
  checksum); `/files/…` PDFs served there are immutable (the checksum is in the URL). With `pdf_captcha` on, every PDF only
  opens after the question, so search engines cannot read the PDFs; the question page is `no-store`, the PDF sent after it
  `private, max-age=300` (never in Cloudflare or a proxy; the reader's browser keeps it, which Chrome's PDF viewer needs
  to download a PDF that came from a POST). Every open asks the question again (owner, 2026-11-24): a click on a PDF link, a reload or a typed URL (any GET)
  gets the download page, also right after the same PDF was opened. A right answer only sets a pass cookie
  (`lq_pdf_pass_{hash of that PDF URL}`, HttpOnly, path `/pdf`, signed for that one URL without `download=1` and that one
  token, 5 minutes) for Chrome's PDF viewer: its download / save button sends the very same POST again (token already
  used), which then gets the PDF. Any other request gets the **download page** (`templates/pdf-check.php`: the Act or document, jurisdiction, language, size, file, the question, Open (new tab) / Download, the Act's other PDFs, back link; owner 2026-11-24). Right after Open or Download the page swaps in a new question from `/pdf-question` (JSON, `no-store`), as a token works once, so a PDF never opens straight from a typed URL, a bookmark, a search engine or an outside link. PDFs and the download page send `X-Robots-Tag: noindex, nofollow`; links to PDFs are `rel="nofollow"`; `robots.txt` disallows `/pdf` and `/files/`.
