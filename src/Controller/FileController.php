<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Url;

/**
 * /files/{sha256}/{name}.pdf: an exported PDF, shown in the browser or (?download=1)
 * downloaded. Files are found by checksum only; the name in the URL is never a path.
 */
final class FileController extends Controller
{
    public function pdf(string $sha, string $name): void
    {
        $path = $this->content->pdfPath($sha) ?? throw new HttpError(404);
        $download = ($this->query['download'] ?? '') === '1';
        $size = filesize($path);
        header('Content-Type: application/pdf');
        header('Content-Length: ' . $size);
        header(sprintf('Content-Disposition: %s; filename="%s"', $download ? 'attachment' : 'inline', $name));
        // ?download=1 is the same file: point search engines at the plain URL.
        header(sprintf('Link: <%s>; rel="canonical"', $this->view->absolute(Url::file(['sha256' => $sha, 'filename' => $name]))));
        // The URL holds the checksum, so the content at a URL never changes.
        header('Cache-Control: public, max-age=31536000, immutable');
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            return;
        }
        readfile($path);
    }
}
