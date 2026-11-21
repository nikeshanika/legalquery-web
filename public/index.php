<?php

/**
 * Front controller: every page request comes here (see .htaccess and router.php).
 *
 * The response is buffered so it goes out with a Content-Length. Without one, a proxy that does not
 * pass on the closed connection (e.g. a devtunnels tunnel in front of `php -S`) leaves the browser
 * waiting for more of the page: endless loading spinner. PDFs end the buffer themselves
 * (FileController) and are streamed with their own Content-Length.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

$config = require dirname(__DIR__) . '/config.php';
$level = ob_get_level(); // php.ini output_buffering may already have opened one
ob_start();
(new LegalQueryWeb\App($config))->run($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
if (ob_get_level() > $level) {
    $body = (string) ob_get_clean();
    $sent = array_filter(headers_list(), static fn(string $h): bool => stripos($h, 'content-length:') === 0);
    // zlib.output_compression changes the length after this point, so leave it to PHP then.
    $compressed = (bool) ini_get('zlib.output_compression');
    if (!headers_sent() && !$sent && !$compressed && http_response_code() !== 304) {
        header('Content-Length: ' . strlen($body));
    }
    echo $body;
}
