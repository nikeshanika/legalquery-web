<?php

/**
 * Router for PHP's built-in server (local development only):
 *
 *   php -S localhost:8800 -t public router.php
 *
 * Real files under public/ (CSS, JS, robots.txt) are served as they are; every other
 * request goes to public/index.php, like public/.htaccess does on Apache.
 */

declare(strict_types=1);

$path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path !== '/' && !str_ends_with($path, '.php') && is_file(__DIR__ . '/public' . $path)) {
    return false;
}

require __DIR__ . '/public/index.php';
