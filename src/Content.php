<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Read-only access to content/ (written by the scraper's export-web command), format 2:
 * central/index.json (all Acts), central/search.json (section headings), and one folder per Act,
 * central/acts/<slug>/ with act.json, sections/<slug>.json, schedules/<slug>.json, <tab>.json and
 * its PDFs; files.json maps a PDF checksum to its path. Format: docs/CONTENT.md.
 */
final class Content
{
    public const FORMAT = 2;

    private const SLUG = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    /** @var array<string, mixed> */
    private array $cache = [];

    public function __construct(private readonly string $dir)
    {
    }

    /**
     * True once an export in the format this site reads is in place.
     */
    public function ready(): bool
    {
        return ($this->manifest()['format'] ?? null) === self::FORMAT && is_file($this->dir . '/central/index.json');
    }

    /**
     * Every exported Act, newest enactment first.
     *
     * @return list<array<string, mixed>>
     */
    public function acts(): array
    {
        return $this->read('central/index.json')['acts'] ?? [];
    }

    /**
     * One Act (act.json): metadata, files, the section and schedule lists without their text,
     * and tab => number of documents. Null when there is no such Act.
     *
     * @return array<string, mixed>|null
     */
    public function act(string $slug): ?array
    {
        if (!preg_match(self::SLUG, $slug)) {
            return null;
        }
        return $this->read("central/acts/$slug/act.json");
    }

    /**
     * One section or schedule with its text and linked documents, or null.
     *
     * @param string $kind SECTION or SCHEDULE
     *
     * @return array<string, mixed>|null
     */
    public function provision(string $act, string $kind, string $slug): ?array
    {
        if (!preg_match(self::SLUG, $act) || !preg_match(self::SLUG, $slug)) {
            return null;
        }
        $folder = $kind === 'SCHEDULE' ? 'schedules' : 'sections';
        $item = $this->read("central/acts/$act/$folder/$slug.json");
        return $item === null ? null : $item + ['kind' => $kind];
    }

    /**
     * The documents of one linked-document tab (rules, notifications, ...), newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function tab(string $act, string $tab): array
    {
        if (!preg_match(self::SLUG, $act) || !preg_match(self::SLUG, $tab)) {
            return [];
        }
        return $this->read("central/acts/$act/$tab.json")['items'] ?? [];
    }

    /**
     * Year => number of Acts, newest year first.
     *
     * @return array<string, int>
     */
    public function years(): array
    {
        $years = [];
        foreach ($this->acts() as $act) {
            if (preg_match('/^\d{4}$/', (string) $act['year'])) {
                $years[$act['year']] = ($years[$act['year']] ?? 0) + 1;
            }
        }
        krsort($years);
        return $years;
    }

    /**
     * Linked-document tab => number of Acts that have it, in the order of $tabs; tabs no Act has
     * are left out.
     *
     * @param list<string> $tabs e.g. ['rules', 'notifications']
     * @return array<string, int>
     */
    public function categories(array $tabs): array
    {
        $counts = array_fill_keys($tabs, 0);
        foreach ($this->acts() as $act) {
            foreach ($act['tabs'] ?? [] as $tab) {
                if (isset($counts[$tab])) {
                    $counts[$tab]++;
                }
            }
        }
        return array_filter($counts);
    }

    /**
     * Section and schedule headings: [act slug, provision slug, label, heading].
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    public function searchRows(): array
    {
        return $this->read('central/search.json') ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        return $this->read('manifest.json') ?? [];
    }

    /**
     * Path of an exported PDF, or null. $sha256 must already be 64 hex characters.
     */
    public function pdfPath(string $sha256): ?string
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $sha256)) {
            return null;
        }
        $relative = $this->read('files.json')[$sha256] ?? null;
        // files.json is the export's own, but its paths still must stay inside content/.
        if (!is_string($relative) || !preg_match('#^(?:[A-Za-z0-9._-]+/)*[A-Za-z0-9_-][A-Za-z0-9._-]*\.pdf$#', $relative)
            || str_contains($relative, '..')) {
            return null;
        }
        $path = $this->dir . '/' . $relative;
        return is_file($path) ? $path : null;
    }

    /**
     * Decoded JSON file (cached for the request), or null when missing.
     *
     * @return array<mixed>|null
     */
    private function read(string $relative): ?array
    {
        if (array_key_exists($relative, $this->cache)) {
            return $this->cache[$relative];
        }
        $path = $this->dir . '/' . $relative;
        $data = null;
        if (is_file($path)) {
            try {
                $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                $data = is_array($decoded) ? $decoded : null;
            } catch (\JsonException $e) {
                error_log("legalquery-web: bad JSON in $path: " . $e->getMessage());
                throw new HttpError(500, 'Content file is damaged; re-run the export.');
            }
        }
        return $this->cache[$relative] = $data;
    }
}
