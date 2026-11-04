<?php

/**
 * Small helpers used by templates.
 */

declare(strict_types=1);

/**
 * Escapes text for HTML. Use it for every value printed in a template, except
 * section/summary HTML, which the scraper's export has already sanitised.
 */
function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * "2026-04-09" -> "9 Apr 2026"; anything else is returned as is.
 */
function format_date(?string $iso): string
{
    $iso = (string) $iso;
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', substr($iso, 0, 10));
    return $date ? $date->format('j M Y') : $iso;
}

function format_size(?int $bytes): string
{
    if (!$bytes) {
        return '';
    }
    if ($bytes < 1024 * 1024) {
        return max(1, (int) round($bytes / 1024)) . ' KB';
    }
    return number_format($bytes / 1024 / 1024, 1) . ' MB';
}

/**
 * Label for a linked-document collection or tab, e.g. RULE -> Rule / Rules.
 */
function collection_label(string $collection, bool $plural = false): string
{
    $labels = [
        'RULE' => ['Rule', 'Rules'],
        'NOTIFICATION' => ['Notification', 'Notifications'],
        'REGULATION' => ['Regulation', 'Regulations'],
        'ORDER' => ['Order', 'Orders'],
        'ORDINANCE' => ['Ordinance', 'Ordinances'],
        'STATUTE' => ['Statute', 'Statutes'],
        'CIRCULAR' => ['Circular', 'Circulars'],
        'APPENDIX' => ['Appendix', 'Appendices'],
        'ACT_AMENDMENT' => ['Amendment', 'Amendments'],
    ];
    if (isset($labels[$collection])) {
        return $labels[$collection][$plural ? 1 : 0];
    }
    $word = ucwords(strtolower(str_replace('_', ' ', $collection ?: 'Document')));
    return $plural ? $word . 's' : $word;
}

/**
 * "Section 2A", or "First Schedule" from the slug "first-schedule".
 *
 * @param array<string, mixed> $item A section (number) or schedule (kind SCHEDULE, slug).
 */
function provision_label(array $item): string
{
    if (($item['kind'] ?? 'SECTION') === 'SCHEDULE') {
        return ucwords(str_replace('-', ' ', (string) $item['slug']));
    }
    $number = trim((string) ($item['number'] ?? ''));
    return $number !== '' ? "Section $number" : 'Section';
}

/**
 * "Section 2A: Definitions" or "First Schedule". Schedule headings usually just repeat
 * the label ("THE FIRST SCHEDULE"), so they are left out then.
 *
 * @param array<string, mixed> $item
 */
function provision_heading(array $item): string
{
    $label = provision_label($item);
    $heading = rtrim(trim((string) ($item['heading'] ?? '')), '.');
    $repeats = ($item['kind'] ?? '') === 'SCHEDULE'
        && str_contains(mb_strtolower($heading), mb_strtolower($label));
    return $heading !== '' && !$repeats ? "$label: $heading" : $label;
}

/**
 * Plain text extracted from a PDF (schedules), tidied for display only: trailing spaces cut, runs of
 * blank lines become one. The words themselves are not touched.
 */
function tidy_text(string $text): string
{
    $lines = array_map('rtrim', preg_split('/\R/u', $text) ?: []);
    return trim(preg_replace('/\n{3,}/', "\n\n", implode("\n", $lines)) ?? '', "\n");
}

/**
 * The one PDF shown for a document or Act: English only for now, no language buttons (owner,
 * 2026-11-04). Preference en, bi, other, then hi (so a Hindi-only document still has its PDF).
 * Files not archived yet are skipped.
 *
 * @param list<array<string, mixed>> $files
 *
 * @return array<string, mixed>|null
 */
function english_file(array $files): ?array
{
    $rank = ['en' => 0, 'bi' => 1, 'hi' => 3];
    $best = null;
    foreach ($files as $file) {
        if (empty($file['sha256'])) {
            continue;
        }
        $r = $rank[$file['language'] ?? ''] ?? 2;
        if ($best === null || $r < $best[0]) {
            $best = [$r, $file];
        }
    }
    return $best[1] ?? null;
}
