<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Renders templates/*.php. A page template is wrapped in templates/layout.php, which holds
 * the one shared <head>, header and footer; partials live in templates/partials/.
 */
final class View
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly string $dir,
        public readonly array $config,
        public readonly string $path,
    ) {
    }

    /**
     * Prints a full page.
     *
     * @param array<string, mixed> $vars
     * @param array{title: string, description?: string, canonical?: string|null, robots?: string,
     *   prev?: string|null, next?: string|null, nav?: string, status?: int, type?: string,
     *   schema?: list<array<string, mixed>>} $meta `type` is the Open Graph type; `schema` holds
     *   JSON-LD blocks (see Seo).
     */
    public function page(string $template, array $vars, array $meta): void
    {
        $meta += [
            'description' => '',
            'canonical' => $this->path,
            'robots' => '',
            'prev' => null,
            'next' => null,
            'nav' => 'acts',
            'status' => 200,
            'type' => 'website',
            'schema' => [],
        ];
        $content = $this->render($template, $vars);
        $html = $this->render('layout', ['content' => $content, 'meta' => $meta]);

        http_response_code($meta['status']);
        header('Content-Type: text/html; charset=utf-8');
        if ($meta['status'] === 200) {
            // Crawlers and browsers revalidate with If-None-Match and get a 304 when the
            // page has not changed since the last export.
            $etag = '"' . substr(hash('sha256', $html), 0, 32) . '"';
            header('ETag: ' . $etag);
            header('Cache-Control: public, max-age=0, must-revalidate');
            $sent = array_map('trim', explode(',', (string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')));
            if (in_array($etag, $sent, true) || in_array('W/' . $etag, $sent, true)) {
                http_response_code(304);
                return;
            }
        }
        echo $html;
    }

    /**
     * A partial from templates/partials/ as a string.
     *
     * @param array<string, mixed> $vars
     */
    public function partial(string $name, array $vars = []): string
    {
        return $this->render('partials/' . $name, $vars);
    }

    /**
     * Absolute URL on this site (canonical links, sitemap).
     */
    public function absolute(string $path): string
    {
        return rtrim((string) $this->config['site_url'], '/') . $path;
    }

    /**
     * URL of a file under public/ (CSS, JS, images), built from site_url like absolute().
     */
    public function asset(string $path): string
    {
        return $this->absolute('/' . ltrim($path, '/'));
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function render(string $template, array $vars): string
    {
        // "$__" names so a template variable called $file or $template is not skipped.
        $__file = $this->dir . '/' . $template . '.php';
        if (!is_file($__file)) {
            throw new \LogicException("Template not found: $template");
        }
        unset($template);
        $view = $this;
        extract($vars, EXTR_SKIP);
        unset($vars);
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
