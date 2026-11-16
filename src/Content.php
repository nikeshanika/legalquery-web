<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Read-only access to content/ (written by the scraper's export-web command), format 3. One folder
 * per jurisdiction (listed in manifest.json, see Jurisdiction) with index.json (all Acts),
 * search.json (section headings) and one folder per Act, acts/<slug>/ with act.json,
 * sections/<slug>.json, schedules/<slug>.json, <tab>.json and its PDFs; files/<ab>.json maps the
 * PDF checksums starting with <ab> to their paths. Format: docs/CONTENT.md. An unknown
 * jurisdiction reads as empty.
 */
final class Content
{
    public const FORMAT = 3;

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
        return ($this->manifest()['format'] ?? null) === self::FORMAT && is_file($this->dir . '/' . Jurisdiction::DEFAULT . '/index.json');
    }

    /**
     * Every exported Act of one jurisdiction, newest enactment first. Each row's `jurisdiction` is
     * the folder it was read from.
     *
     * @return list<array<string, mixed>>
     */
    public function acts(string $jurisdiction): array
    {
        if (!Jurisdiction::exists($jurisdiction)) {
            return [];
        }
        return array_map(
            static fn(array $act): array => ['jurisdiction' => $jurisdiction] + $act,
            $this->read("$jurisdiction/index.json")['acts'] ?? [],
        );
    }

    /**
     * One Act (act.json): metadata, files, the section and schedule lists without their text,
     * and tab => number of documents. Null when there is no such Act.
     *
     * @return array<string, mixed>|null
     */
    public function act(string $jurisdiction, string $slug): ?array
    {
        if (!Jurisdiction::exists($jurisdiction) || !preg_match(self::SLUG, $slug)) {
            return null;
        }
        $act = $this->read("$jurisdiction/acts/$slug/act.json");
        return $act === null ? null : ['jurisdiction' => $jurisdiction] + $act;
    }

    /**
     * One section or schedule with its text and linked documents, or null.
     *
     * @param string $kind SECTION or SCHEDULE
     *
     * @return array<string, mixed>|null
     */
    public function provision(string $jurisdiction, string $act, string $kind, string $slug): ?array
    {
        if (!Jurisdiction::exists($jurisdiction) || !preg_match(self::SLUG, $act) || !preg_match(self::SLUG, $slug)) {
            return null;
        }
        $folder = $kind === 'SCHEDULE' ? 'schedules' : 'sections';
        $item = $this->read("$jurisdiction/acts/$act/$folder/$slug.json");
        return $item === null ? null : $item + ['kind' => $kind];
    }

    /**
     * The documents of one linked-document tab (rules, notifications, ...), newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function tab(string $jurisdiction, string $act, string $tab): array
    {
        if (!Jurisdiction::exists($jurisdiction) || !preg_match(self::SLUG, $act) || !preg_match(self::SLUG, $tab)) {
            return [];
        }
        return $this->read("$jurisdiction/acts/$act/$tab.json")['items'] ?? [];
    }

    /**
     * Year => number of Acts of one jurisdiction, newest year first.
     *
     * @return array<string, int>
     */
    public function years(string $jurisdiction): array
    {
        $years = [];
        foreach ($this->acts($jurisdiction) as $act) {
            if (preg_match('/^\d{4}$/', (string) $act['year'])) {
                $years[$act['year']] = ($years[$act['year']] ?? 0) + 1;
            }
        }
        krsort($years);
        return $years;
    }

    /**
     * Linked-document tab => number of Acts of one jurisdiction that have it, in the order of
     * $tabs; tabs no Act has are left out.
     *
     * @param list<string> $tabs e.g. ['rules', 'notifications']
     * @return array<string, int>
     */
    public function categories(string $jurisdiction, array $tabs): array
    {
        $counts = array_fill_keys($tabs, 0);
        foreach ($this->acts($jurisdiction) as $act) {
            foreach ($act['tabs'] ?? [] as $tab) {
                if (isset($counts[$tab])) {
                    $counts[$tab]++;
                }
            }
        }
        return array_filter($counts);
    }

    /**
     * Section and schedule headings of one jurisdiction: [act slug, provision slug, label, heading].
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    public function searchRows(string $jurisdiction): array
    {
        return Jurisdiction::exists($jurisdiction) ? ($this->read("$jurisdiction/search.json") ?? []) : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(): array
    {
        return $this->read('manifest.json') ?? [];
    }

    /**
     * The exported jurisdictions from manifest.json: code => display name ("CENTRAL", "Kerala").
     * Codes that are not URL-safe are left out.
     *
     * @return array<string, string>
     */
    public function jurisdictions(): array
    {
        $out = [];
        foreach ($this->manifest()['jurisdictions'] ?? [] as $row) {
            $code = is_array($row) ? ($row['code'] ?? null) : null;
            if (is_string($code) && preg_match(self::SLUG, $code)) {
                $out[$code] = is_string($row['name'] ?? null) && $row['name'] !== '' ? $row['name'] : $code;
            }
        }
        return $out;
    }

    /**
     * manifest.json counts of one jurisdiction (acts, sections, schedules), or [] if not exported.
     *
     * @return array<string, mixed>
     */
    public function counts(string $jurisdiction): array
    {
        foreach ($this->manifest()['jurisdictions'] ?? [] as $row) {
            if (is_array($row) && ($row['code'] ?? null) === $jurisdiction) {
                return $row;
            }
        }
        return [];
    }

    /**
     * Path of an exported PDF, or null. $sha256 must already be 64 hex characters.
     */
    public function pdfPath(string $sha256): ?string
    {
        if (!preg_match('/^[0-9a-f]{64}$/', $sha256)) {
            return null;
        }
        $relative = $this->read('files/' . substr($sha256, 0, 2) . '.json')[$sha256] ?? null;
        // The file index is the export's own, but its paths still must stay inside content/.
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
