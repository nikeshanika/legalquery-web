<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Builds every public URL, so the URL rules live in one place (docs/URLS.md).
 */
final class Url
{
    public static function home(): string
    {
        return '/';
    }

    public static function actList(string $year = ''): string
    {
        return $year === '' ? '/acts/central' : '/acts/central/' . rawurlencode($year);
    }

    /**
     * Central Acts that have one kind of linked document, e.g. /rules (ActController::TABS keys).
     */
    public static function category(string $tab): string
    {
        return '/' . rawurlencode($tab);
    }

    /**
     * An Act page or one of its tabs ('schedules', 'details', 'rules', ...).
     */
    public static function act(string $slug, string $tab = ''): string
    {
        return '/acts/central/' . rawurlencode($slug) . ($tab !== '' ? '/' . rawurlencode($tab) : '');
    }

    /**
     * A section or schedule page, e.g. /acts/central/companies-act-2013/section-2a.
     */
    public static function provision(string $actSlug, string $slug): string
    {
        return self::act($actSlug) . '/' . rawurlencode($slug);
    }

    /**
     * Page N of a paged list: page 1 is the plain URL, then .../page/N.
     *
     * @param array<string, string> $query
     */
    public static function paged(string $base, int $page, array $query = []): string
    {
        $url = $page > 1 ? $base . '/page/' . $page : $base;
        $query = array_filter($query, static fn(string $v): bool => $v !== '');
        return $query ? $url . '?' . http_build_query($query) : $url;
    }

    public static function search(string $q = '', int $page = 1): string
    {
        return self::paged('/search', $page, ['q' => $q]);
    }

    /**
     * A PDF by checksum. The file name part is only for people (and the download name).
     *
     * @param array{sha256?: string, filename?: string} $file
     */
    public static function file(array $file, bool $download = false): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($file['filename'] ?? 'document.pdf'));
        $name = trim((string) $name, '-.') ?: 'document';
        if (!str_ends_with(strtolower($name), '.pdf')) {
            $name .= '.pdf';
        }
        return '/files/' . ($file['sha256'] ?? '') . '/' . substr($name, -120) . ($download ? '?download=1' : '');
    }
}
