# URLs

All URLs are built in `src/Url.php` and matched in `src/Router.php`.

| URL | Page |
|---|---|
| `/` | Home: search, counts, latest Acts, years, categories, references (`/acts` → 301 here) |
| `/acts/central` · `/acts/central/page/2` | All Central Acts |
| `/acts/central/2020` · `/acts/central/2020/page/2` | Central Acts of one year |
| `/acts/central?search=tax` | Filtered list (`noindex`); the year dropdown's `?year=` → 302 to the year URL |
| `/rules` · `/rules/page/2` | Central Acts that have Rules, newest first, each linking to its tab. Same for `notifications`, `regulations`, `orders`, `ordinances`, `statutes`, `circulars`, `appendices`, `amendments` (404 when no Act has it) |
| `/acts/central/{act}` · `…/page/2` | Act page, Sections tab (sections paged) |
| `/acts/central/{act}/schedules` | Schedules tab (404 when the Act has none) |
| `/acts/central/{act}/details` | Act Details tab (metadata, summary, history) |
| `/acts/central/{act}/rules` · `…/page/2` | Linked-document tab: `rules`, `notifications`, `regulations`, `orders`, `ordinances`, `statutes`, `circulars`, `appendices`, `amendments` (404 when empty) |
| `/acts/central/{act}/section-2a` | One section |
| `/acts/central/{act}/first-schedule` | One schedule |
| `/files/{sha256}/{name}.pdf` | PDF inline; `?download=1` downloads |
| `/search?q=…` · `/search/page/2?q=…` | Search (`noindex`) |
| `/sitemap.xml`, `/robots.txt` | Built from `site_url` in config. Above 50,000 URLs `/sitemap.xml` becomes an index of `/sitemap-1.xml`, `/sitemap-2.xml`, … (404 until then) |
| `/favicon.ico`, `/favicon.png` | Site icon (static files in `public/`) |

Rules:

- Lowercase, words joined by `-`, no IDs or UUIDs, no trailing slash. Uppercase, trailing slash,
  `//` and `/index.php` get a 301 to the clean URL.
- Pagination is in the path, 1-based. Page 1 is the plain URL; `…/page/1` → 301. A page past
  the last one, `/page/0` and `/page/01` are 404s.
- Every page prints a canonical link (from `site_url`); paged lists and sections print
  `rel="prev"`/`rel="next"`.
- Slugs never change once published (see `CONTENT.md` › Slugs).
- SEO in the `<head>` (`templates/layout.php`): title, description, canonical, prev/next, Open Graph
  and X card (image `share_image` in config), favicon. JSON-LD from `src/Seo.php`: `WebSite` +
  search box (home), `Legislation` (Act page and Act Details; sections and schedules are
  `Legislation` that `isPartOf` the Act) and `BreadcrumbList` (printed by `partials/crumbs.php`).
- HTML pages send an `ETag` (304 on `If-None-Match`); PDFs send `Link: rel="canonical"` to the URL
  without `?download=1`.
