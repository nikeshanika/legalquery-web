<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Seo;
use LegalQueryWeb\Url;

/**
 * The site's own policy pages (owner, 2026-11-27): /privacy-policy, /terms-of-service,
 * /legal-disclaimer, linked from the footer (no Refund Policy: this site takes no payments). Their text is the same as the
 * LegalQuery policy pages and is kept in `pages/<slug>.html` (in git; trusted HTML written by us,
 * not from the export): headings, paragraphs, lists and mailto links only, no links to other sites.
 */
final class PageController extends Controller
{
    /** slug => title, in footer order. A slug is a published URL: never rename one. */
    public const PAGES = [
        'privacy-policy' => 'Privacy Policy',
        'terms-of-service' => 'Terms of Service',
        'legal-disclaimer' => 'Legal Disclaimer',
    ];

    public function show(string $page): void
    {
        $title = self::PAGES[$page] ?? throw new HttpError(404);
        $file = dirname(__DIR__, 2) . '/pages/' . $page . '.html';
        $html = is_file($file) ? (string) file_get_contents($file) : '';
        if ($html === '') {
            throw new HttpError(404);
        }
        $this->view->page('page', ['title' => $title, 'body_html' => $html], [
            'title' => $title,
            'description' => Seo::description($html),
            'canonical' => Url::page($page),
            // Same text as legalquery.in's policy pages: kept out of Google (and of the sitemap), links still followed.
            'robots' => 'noindex, follow',
            'nav' => '',
        ]);
    }
}
