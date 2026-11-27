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

    /**
     * All Acts of a jurisdiction, or those of one year: /acts/central, /acts/central/2020.
     */
    public static function actList(string $jurisdiction, string $year = ''): string
    {
        $base = '/acts/' . rawurlencode($jurisdiction);
        return $year === '' ? $base : $base . '/' . rawurlencode($year);
    }

    /**
     * The States and Union Territories whose Acts are on the site.
     */
    public static function states(): string
    {
        return '/states';
    }

    /**
     * A policy page, e.g. /privacy-policy (PageController::PAGES keys).
     */
    public static function page(string $slug): string
    {
        return '/' . $slug;
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
    public static function act(string $jurisdiction, string $slug, string $tab = ''): string
    {
        return self::actList($jurisdiction) . '/' . rawurlencode($slug) . ($tab !== '' ? '/' . rawurlencode($tab) : '');
    }

    /**
     * Where a section or schedule is shown: in its Act's Sections tab (page N) or Schedules tab,
     * e.g. /acts/central/companies-act-2013/page/2#section-25. Sections and schedules have no
     * page of their own (owner, 2026-11-03); their old URLs 301 here.
     *
     * @param string $kind SECTION or SCHEDULE
     */
    public static function provision(string $jurisdiction, string $actSlug, string $slug, string $kind = 'SECTION', int $page = 1): string
    {
        $base = $kind === 'SCHEDULE'
            ? self::act($jurisdiction, $actSlug, 'schedules')
            : self::paged(self::act($jurisdiction, $actSlug), $page);
        return $base . '#' . rawurlencode($slug);
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
     * The download page / PDF of an Act's own PDF (owner, 2026-11-24): /pdf?j={jurisdiction}&a={act}, plus
     * &v={language} for every PDF but the Act's first English one (`hi`, `gu`, `bi`, `en-2` …: ActPdf).
     * The same URL shows the download page (GET without a pass) and sends the PDF (the right answer).
     */
    public static function actPdf(string $jurisdiction, string $act, string $v = '', bool $download = false): string
    {
        return self::pdfQuery(['j' => $jurisdiction, 'a' => $act, 'v' => $v], $download);
    }

    /**
     * A linked document's download page / PDF: /pdf?j=…&a=…&d={tab}&f={file} (Content::tab() gives `f`).
     */
    public static function linkedPdf(string $jurisdiction, string $act, string $tab, string $file, bool $download = false): string
    {
        return self::pdfQuery(['j' => $jurisdiction, 'a' => $act, 'd' => $tab, 'f' => $file], $download);
    }

    /**
     * The download page / PDF of a document linked only from sections or schedules (in none of the Act's
     * tabs): /pdf?j=…&a=…&s={provision}&f={file}, `s` = the first section (else schedule) that links it
     * (Content::pdfLinks gives `s` and `f`; owner, 2026-11-26).
     */
    public static function provisionPdf(string $jurisdiction, string $act, string $provision, string $file, bool $download = false): string
    {
        return self::pdfQuery(['j' => $jurisdiction, 'a' => $act, 's' => $provision, 'f' => $file], $download);
    }

    /**
     * /pdf?… with the parameters in this order and empty ones left out: the one form FileController
     * accepts (any other gets a 301 to it).
     *
     * @param array<string, string> $params
     */
    private static function pdfQuery(array $params, bool $download): string
    {
        $params = array_filter($params, static fn(string $v): bool => $v !== '');
        return '/pdf?' . http_build_query($params + ($download ? ['download' => '1'] : []), '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * $url with download=1 added (the PDF is sent as a download, not shown).
     */
    public static function withDownload(string $url, bool $download = true): string
    {
        return $download ? $url . (str_contains($url, '?') ? '&' : '?') . 'download=1' : $url;
    }

    /**
     * A PDF's URL: its /pdf URL when it has one (`href`, from ActPdf, Content::tab or Content::pdfLinks), else by checksum.
     *
     * @param array<string, mixed> $file
     */
    public static function pdf(array $file, bool $download = false): string
    {
        if (isset($file['href'])) {
            return self::withDownload((string) $file['href'], $download);
        }
        return self::file($file, $download);
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
