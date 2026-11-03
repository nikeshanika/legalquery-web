<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Search over the exported index: Act titles / numbers / years, and section and schedule
 * headings (search.json). Every word of the query must appear; Hindi works as typed.
 */
final class Search
{
    /**
     * Lowercase words of a query (letters and digits of any script).
     *
     * @return list<string>
     */
    public static function words(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{N}\p{M}]+/u', mb_strtolower($query), -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_unique($words ?: []));
    }

    /**
     * Acts matching every word, best first: whole phrase in the title, then title words,
     * then newest. "37 of 2020" and "2020" match Act number / year.
     *
     * @param list<array<string, mixed>> $acts
     *
     * @return list<array<string, mixed>>
     */
    public static function acts(array $acts, string $query): array
    {
        $words = self::words($query);
        if (!$words) {
            return $acts;
        }
        $phrase = implode(' ', $words);
        $scored = [];
        foreach ($acts as $i => $act) {
            $title = self::normalise((string) $act['title']);
            $number = ltrim((string) $act['act_number'], '0');
            // "37 of 2020" is how Acts are cited.
            $haystack = "$title $number of {$act['year']} {$act['act_number']}";
            if (!self::containsAll($haystack, $words)) {
                continue;
            }
            $score = str_contains($title, $phrase) ? 2 : (self::containsAll($title, $words) ? 1 : 0);
            $scored[] = [$score, $i, $act];
        }
        // Stable: equal scores keep the index order (newest first).
        usort($scored, fn(array $a, array $b): int => [$b[0], $a[1]] <=> [$a[0], $b[1]]);
        return array_column($scored, 2);
    }

    /**
     * Sections and schedules whose label or heading has every word, with their Act.
     *
     * @param list<array{0: string, 1: string, 2: string, 3: string}> $rows search.json
     * @param array<string, array<string, mixed>> $actsBySlug
     *
     * @return list<array{act: array<string, mixed>, slug: string, label: string, heading: string}>
     */
    public static function provisions(array $rows, array $actsBySlug, string $query): array
    {
        $words = self::words($query);
        if (!$words) {
            return [];
        }
        $found = [];
        foreach ($rows as [$actSlug, $slug, $label, $heading]) {
            if (!isset($actsBySlug[$actSlug])) {
                continue;
            }
            $text = self::normalise($label . ' ' . $heading);
            if (self::containsAll($text, $words)) {
                $found[] = ['act' => $actsBySlug[$actSlug], 'slug' => $slug, 'label' => $label, 'heading' => $heading];
            }
        }
        return $found;
    }

    private static function normalise(string $text): string
    {
        return implode(' ', self::words($text));
    }

    /**
     * @param list<string> $words
     */
    private static function containsAll(string $haystack, array $words): bool
    {
        foreach ($words as $word) {
            if (!str_contains($haystack, $word)) {
                return false;
            }
        }
        return true;
    }
}
