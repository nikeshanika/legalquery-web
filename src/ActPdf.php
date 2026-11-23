<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * The readable URLs of an Act's own PDFs (owner, 2026-11-19): /acts/{jurisdiction}/{act}.pdf instead
 * of /files/{sha256}/{India Code name}.pdf. Only the URL changes; the PDF on disk keeps its name.
 *
 * Name of each PDF, from pdf_choices() (archived PDFs, each checksum once, English first):
 *
 * - English (and a PDF of unknown language): `{act}`
 * - English + Hindi in one PDF: `{act}-english-hindi`
 * - Hindi: `{act}-hindi`
 * - A State language (owner, 2026-11-23): `{act}-gujarati`, `{act}-malayalam` … (PDF_LANGUAGES)
 * - A second PDF that would get the same name: `-2`, `-3` … on the language word
 *   (`{act}-english-2`, `{act}-hindi-2`).
 *
 * Until 2026-11-23 the export tagged State-language PDFs English or unknown, so they were published
 * as `{act}-english-2` (or `{act}` when alone): moved() finds those old names for a 301.
 *
 * Linked documents (rules, notifications …) keep their /files/ URLs: they have no slugs.
 */
final class ActPdf
{
    /**
     * pdf_choices() of the Act's files, each with `name` (URL name without ".pdf") and `href`.
     *
     * @param array<string, mixed> $act act.json (with `jurisdiction`)
     *
     * @return list<array<string, mixed>>
     */
    public static function choices(array $act): array
    {
        return self::named($act, static fn(string $lang): string => \PDF_LANGUAGES[$lang][1] ?? 'english');
    }

    /**
     * The choice now published under an old name of a State-language PDF (see the class comment):
     * the names choices() gave when only English, Hindi and English + Hindi were known. Null when
     * $name was no such name.
     *
     * @param array<string, mixed> $act
     *
     * @return array<string, mixed>|null
     */
    public static function moved(array $act, string $name): ?array
    {
        $old = self::named($act, static fn(string $lang): string => in_array($lang, ['bi', 'hi'], true) ? \PDF_LANGUAGES[$lang][1] : 'english');
        foreach ($old as $was) {
            if ($was['name'] !== $name) {
                continue;
            }
            foreach (self::choices($act) as $now) {
                if ($now['sha256'] === $was['sha256']) {
                    return $now['name'] !== $name ? $now : null;
                }
            }
        }
        return null;
    }

    /**
     * @param array<string, mixed> $act
     * @param callable(string): string $wordOf URL word of a language code
     *
     * @return list<array<string, mixed>>
     */
    private static function named(array $act, callable $wordOf): array
    {
        $taken = [];
        $out = [];
        foreach (pdf_choices($act['files'] ?? []) as $choice) {
            $word = $wordOf($choice['lang']);
            $taken[$word] = ($taken[$word] ?? 0) + 1;
            $n = $taken[$word];
            $suffix = match (true) {
                $n > 1 => "-$word-$n",
                $word === 'english' => '',
                default => "-$word",
            };
            $name = $act['slug'] . $suffix;
            $out[] = $choice + ['name' => $name, 'href' => Url::actPdf((string) $act['jurisdiction'], $name)];
        }
        return $out;
    }

    /**
     * The Act PDF called $name (URL name without ".pdf"), or null.
     *
     * @param array<string, mixed> $act
     *
     * @return array<string, mixed>|null
     */
    public static function find(array $act, string $name): ?array
    {
        foreach (self::choices($act) as $choice) {
            if ($choice['name'] === $name) {
                return $choice;
            }
        }
        return null;
    }

    /**
     * Act slugs that a URL name can belong to: the name itself (main PDF), then the name without
     * each language suffix it ends with. The caller takes the first Act that has a PDF of that name.
     *
     * @return list<string>
     */
    public static function candidates(string $name): array
    {
        $out = [$name];
        $words = implode('|', array_diff(array_column(\PDF_LANGUAGES, 1), ['english', 'english-hindi', 'hindi']));
        foreach (['/-english-hindi(?:-\d+)?$/', '/-hindi(?:-\d+)?$/', '/-english-\d+$/', "/-(?:$words)(?:-\\d+)?$/"] as $suffix) {
            $act = (string) preg_replace($suffix, '', $name, 1, $replaced);
            if ($replaced && $act !== '') {
                $out[] = $act;
            }
        }
        return array_values(array_unique($out));
    }
}
