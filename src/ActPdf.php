<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * The PDFs of an Act and their URLs.
 *
 * Since 2026-11-24 (owner) every Act PDF is at /pdf?j={jurisdiction}&a={act}[&v={language}] (Url::actPdf):
 * that URL shows the download page, and sends the PDF only after the PDF question. `v` of each PDF, from
 * pdf_choices() (archived PDFs, each checksum once, English first):
 *
 * - the Act's first English PDF (or a PDF of unknown language): no `v`
 * - any other: its language code, `hi`, `bi` (English + Hindi), `gu`, `ml` … (PDF_LANGUAGES)
 * - a second PDF of the same language: `-2`, `-3` … (`en-2`, `hi-2`)
 * - AI translation (demo, `ai_translations_dir`; owner 2026-11-21): `hi-ai`, `bn-ai`, `mr-ai` … (Content::AI_LANGUAGES),
 *   after the official PDFs
 *
 * Before that (2026-11-19 to 2026-11-24) the PDFs had readable .pdf URLs, kept as 301s (FileController):
 * `{act}`, `{act}-english-hindi`, `{act}-hindi`, `{act}-gujarati` … (`name` of each choice, also the
 * downloaded file's name), and `{act}-english-2` for a second PDF of a language. Until 2026-11-23 the
 * export tagged State-language PDFs English or unknown, so they were published as `{act}-english-2`
 * (or `{act}` when alone): moved() finds those old names.
 *
 * Linked documents (rules, notifications …) get theirs from Content::tab().
 */
final class ActPdf
{
    /**
     * pdf_choices() of the Act's files, each with `v` (URL language, '' for the first English PDF),
     * `name` (old .pdf URL name without ".pdf", the downloaded file's name) and `href` (its /pdf URL).
     *
     * @param array<string, mixed> $act act.json (with `jurisdiction`)
     *
     * @return list<array<string, mixed>>
     */
    public static function choices(array $act): array
    {
        $taken = [];
        $out = [];
        foreach (self::named($act, static fn(string $lang): string => \PDF_LANGUAGES[$lang][1] ?? 'english') as $choice) {
            $code = $choice['lang'] === 'unknown' || !isset(\PDF_LANGUAGES[$choice['lang']]) ? 'en' : (string) $choice['lang'];
            $taken[$code] = ($taken[$code] ?? 0) + 1;
            $n = $taken[$code];
            $v = match (true) {
                $n > 1 => "$code-$n",
                $code === 'en' => '',
                default => $code,
            };
            $out[] = ['v' => $v, 'href' => Url::actPdf((string) $act['jurisdiction'], (string) $act['slug'], $v)] + $choice;
        }
        // AI translations (Content::aiFiles, demo): after the official PDFs, marked `ai`, never official.
        // No AI Hindi when the Act already has an official Hindi PDF (Hindi or English + Hindi; owner 2026-11-21).
        $officialHindi = (bool) array_intersect(['hi', 'bi'], array_column($out, 'lang'));
        foreach ($act['ai_files'] ?? [] as $file) {
            $lang = (string) ($file['language'] ?? '');
            if (!isset(Content::AI_LANGUAGES[$lang], self::AI_LABEL[$lang]) || ($lang === 'hi' && $officialHindi)) {
                continue;
            }
            $v = "$lang-ai";
            $out[] = $file + ['v' => $v, 'lang' => $lang, 'label' => self::AI_LABEL[$lang], 'ai' => true,
                'name' => $act['slug'] . '-' . Content::AI_LANGUAGES[$lang],
                'href' => Url::actPdf((string) $act['jurisdiction'], (string) $act['slug'], $v)];
        }
        return $out;
    }

    /** Button text of an AI translation: the language in its own script + "AI translation". */
    private const AI_LABEL = [
        'hi' => 'हिन्दी (AI अनुवाद)', 'bn' => 'বাংলা (AI অনুবাদ)', 'mr' => 'मराठी (AI भाषांतर)',
        'gu' => 'ગુજરાતી (AI અનુવાદ)', 'kn' => 'ಕನ್ನಡ (AI ಅನುವಾದ)', 'ml' => 'മലയാളം (AI വിവർത്തനം)',
        'or' => 'ଓଡ଼ିଆ (AI ଅନୁବାଦ)', 'pa' => 'ਪੰਜਾਬੀ (AI ਅਨੁਵਾਦ)', 'ta' => 'தமிழ் (AI மொழிபெயர்ப்பு)',
        'te' => 'తెలుగు (AI అనువాదం)',
    ];

    /**
     * The Act PDF whose `v` is $v ('' = the first English PDF), or null.
     *
     * @param array<string, mixed> $act
     *
     * @return array<string, mixed>|null
     */
    public static function byLanguage(array $act, string $v): ?array
    {
        foreach (self::choices($act) as $choice) {
            if ($choice['v'] === $v) {
                return $choice;
            }
        }
        return null;
    }

    /**
     * The choice that was published under an old name of a State-language PDF (see the class comment):
     * the names given when only English, Hindi and English + Hindi were known. Null when $name was no
     * such name.
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
     * pdf_choices() with `name`: the Act slug plus the URL word of its language ($wordOf).
     *
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
            $out[] = $choice + ['name' => $act['slug'] . $suffix];
        }
        return $out;
    }

    /**
     * The Act PDF that had the old .pdf URL name $name (URL name without ".pdf"), or null.
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
     * Act slugs that an old .pdf URL name can belong to: the name itself (main PDF), then the name without
     * each language suffix it ends with. The caller takes the first Act that has a PDF of that name.
     *
     * @return list<string>
     */
    public static function candidates(string $name): array
    {
        $out = [$name];
        $words = implode('|', array_diff(array_column(\PDF_LANGUAGES, 1), ['english', 'english-hindi', 'hindi']));
        foreach (['/-english-hindi(?:-\d+)?$/', '/-hindi(?:-\d+)?$/', '/-english-\d+$/', "/-(?:$words)(?:-\\d+)?$/", '/-(?:hindi|bengali)-ai$/'] as $suffix) {
            $act = (string) preg_replace($suffix, '', $name, 1, $replaced);
            if ($replaced && $act !== '') {
                $out[] = $act;
            }
        }
        return array_values(array_unique($out));
    }
}
