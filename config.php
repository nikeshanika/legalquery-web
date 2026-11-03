<?php

/**
 * Site settings. Override any of them in config.local.php (not in git), e.g. on the server:
 *
 *   <?php return ['site_url' => 'https://laws.legalquery.in'];
 */

declare(strict_types=1);

$config = [
    // Used for canonical links and the sitemap. No trailing slash.
    'site_url' => 'http://localhost:8800',
    'site_name' => 'LegalQuery',
    // Written by the scraper: python indiacode_scraper.py export-web --out ../legalquery-web/content
    'content_dir' => __DIR__ . '/content',
    // Picture shown when a page is shared (Open Graph / X), 1200×630. Site path or full URL.
    'share_image' => '/assets/img/share.png',
    // Rows per page on every paged list.
    'per_page' => 20,
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace($config, require $local);
}

return $config;
