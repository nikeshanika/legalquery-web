<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Search-engine helpers: meta descriptions and schema.org structured data (JSON-LD).
 * Paths given here are site paths ("/acts/central/x"); the layout makes them absolute.
 */
final class Seo
{
    /**
     * Plain text for a meta description: tags removed, spaces collapsed, cut at a word
     * boundary to at most $max characters ("…" added when cut).
     */
    public static function description(string $html, int $max = 160): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');
        if ($space !== false && $space > $max * 0.6) {
            $cut = mb_substr($cut, 0, $space);
        }
        return rtrim($cut, " ,;:.–-") . '…';
    }

    /**
     * BreadcrumbList for the crumbs partial: [label, path] pairs, then the current page
     * (which has no link, as Google allows for the last item).
     *
     * @param list<array{0: string, 1: string}> $crumbs
     *
     * @return array<string, mixed>
     */
    public static function breadcrumbs(array $crumbs, string $current, callable $absolute): array
    {
        $items = [];
        foreach ($crumbs as $i => [$label, $path]) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $label,
                // Breadcrumb targets are pages, not places on a page.
                'item' => $absolute(strtok($path, '#') ?: '/'),
            ];
        }
        $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $current];
        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }

    /**
     * WebSite with the search box (home page).
     *
     * @return array<string, mixed>
     */
    public static function website(string $name, callable $absolute): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $name,
            'url' => $absolute(Url::home()),
            'inLanguage' => 'en-IN',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => $absolute('/search') . '?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /**
     * An Act as schema.org Legislation.
     *
     * @param array<string, mixed> $act
     *
     * @return array<string, mixed>
     */
    public static function act(array $act, callable $absolute): array
    {
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Legislation',
            '@id' => $absolute(Url::act($act['slug'])) . '#act',
            'name' => $act['title'],
            'url' => $absolute(Url::act($act['slug'])),
            'legislationType' => 'Act',
            'legislationJurisdiction' => ['@type' => 'Country', 'name' => 'India'],
            'legislationPassedBy' => ['@type' => 'GovernmentOrganization', 'name' => 'Parliament of India'],
            'inLanguage' => 'en',
        ];
        if (($act['long_title'] ?? '') !== '') {
            $data['description'] = self::description($act['long_title'], 300);
        }
        if (($act['act_number'] ?? '') !== '') {
            $data['legislationIdentifier'] = 'Act No. ' . $act['act_number'] . ' of ' . $act['year'];
        }
        if (($act['enactment_date'] ?? '') !== '') {
            $data['legislationDate'] = substr($act['enactment_date'], 0, 10);
        }
        if (($act['enforcement_date'] ?? '') !== '') {
            $data['legislationDateOfApplicability'] = substr($act['enforcement_date'], 0, 10);
        }
        if (($act['ministry'] ?? '') !== '') {
            $data['legislationResponsible'] = ['@type' => 'GovernmentOrganization', 'name' => $act['ministry']];
        }
        if (($act['repeal_status'] ?? '') === 'not_repealed') {
            $data['legislationLegalForce'] = 'https://schema.org/InForce';
        }
        $encodings = [];
        foreach ($act['files'] ?? [] as $file) {
            if (isset($file['sha256'])) {
                $encodings[] = [
                    '@type' => 'LegislationObject',
                    'encodingFormat' => 'application/pdf',
                    'contentUrl' => $absolute(Url::file($file)),
                    'inLanguage' => ['hi' => 'hi', 'bi' => ['en', 'hi']][$file['language']] ?? 'en',
                ];
            }
        }
        if ($encodings) {
            $data['encoding'] = $encodings;
        }
        return $data;
    }

    /**
     * A <script type="application/ld+json"> block. JSON_HEX_TAG keeps "</script>" in data
     * from closing the tag.
     *
     * @param array<string, mixed> $data
     */
    public static function script(array $data): string
    {
        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_THROW_ON_ERROR);
        return '<script type="application/ld+json">' . $json . "</script>\n";
    }
}
