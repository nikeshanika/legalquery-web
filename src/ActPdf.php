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
 * - A second PDF that would get the same name: `-2`, `-3` … on the language word
 *   (`{act}-english-2`, `{act}-hindi-2`).
 * - AI translation (demo, `ai_translations_dir`; owner 2026-11-21): `{act}-hindi-ai`, `{act}-bengali-ai`.
 *
 * Linked documents (rules, notifications …) keep their /files/ URLs: they have no slugs.
 */
final class ActPdf
{
    private const WORD = ['en' => 'english', 'unknown' => 'english', 'bi' => 'english-hindi', 'hi' => 'hindi'];

    /**
     * pdf_choices() of the Act's files, each with `name` (URL name without ".pdf") and `href`.
     *
     * @param array<string, mixed> $act act.json (with `jurisdiction`)
     *
     * @return list<array<string, mixed>>
     */
    public static function choices(array $act): array
    {
        $taken = [];
        $out = [];
        foreach (pdf_choices($act['files'] ?? []) as $choice) {
            $word = self::WORD[$choice['lang']] ?? 'english';
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
        // AI translations (Content::aiFiles, demo): after the official PDFs, marked `ai`, never official.
        // No AI Hindi when the Act already has an official Hindi PDF (Hindi or English + Hindi; owner 2026-11-21).
        $officialHindi = (bool) array_intersect(['hi', 'bi'], array_column($out, 'lang'));
        foreach ($act['ai_files'] ?? [] as $file) {
            $lang = (string) ($file['language'] ?? '');
            if (!isset(Content::AI_LANGUAGES[$lang], self::AI_LABEL[$lang]) || ($lang === 'hi' && $officialHindi)) {
                continue;
            }
            $name = $act['slug'] . '-' . Content::AI_LANGUAGES[$lang];
            $out[] = $file + ['lang' => $lang, 'label' => self::AI_LABEL[$lang], 'ai' => true,
                'name' => $name, 'href' => Url::actPdf((string) $act['jurisdiction'], $name)];
        }
        return $out;
    }

    /** Button text of an AI translation: the language in its own script + "AI translation". */
    private const AI_LABEL = ['hi' => 'हिन्दी (AI अनुवाद)', 'bn' => 'বাংলা (AI অনুবাদ)'];

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
        foreach (['/-english-hindi(?:-\d+)?$/', '/-hindi(?:-\d+)?$/', '/-english-\d+$/', '/-(?:hindi|bengali)-ai$/'] as $suffix) {
            $act = (string) preg_replace($suffix, '', $name, 1, $replaced);
            if ($replaced && $act !== '') {
                $out[] = $act;
            }
        }
        return array_values(array_unique($out));
    }
}
