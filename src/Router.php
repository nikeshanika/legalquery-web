<?php

declare(strict_types=1);

namespace LegalQueryWeb;

use LegalQueryWeb\Controller\ActController;
use LegalQueryWeb\Controller\ActListController;
use LegalQueryWeb\Controller\CategoryController;
use LegalQueryWeb\Controller\FileController;
use LegalQueryWeb\Controller\HomeController;
use LegalQueryWeb\Controller\SearchController;
use LegalQueryWeb\Controller\SeoController;

/**
 * URL patterns -> controller methods. Named groups become the method's arguments;
 * a group that did not match is passed as ''. The first matching pattern wins, so
 * fixed paths come before the patterns with an Act slug.
 */
final class Router
{
    private const SLUG = '(?<act>[a-z0-9]+(?:-[a-z0-9]+)*)';
    private const PAGE = '(?:/page/(?<page>\d+))?';
    private const TABS = 'rules|notifications|regulations|orders|ordinances|statutes|circulars|appendices|amendments';

    /**
     * @return list<array{0: string, 1: array{0: class-string, 1: string}}>
     */
    public static function routes(): array
    {
        $act = '/acts/central/' . self::SLUG;
        return [
            ['#^/$#', [HomeController::class, 'index']],
            ['#^/acts$#', [HomeController::class, 'acts']],
            ['#^/search' . self::PAGE . '$#', [SearchController::class, 'index']],
            ['#^/sitemap\.xml$#', [SeoController::class, 'sitemap']],
            ['#^/sitemap-(?<part>\d+)\.xml$#', [SeoController::class, 'sitemapPart']],
            ['#^/robots\.txt$#', [SeoController::class, 'robots']],
            ['#^/files/(?<sha>[0-9a-f]{64})/(?<name>[A-Za-z0-9._-]{1,120})$#', [FileController::class, 'pdf']],
            ['#^/(?<category>' . self::TABS . ')' . self::PAGE . '$#', [CategoryController::class, 'index']],
            ['#^/acts/central' . self::PAGE . '$#', [ActListController::class, 'index']],
            ['#^/acts/central/(?<year>\d{4})' . self::PAGE . '$#', [ActListController::class, 'index']],
            ['#^' . $act . self::PAGE . '$#', [ActController::class, 'sections']],
            ['#^' . $act . '/schedules$#', [ActController::class, 'schedules']],
            ['#^' . $act . '/details$#', [ActController::class, 'details']],
            ['#^' . $act . '/(?<tab>' . self::TABS . ')' . self::PAGE . '$#', [ActController::class, 'tab']],
            ['#^' . $act . '/(?<provision>section-[a-z0-9-]+|[a-z]+-schedule|schedule-\d+)$#', [ActController::class, 'provision']],
        ];
    }

    /**
     * @return array{0: array{0: class-string, 1: string}, 1: array<string, string>}|null
     */
    public static function match(string $path): ?array
    {
        foreach (self::routes() as [$pattern, $handler]) {
            if (preg_match($pattern, $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$handler, $params];
            }
        }
        return null;
    }
}
