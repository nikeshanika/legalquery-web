<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\ActPdf;
use LegalQueryWeb\HttpError;
use LegalQueryWeb\PdfGate;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Url;

/**
 * PDFs, shown in the browser or (?download=1) downloaded:
 *
 * - /acts/{jurisdiction}/{name}.pdf: an Act's own PDF by its readable name (ActPdf, owner 2026-11-19).
 * - /files/{sha256}/{name}.pdf: any exported PDF by checksum (linked documents: rules, notifications …).
 *   A GET of an Act's own PDF here gets a 301 to its readable URL. The name part is never a path.
 *
 * Only the URL is readable; the file on disk keeps its India Code name.
 *
 * With `pdf_captcha` on, every PDF asks a short sum first (PdfGate). The PDF popup (partials/pdf-menu)
 * shows the question from /pdf-question and POSTs the answer to the PDF URL, which then sends the PDF.
 * A plain GET of the URL (no JavaScript, old links) shows the question as a page that POSTs the same way.
 */
final class FileController extends Controller
{
    public function pdf(string $sha, string $name): void
    {
        $path = $this->content->pdfPath($sha) ?? throw new HttpError(404);
        $download = ($this->query['download'] ?? '') === '1';
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' && ($readable = $this->readableUrl($path, $sha))) {
            throw new Redirect(Url::pdf(['href' => $readable], $download), 301);
        }
        $url = Url::file(['sha256' => $sha, 'filename' => $name]);
        // The URL holds the checksum, so the content at a URL never changes.
        $this->send($path, $name, $url, $download, 'public, max-age=31536000, immutable', null);
    }

    /**
     * /acts/{jurisdiction}/{name}.pdf
     */
    public function actPdf(string $jurisdiction, string $name): void
    {
        foreach (ActPdf::candidates($name) as $slug) {
            $act = $this->content->act($jurisdiction, $slug);
            $file = $act ? ActPdf::find($act, $name) : null;
            if ($file) {
                break;
            }
        }
        if (empty($file)) {
            throw new HttpError(404);
        }
        // An AI translation's path comes from Content::aiFiles (config folder + checksum + language), not the URL.
        $path = !empty($file['ai'])
            ? (is_file((string) $file['path']) ? (string) $file['path'] : throw new HttpError(404))
            : ($this->content->pdfPath((string) $file['sha256']) ?? throw new HttpError(404));
        $download = ($this->query['download'] ?? '') === '1';
        // The PDF behind a readable URL can change with a new export: revalidate, by checksum.
        $this->send($path, $name . '.pdf', $this->canonicalOf($act, $file), $download, 'public, max-age=86400', '"' . substr((string) $file['sha256'], 0, 32) . '"', (string) $file['href']);
    }

    /**
     * Sends a PDF, after the question when `pdf_captcha` is on. $url is its canonical URL; $self the
     * URL the question posts to (defaults to $url).
     */
    private function send(string $path, string $name, string $url, bool $download, string $cache, ?string $etag, ?string $self = null): void
    {
        $self ??= $url;
        $gate = $this->gate();
        if ($gate) {
            $token = (string) ($_POST['token'] ?? '');
            $asked = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $token !== '';
            if (!$asked || !$gate->check($token, (string) ($_POST['answer'] ?? ''))) {
                $this->askQuestion($gate, $name, Url::pdf(['href' => $self], $download), $asked);
                return;
            }
        }

        // ?download=1 is the same file: point search engines at the plain URL.
        header(sprintf('Link: <%s>; rel="canonical"', $this->view->absolute($url)));
        // Behind the question the PDF must not sit in any cache (Cloudflare, proxies), or the cache
        // would hand it out to anyone.
        header('Cache-Control: ' . ($gate ? 'no-store' : $cache));
        if (!$gate && $etag !== null) {
            header('ETag: ' . $etag);
            $sent = array_map('trim', explode(',', (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
            if (in_array($etag, $sent, true)) {
                http_response_code(304);
                return;
            }
        }
        header('Content-Type: application/pdf');
        header('Content-Length: ' . filesize($path));
        header(sprintf('Content-Disposition: %s; filename="%s"', $download ? 'attachment' : 'inline', $name));
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            return;
        }
        readfile($path);
    }

    /**
     * The readable URL of a PDF stored as an Act's own PDF (content/<j>/acts/<act>/pdf/<file>), or null.
     */
    private function readableUrl(string $path, string $sha): ?string
    {
        if (!preg_match('#/([a-z0-9-]+)/acts/([a-z0-9-]+)/pdf/[^/]+$#', $path, $m)) {
            return null;
        }
        $act = $this->content->act($m[1], $m[2]);
        foreach ($act ? ActPdf::choices($act) : [] as $file) {
            if ($file['sha256'] === $sha) {
                return $this->canonicalOf($act, $file);
            }
        }
        return null;
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
     * /pdf-question: a new question as JSON, for the PDF popup.
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

    private function askQuestion(PdfGate $gate, string $name, string $url, bool $wrong): void
    {
        header('Cache-Control: no-store');
        header('X-Robots-Tag: noindex');
        $this->view->page('pdf-check', [
            'question' => $gate->question(),
            'name' => $name,
            'action' => $url,
            'wrong' => $wrong,
        ], [
            'title' => 'Open PDF: ' . $name,
            'robots' => 'noindex',
            'canonical' => null,
            'nav' => '',
            'status' => $wrong ? 422 : 200,
            'cache' => false,
        ]);
    }
}
