<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * One request: canonical-URL redirects, routing, error pages.
 */
final class App
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(private readonly array $config)
    {
    }

    public function run(string $method, string $uri): void
    {
        $path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?: '/'));
        $query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('X-Frame-Options: SAMEORIGIN');

        $view = new View(dirname(__DIR__) . '/templates', $this->config, $path);
        try {
            if ($method !== 'GET' && $method !== 'HEAD') {
                header('Allow: GET, HEAD');
                throw new HttpError(405);
            }
            $clean = self::canonicalPath($path);
            if ($clean !== $path) {
                throw new Redirect($clean . ($query !== '' ? '?' . $query : ''), 301);
            }
            $content = new Content((string) $this->config['content_dir']);
            [$handler, $params] = Router::match($path) ?? throw new HttpError(404);
            if (!$content->ready() && $handler[0] !== Controller\SeoController::class) {
                throw new HttpError(503, 'No content yet. Run the export in the scraper project.');
            }
            [$class, $action] = $handler;
            $controller = new $class($content, $view, $this->config, $_GET);
            $controller->$action(...$params);
        } catch (Redirect $redirect) {
            header('Location: ' . $redirect->location, true, $redirect->status);
        } catch (HttpError $error) {
            $this->error($view, $error->status, $error->getMessage());
        } catch (\Throwable $e) {
            error_log('legalquery-web: ' . $e);
            $this->error($view, 500, '');
        }
    }

    /**
     * The canonical form of a path: no trailing slash, no "//", lowercase (except file
     * names under /files/ and assets, which keep their case).
     */
    public static function canonicalPath(string $path): string
    {
        $clean = (string) preg_replace('#/{2,}#', '/', $path);
        if ($clean === '/index.php') {
            return '/';
        }
        if ($clean !== '/') {
            $clean = rtrim($clean, '/') ?: '/';
        }
        if (!str_starts_with($clean, '/files/') && !str_starts_with($clean, '/assets/')) {
            $clean = strtolower($clean);
        }
        return $clean;
    }

    private function error(View $view, int $status, string $message): void
    {
        $titles = [
            404 => 'Page not found',
            405 => 'Method not allowed',
            500 => 'Something went wrong',
            503 => 'Not available yet',
        ];
        $view->page('error', [
            'status' => $status,
            'heading' => $titles[$status] ?? 'Error',
            'message' => $status === 404 ? '' : $message,
        ], [
            'title' => $titles[$status] ?? 'Error',
            'robots' => 'noindex',
            'canonical' => null,
            'status' => $status,
            'nav' => '',
        ]);
    }
}
