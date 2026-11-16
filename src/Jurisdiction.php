<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * The jurisdictions the site knows: each is a content folder (content/<code>/), a URL segment
 * (/acts/<code>) and the names shown for it. Only Central Acts exist today; a State is added here
 * once the scraper exports it (owner OK for its URL first, docs/URLS.md).
 */
final class Jurisdiction
{
    /** Used by the pages that are not about one jurisdiction yet: home, categories (/rules ...). */
    public const DEFAULT = 'central';

    /**
     * code => [list heading, Act Details "Jurisdiction" row].
     */
    private const ALL = [
        'central' => ['Central Acts', 'Central (Parliament of India)'],
    ];

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::ALL);
    }

    public static function exists(string $code): bool
    {
        return isset(self::ALL[$code]);
    }

    /**
     * "Central Acts": list heading, breadcrumb and menu label.
     */
    public static function name(string $code): string
    {
        return self::ALL[$code][0] ?? throw new \InvalidArgumentException("Unknown jurisdiction $code");
    }

    /**
     * "Central (Parliament of India)": the Act Details row.
     */
    public static function detail(string $code): string
    {
        return self::ALL[$code][1] ?? throw new \InvalidArgumentException("Unknown jurisdiction $code");
    }

    /**
     * Regex alternation of the codes, for the router.
     */
    public static function pattern(): string
    {
        return implode('|', array_map(static fn(string $c): string => preg_quote($c, '#'), self::codes()));
    }
}
