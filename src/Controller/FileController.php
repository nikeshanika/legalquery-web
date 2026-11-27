<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\ActPdf;
use LegalQueryWeb\Content;
use LegalQueryWeb\HttpError;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\PdfGate;
use LegalQueryWeb\PdfTitle;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Url;

/**
 * PDFs (owner, 2026-11-24): every PDF has one URL, /pdf?…, that shows its download page and sends the
 * PDF (inline, or with &download=1 as a download) only after the PDF question:
 *
 * - /pdf?j={jurisdiction}&a={act}[&v={language}]: an Act's own PDF (ActPdf).
 * - /pdf?j={jurisdiction}&a={act}&d={tab}&f={file}: a linked document (rules, notifications …; Content::tab).
 * - /pdf?j={jurisdiction}&a={act}&s={provision}&f={file}: a document linked only from sections or schedules
 *   (Content::pdfLinks; owner, 2026-11-26).
 *
 * The parameters must come in that order, lowercase, with nothing else (Url::actPdf, Url::linkedPdf,
 * Url::provisionPdf): any
 * other spelling of a PDF that exists gets a 301 to it. Only the URL is readable; the file on disk keeps
 * its India Code name.
 *
 * Old URLs, kept for links already out there: /acts/{jurisdiction}/{name}.pdf (2026-11-19 to 2026-11-24)
 * and /files/{sha256}/{name}.pdf get a 301 to the /pdf URL (a 307 for a POST, so an answer is not lost).
 * A /files/ PDF with no /pdf URL (linked from nowhere on the site) is still served there.
 *
 * With `pdf_captcha` on (PdfGate), a GET without a pass (typed, bookmarked, a search engine, an outside
 * link) gets the download page (templates/pdf-check.php: what the PDF is, the question, Open / Download,
 * the Act's other PDFs, back to the Act), which POSTs the answer to the same URL. The site's PDF links
 * all lead to that page (no popup; owner, 2026-11-24). PDFs and that page are `noindex, nofollow`, and
 * robots.txt disallows /pdf and /files/.
 */
final class FileController extends Controller
{
    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /**
     * /pdf?…
     */
    public function page(): void
    {
        $param = fn(string $key): string => is_string($this->query[$key] ?? null) ? $this->query[$key] : '';
        [$j, $a, $d, $s, $f, $v] = [$param('j'), $param('a'), $param('d'), $param('s'), $param('f'), $param('v')];
        $download = $param('download') === '1';
        if (!Jurisdiction::exists($j) || !preg_match(self::SLUG, $a)) {
            throw new HttpError(404);
        }
        $act = $this->content->act($j, $a) ?? throw new HttpError(404);

        if ($s !== '') {
            // A document linked only from sections or schedules.
            if ($d !== '' || $v !== '' || !preg_match(self::SLUG, $s) || !preg_match('/^[a-z0-9._-]{1,150}$/', $f)) {
                throw new HttpError(404);
            }
            $link = $this->content->provisionFile($act, array_keys(ActController::TABS), $s, $f) ?? throw new HttpError(404);
            $self = (string) $link['href'];
            $this->canonicalRequest(Url::withDownload($self, $download));
            $sha = (string) ($link['file']['sha256'] ?? '');
            $path = $this->content->pdfPath($sha) ?? throw new HttpError(404);
            $name = self::fileName((string) ($link['file']['filename'] ?? $f . '.pdf'));
            $this->send($path, $name, $self, $download, 'public, max-age=86400', self::etag($sha), $self, $this->info($path, $name, $act, found: $link));
            return;
        }

        if ($d !== '' || $f !== '') {
            // A linked document.
            if ($v !== '' || !isset(ActController::TABS[$d]) || !preg_match('/^[a-z0-9._-]{1,150}$/', $f)) {
                throw new HttpError(404);
            }
            $linked = $this->content->linkedFile($j, $a, $d, $f) ?? throw new HttpError(404);
            $self = (string) $linked['href'];
            $this->canonicalRequest(Url::withDownload($self, $download));
            $path = $this->content->pdfPath((string) ($linked['sha256'] ?? '')) ?? throw new HttpError(404);
            $name = self::fileName((string) ($linked['filename'] ?? $f . '.pdf'));
            $this->send($path, $name, $self, $download, 'public, max-age=86400', self::etag((string) $linked['sha256']), $self, $this->info($path, $name, $act, null, $linked));
            return;
        }

        if ($v !== '' && !preg_match('/^[a-z]{2,3}(?:-[1-9]\d*|-ai)?$/', $v)) {
            throw new HttpError(404);
        }
        $file = ActPdf::byLanguage($act, $v);
        if ($file === null && $v === '') {
            // No English PDF: the Act's first PDF (e.g. only Hindi).
            $first = ActPdf::choices($act)[0] ?? throw new HttpError(404);
            throw new Redirect(Url::withDownload((string) $first['href'], $download), 301);
        }
        $file ?? throw new HttpError(404);
        $self = (string) $file['href'];
        $this->canonicalRequest(Url::withDownload($self, $download));
        // An AI translation's path comes from Content::aiFiles (config folder + checksum + language), not the URL.
        $path = !empty($file['ai'])
            ? (is_file((string) $file['path']) ? (string) $file['path'] : throw new HttpError(404))
            : ($this->content->pdfPath((string) $file['sha256']) ?? throw new HttpError(404));
        $name = $file['name'] . '.pdf';
        // The PDF behind a URL can change with a new export: revalidate, by checksum.
        $this->send($path, $name, $this->canonicalOf($act, $file), $download, 'public, max-age=86400', self::etag((string) $file['sha256']), $self, $this->info($path, $name, $act, $file));
    }

    /**
     * /files/{sha256}/{name}.pdf: 301 to the PDF's /pdf URL; served here only when it has none.
     */
    public function pdf(string $sha, string $name): void
    {
        $path = $this->content->pdfPath($sha) ?? throw new HttpError(404);
        $download = ($this->query['download'] ?? '') === '1';
        $href = $this->hrefOf($path, $sha);
        if ($href !== null) {
            throw new Redirect(Url::withDownload($href, $download), self::movedStatus());
        }
        $url = Url::file(['sha256' => $sha, 'filename' => $name]);
        // The URL holds the checksum, so the content at a URL never changes.
        $this->send($path, $name, $url, $download, 'public, max-age=31536000, immutable', null, null, $this->info($path, $name));
    }

    /**
     * /acts/{jurisdiction}/{name}.pdf (2026-11-19 to 2026-11-24): 301 to the PDF's /pdf URL.
     */
    public function actPdf(string $jurisdiction, string $name): void
    {
        $download = ($this->query['download'] ?? '') === '1';
        foreach (ActPdf::candidates($name) as $slug) {
            $act = $this->content->act($jurisdiction, $slug);
            // Its name then, or a State-language PDF's older name (`{act}-english-2`).
            $file = $act ? (ActPdf::find($act, $name) ?? ActPdf::moved($act, $name)) : null;
            if ($file) {
                throw new Redirect(Url::withDownload((string) $file['href'], $download), self::movedStatus());
            }
        }
        throw new HttpError(404);
    }

    /**
     * A GET or HEAD of any other spelling of $url (order, case, extra parameters): 301 to $url.
     */
    private function canonicalRequest(string $url): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (($method === 'GET' || $method === 'HEAD') && ($_SERVER['REQUEST_URI'] ?? $url) !== $url) {
            throw new Redirect($url, 301);
        }
    }

    /**
     * 301 for a GET, 307 for a POST (a PDF question's answer sent to an old URL keeps its body).
     */
    private static function movedStatus(): int
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' ? 307 : 301;
    }

    private static function etag(string $sha): string
    {
        return '"' . substr($sha, 0, 32) . '"';
    }

    /**
     * A safe download name: "Rules 2013 (Amendment).pdf" → "Rules-2013-Amendment-.pdf".
     */
    private static function fileName(string $name): string
    {
        $name = trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $name), '-.') ?: 'document';
        return str_ends_with(strtolower($name), '.pdf') ? $name : $name . '.pdf';
    }

    /**
     * Sends a PDF, after the question when `pdf_captcha` is on. $url is its canonical URL; $self the
     * URL the question posts to (defaults to $url); $info what the download page shows (info()).
     *
     * @param array<string, mixed> $info
     */
    private function send(string $path, string $name, string $url, bool $download, string $cache, ?string $etag, ?string $self = null, array $info = []): void
    {
        $self ??= $url;
        $gate = $this->gate();
        if ($gate) {
            // Every open asks the question again (owner, 2026-11-24): a click on a PDF link, a reload or a typed URL
            // (a GET) always gets the download page. The one exception is the browser's PDF viewer saving the PDF
            // it just showed: Chrome does that by sending the very same POST again (its token already used up)
            // and fails without the PDF ("Check Internet connection"). So a right answer sets a pass for this
            // PDF URL ($self, without download=1) and this one token, in a cookie of its own (every /pdf URL has
            // the same path), for PASS_TTL; it opens the PDF only for a POST carrying that same token.
            $cookie = PdfGate::PASS_COOKIE . '_' . substr(hash('sha256', $self), 0, 16);
            $token = (string) ($_POST['token'] ?? '');
            $asked = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $token !== '';
            if ($asked && $gate->check($token, (string) ($_POST['answer'] ?? ''))) {
                setcookie($cookie, $gate->pass($self . '|' . $token), [
                    'expires' => time() + PdfGate::PASS_TTL,
                    'path' => (string) parse_url($self, PHP_URL_PATH),
                    'secure' => self::https(),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            } elseif (!$asked || !$gate->passes((string) ($_COOKIE[$cookie] ?? ''), $self . '|' . $token)) {
                // Opened directly, from a link, a search engine, or a wrong answer: the download page.
                $this->askQuestion($gate, $name, $self, $download, $asked, $info);
                return;
            }
        }
        // ?download=1 is the same file: point search engines at the plain URL. PDFs stay out of search results.
        header(sprintf('Link: <%s>; rel="canonical"', $this->view->absolute($url)));
        header('X-Robots-Tag: noindex, nofollow');
        // Behind the question the PDF must not sit in a shared cache (Cloudflare, proxies), or that cache
        // would hand it out to anyone: `private`. The reader's own browser may keep it for the pass's
        // lifetime: Chrome's PDF viewer downloads a PDF that came from a POST only from its cache, and with
        // `no-store` that download failed ("Check Internet connection", then "Resume").
        header('Cache-Control: ' . ($gate ? 'private, max-age=' . PdfGate::PASS_TTL : $cache));
        // The site's title instead of the file's own Title metadata, which is often junk (PdfTitle).
        $suffix = PdfTitle::suffix($path, (string) ($info['title'] ?? ''));
        if ($etag !== null && $suffix !== '') {
            $etag = '"' . substr(hash('sha256', $etag . $suffix), 0, 32) . '"';
        }
        if (!$gate && $etag !== null) {
            header('ETag: ' . $etag);
            $sent = array_map('trim', explode(',', (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
            if (in_array($etag, $sent, true)) {
                http_response_code(304);
                return;
            }
        }
        header('Content-Type: application/pdf');
        header('Content-Length: ' . (filesize($path) + strlen($suffix)));
        header(sprintf('Content-Disposition: %s; filename="%s"', $download ? 'attachment' : 'inline', $name));
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            return;
        }
        // Stream the PDF instead of holding it in public/index.php's output buffer.
        while (ob_get_level() > 0) {
            ob_end_flush();
        }
        readfile($path);
        echo $suffix;
    }

    /**
     * The page that lists the PDF stored at $path: its Act page for the Act's own PDF
     * (content/<j>/acts/<act>/pdf/<file>), the Act's tab for a linked document
     * (…/pdf/rules/<file> → /acts/<j>/<act>/rules), else null.
     */
    public static function pageOf(string $path): ?string
    {
        if (!preg_match('#/([a-z0-9-]+)/acts/([a-z0-9-]+)/pdf/(?:([a-z]+)/)?[^/]+$#', $path, $m)) {
            return null;
        }
        $tab = $m[3] ?? '';
        return Url::act($m[1], $m[2], isset(ActController::TABS[$tab]) ? $tab : '');
    }

    /**
     * What the download page says about the PDF at $path: `title`, `rows` (label => value), `back` (URL)
     * and, for an Act's own PDF, `others` (the Act's other PDFs: label, lang, href).
     *
     * @param array<string, mixed>|null $act the Act whose PDF URL this is (else found from $path)
     * @param array<string, mixed>|null $file its choice (ActPdf::choices) when it is an Act's own PDF
     * @param array<string, mixed>|null $linked its file (Content::linkedFile) when it is a linked document
     * @param array<string, mixed>|null $found its entry in Content::pdfLinks when it is linked only from sections
     *
     * @return array<string, mixed>
     */
    private function info(string $path, string $name, ?array $act = null, ?array $file = null, ?array $linked = null, ?array $found = null): array
    {
        preg_match('#/([a-z0-9-]+)/acts/([a-z0-9-]+)/pdf/(?:([a-z]+)/)?[^/]+$#', $path, $m);
        $act ??= isset($m[1]) ? $this->content->act($m[1], $m[2]) : null;
        $tab = $linked !== null ? (string) $linked['tab'] : ($file === null ? ($m[3] ?? '') : '');
        $rows = [];
        $notice = '';
        // Linked only from a section or schedule: that link gives its title, date, language and the document's
        // other PDFs (owner, 2026-11-26).
        $foundDoc = $found['doc'] ?? null;
        if ($act && $file === null) {
            $rows['Under'] = (string) ($act['title'] ?? '');
        }
        if ($act) {
            $rows['Jurisdiction'] = Jurisdiction::short((string) $act['jurisdiction']);
        }
        $rows['Document'] = $file !== null
            ? 'The Act, as published'
            : (isset(ActController::TABS[$tab]) ? collection_label(ActController::TABS[$tab]) : 'Document');
        if ($file !== null) {
            $rows['Language'] = (string) $file['label'];
            if (!empty($file['ai'])) {
                $rows['Document'] = 'AI translation of the Act: not official, not reviewed';
                $notice = self::aiNotice((string) $file['lang']);
            }
        }
        $date = (string) ($linked['date'] ?? $foundDoc['date'] ?? '');
        if ($date !== '') {
            $rows['Date'] = format_date($date);
        }
        $rows['Size'] = format_size((int) filesize($path));
        $rows['File'] = $name;
        $back = match (true) {
            $act === null => Url::home(),
            $file !== null => Url::act((string) $act['jurisdiction'], (string) $act['slug']),
            $linked !== null => Url::act((string) $act['jurisdiction'], (string) $act['slug'], $tab),
            // Linked only from sections: the first section that links it (owner, 2026-11-26). A PDF linked from
            // nowhere (served at /files/) may still sit in a tab's folder (pdf/notifications/ …): that tab when
            // the Act has it, else the Act page.
            $found !== null && $this->provisionUrl($act, $found['slug']) !== null => (string) $this->provisionUrl($act, $found['slug']),
            !empty($act['tabs'][$tab]) && isset(ActController::TABS[$tab]) => Url::act((string) $act['jurisdiction'], (string) $act['slug'], $tab),
            default => Url::act((string) $act['jurisdiction'], (string) $act['slug']),
        };
        $others = [];
        foreach ($file !== null && $act ? ActPdf::choices($act) : [] as $choice) {
            if ($choice['href'] !== $file['href']) {
                $others[] = ['label' => $choice['label'], 'lang' => $choice['lang'], 'href' => $choice['href'], 'ai' => !empty($choice['ai'])];
            }
        }
        // A linked document (rule, notification, amendment …): its language and its PDFs in other languages, if any
        // (most have only English), so the header language menu lists only those (owner, 2026-11-26).
        $lang = $file !== null ? (string) $file['lang'] : '';
        if ($linked !== null) {
            $lang = 'unknown';
            foreach (pdf_choices(array_values(array_filter((array) ($linked['item_files'] ?? []), static fn($f): bool => is_array($f) && isset($f['href'])))) as $choice) {
                if ($choice['href'] === $linked['href']) {
                    $lang = (string) $choice['lang'];
                } else {
                    $others[] = ['label' => $choice['label'], 'lang' => $choice['lang'], 'href' => $choice['href'], 'ai' => false];
                }
            }
        }
        if ($foundDoc !== null) {
            $lang = 'unknown';
            $links = $this->content->pdfLinks($act, array_keys(ActController::TABS));
            foreach (pdf_choices(array_values(array_filter((array) ($foundDoc['files'] ?? []), 'is_array'))) as $choice) {
                if ($choice['sha256'] === ($found['file']['sha256'] ?? null)) {
                    $lang = (string) $choice['lang'];
                } else {
                    $others[] = ['label' => $choice['label'], 'lang' => $choice['lang'], 'href' => (string) ($links[$choice['sha256']]['href'] ?? Url::pdf($choice)), 'ai' => false];
                }
            }
        }
        // Else only the file name to go by: "Investigation-of-…-Rules-2013.pdf" → "Investigation of … Rules 2013".
        $title = match (true) {
            $act && $file !== null => (string) $act['title'],
            !empty($linked['title']) => (string) $linked['title'],
            !empty($foundDoc['title']) => (string) $foundDoc['title'],
            default => trim((string) preg_replace('/[-_\s]+/', ' ', (string) preg_replace('/\.pdf$/i', '', $name))),
        };
        // `lang` / `ai`: this PDF's language when it is an Act PDF, for the header language menu (site.js).
        return ['title' => $title, 'rows' => $rows, 'back' => $back, 'others' => $others, 'notice' => $notice,
            'lang' => $lang, 'ai' => !empty($file['ai']),
            'others_title' => $linked !== null || $foundDoc !== null ? 'Other PDFs of this document' : 'Other PDFs of this Act'];
    }

    /**
     * The notice the download page of an AI translation shows above Open / Download (owner, 2026-11-25), e.g.
     * "Please note: you are about to open or download the Gujarati AI translation of this Act. …".
     */
    public static function aiNotice(string $lang): string
    {
        $name = ucfirst(preg_replace('/-ai$/', '', Content::AI_LANGUAGES[$lang] ?? '') ?: 'AI');
        return "Please note: you are about to open or download the $name translation of this Act. It was made by AI: "
            . 'it is not official, not issued or certified by the Government of India, and not yet reviewed. '
            . 'Only the official PDF of the Act is the authoritative text.';
    }

    /**
     * The /pdf URL of the PDF stored at $path with checksum $sha: an Act's own PDF (its canonical URL), or a
     * document the Act whose folder holds it links from a tab, a section or a schedule. Null when it has none.
     */
    private function hrefOf(string $path, string $sha): ?string
    {
        if (!preg_match('#/([a-z0-9-]+)/acts/([a-z0-9-]+)/pdf/(?:[^/]+/)?[^/]+$#', $path, $m)) {
            return null;
        }
        $act = $this->content->act($m[1], $m[2]) ?? null;
        if ($act === null) {
            return null;
        }
        foreach (ActPdf::choices($act) as $file) {
            if ($file['sha256'] === $sha) {
                return $this->canonicalOf($act, $file);
            }
        }
        $link = $this->content->pdfLinks($act, array_keys(ActController::TABS))[$sha] ?? null;
        return $link === null ? null : (string) $link['href'];
    }

    /**
     * Canonical URL of an Act PDF: the original Act's URL when this Act is a copy of an exported Act
     * with the same PDF there (a Central Act listed under a UT), else its own.
     *
     * @param array<string, mixed> $act
     * @param array<string, mixed> $file
     */
    private function canonicalOf(array $act, array $file): string
    {
        $original = $act['original'] ?? null;
        if (is_array($original) && preg_match('#^/acts/([a-z0-9-]+)/([a-z0-9-]+)$#', (string) ($original['path'] ?? ''), $m)) {
            $source = $this->content->act($m[1], $m[2]);
            foreach ($source ? ActPdf::choices($source) : [] as $choice) {
                if ($choice['sha256'] === $file['sha256']) {
                    return (string) $choice['href'];
                }
            }
        }
        return (string) $file['href'];
    }

    /**
     * True when this request came over HTTPS (directly or through a proxy such as Cloudflare).
     */
    private static function https(): bool
    {
        $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
        return ($https !== '' && $https !== 'off') || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }

    /**
     * /pdf-question: a new question as JSON, for the download page: after Open or Download it swaps in a
     * new question (a token works once; owner, 2026-11-24).
     */
    public function question(): void
    {
        $gate = $this->gate() ?? throw new HttpError(404);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        echo json_encode($gate->question(), JSON_THROW_ON_ERROR);
    }

    private function gate(): ?PdfGate
    {
        if (empty($this->config['pdf_captcha'])) {
            return null;
        }
        $secret = (string) ($this->config['pdf_captcha_secret'] ?? '');
        $tmp = sys_get_temp_dir() . '/legalquery-web-pdf-' . substr(hash('sha256', __DIR__), 0, 12);
        return new PdfGate($secret !== '' ? $secret : self::localSecret($tmp . '-secret'), $tmp . '-used');
    }

    /**
     * When config.local.php sets no secret: a random one kept in the system temp folder (local
     * development). A lost file only means open questions stop working.
     */
    private static function localSecret(string $file): string
    {
        $secret = is_file($file) ? trim((string) file_get_contents($file)) : '';
        if (strlen($secret) < 32) {
            $secret = bin2hex(random_bytes(32));
            @file_put_contents($file, $secret, LOCK_EX);
            @chmod($file, 0600);
        }
        return $secret;
    }

    /**
     * The download page: what the PDF is, the question, Open / Download (POST to $self, the PDF URL).
     *
     * @param array<string, mixed> $info
     */
    private function askQuestion(PdfGate $gate, string $name, string $self, bool $download, bool $wrong, array $info): void
    {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex, nofollow');
        $this->view->page('pdf-check', [
            'question' => $gate->question(),
            'name' => $name,
            'open' => Url::pdf(['href' => $self]),
            'download' => Url::pdf(['href' => $self], true),
            'wantsDownload' => $download,
            'wrong' => $wrong,
            'info' => $info + ['title' => $name, 'rows' => [], 'back' => Url::home(), 'others' => [], 'notice' => '', 'lang' => '', 'ai' => false],
        ], [
            'title' => 'Download PDF: ' . ($info['title'] ?? $name),
            'robots' => 'noindex, nofollow',
            'canonical' => null,
            'nav' => '',
            'status' => $wrong ? 422 : 200,
            'cache' => false,
        ]);
    }
}
