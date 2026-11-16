<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * The jurisdictions the site knows: each is a content folder (content/<code>/), a URL segment
 * (/acts/<code>) and the names shown for it. `central` is always known; States and UTs come from
 * the export's manifest.json (App calls register()), so a State is on the site once the scraper
 * exports it. URLs /acts/<code>/... (owner, 2026-11-16; docs/URLS.md).
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

    /** @var array<string, array{0: string, 1: string}> Exported States/UTs (register()). */
    private static array $more = [];

    /**
     * Add the exported jurisdictions (Content::jurisdictions(): code => name, e.g. "Kerala").
     * `central` keeps its own names. Replaces any earlier registration.
     *
     * @param array<string, string> $names
     */
    public static function register(array $names): void
    {
        self::$more = [];
        foreach ($names as $code => $name) {
            if (!isset(self::ALL[$code]) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $code)) {
                self::$more[(string) $code] = [$name . ' Acts', $name];
            }
        }
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::ALL + self::$more);
    }

    public static function exists(string $code): bool
    {
        return isset(self::ALL[$code]) || isset(self::$more[$code]);
    }

    /**
     * "Central Acts", "Kerala Acts": list heading, breadcrumb and menu label.
     */
    public static function name(string $code): string
    {
        return (self::ALL + self::$more)[$code][0] ?? throw new \InvalidArgumentException("Unknown jurisdiction $code");
    }

    /**
     * "Central (Parliament of India)", "Kerala": the Act Details row.
     */
    public static function detail(string $code): string
    {
        return (self::ALL + self::$more)[$code][1] ?? throw new \InvalidArgumentException("Unknown jurisdiction $code");
    }

    /**
     * Regex alternation of the codes, for the router.
     */
    public static function pattern(): string
    {
        return implode('|', array_map(static fn(string $c): string => preg_quote($c, '#'), self::codes()));
    }
}
