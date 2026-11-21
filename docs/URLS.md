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
| `/acts/central/{act}.pdf` | The Act's own PDF by a readable name (owner, 2026-11-19), inline; `?download=1` downloads (file name `{act}.pdf`). English (or unknown language) `{act}.pdf`, Hindi `{act}-hindi.pdf`, English + Hindi in one PDF `{act}-english-hindi.pdf`; AI translation (demo, only with `ai_translations_dir` set; owner 2026-11-21) `{act}-hindi-ai.pdf` (only when the Act has no official Hindi or English + Hindi PDF), `{act}-bengali-ai.pdf`, served from the indiacode project's `translations/` folder, never in JSON-LD; a second PDF of the same language gets `-2`, `-3` on the language word (`{act}-english-2.pdf`, `{act}-hindi-2.pdf`). Names come from `pdf_choices()` order (`src/ActPdf.php`). Only the URL is readable: the file on disk keeps its India Code name. A copy of another Act (`original`, e.g. a Central Act under a UT) sends `Link: rel="canonical"` to the original's PDF URL |
| `/files/{sha256}/{name}.pdf` | Any PDF by checksum (linked documents: rules, notifications …), inline; `?download=1` downloads. A GET of an Act's own PDF here → **301** to its `/acts/…/{act}.pdf` URL (POST, the PDF question's answer, is still served) |
| `/search?q=…` · `/search/page/2?q=…` | Search (`noindex`) |
| `/sitemap.xml`, `/robots.txt` | Built from `site_url` in config. The sitemap lists every indexable page: home, `/states`, every page of each Act list, year list and category, and per Act its Sections pages (all `/page/N`, since section text is only there), Schedules, Details and every page of each linked-document tab (counts from `act.json`). Not listed: search, filtered lists, PDFs, redirects. Above 50,000 URLs `/sitemap.xml` becomes an index of `/sitemap-1.xml`, `/sitemap-2.xml`, … (404 until then) |
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
  without `?download=1`. `/files/…` PDFs are cached as immutable (the checksum is in the URL); `/acts/…/{act}.pdf` can
  change with an export, so it sends `max-age=86400` and an `ETag` (the checksum). With `pdf_captcha` on, every PDF is
  `no-store` and only opens after the question, so search engines cannot read the PDFs.
