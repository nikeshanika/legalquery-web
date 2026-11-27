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

    /**
     * AI translations shown as extra Act PDFs (demo, owner 2026-11-21): language => file name word, in popup
     * order. Every language of the translation handover (2026-11-25); keep in step with the ledger's
     * SITE_LANGUAGES (../translation-ledger/ledger.py).
     */
    public const AI_LANGUAGES = [
        'hi' => 'hindi-ai', 'bn' => 'bengali-ai', 'mr' => 'marathi-ai', 'gu' => 'gujarati-ai', 'kn' => 'kannada-ai',
        'ml' => 'malayalam-ai', 'or' => 'odia-ai', 'pa' => 'punjabi-ai', 'ta' => 'tamil-ai', 'te' => 'telugu-ai',
    ];

    /**
     * @param string $aiDir The site's `translations/` folder (filled by ../translation-ledger `stage`), or '' = off.
     *   An Act PDF with checksum S gets an AI translation in language L when S/L/vN/document.pdf exists
     *   (the highest N).
     * @param list<string> $aiOnly Only these source checksums (empty = every one found).
     */
    public function __construct(private readonly string $dir, private readonly string $aiDir = '', private readonly array $aiOnly = [])
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
        if ($act === null) {
            return null;
        }
        $act = ['jurisdiction' => $jurisdiction] + $act;
        if ($this->aiDir !== '') {
            $act['ai_files'] = $this->aiFiles($act['files'] ?? []);
        }
        return $act;
    }

    /**
     * AI translations of the Act's PDFs, read from the indiacode project (not from content/): one entry
     * per language, `{filename, language, size, sha256, path (absolute), source_sha256}`.
     *
     * @param array<mixed> $files act.json files
     *
     * @return list<array<string, mixed>>
     */
    private function aiFiles(array $files): array
    {
        $out = [];
        foreach ($files as $file) {
            $source = (string) ($file['sha256'] ?? '');
            if (!preg_match('/^[0-9a-f]{64}$/', $source) || ($this->aiOnly && !in_array($source, $this->aiOnly, true))) {
                continue;
            }
            foreach (self::AI_LANGUAGES as $lang => $word) {
                $path = isset($out[$lang]) ? null : $this->aiNewest("$this->aiDir/$source/$lang");
                if ($path === null) {
                    continue;
                }
                $base = preg_replace('/\.pdf$/i', '', (string) ($file['filename'] ?? 'document')) ?: 'document';
                $out[$lang] = [
                    'filename' => "$base-$word.pdf",
                    'language' => $lang,
                    'size' => (int) filesize($path),
                    'sha256' => (string) hash_file('sha256', $path),
                    'path' => $path,
                    'source_sha256' => $source,
                ];
            }
        }
        return array_values($out);
    }

    /** document.pdf of the highest vN folder in $langDir that has one, or null. */
    private function aiNewest(string $langDir): ?string
    {
        $best = null;
        $bestN = 0;
        foreach (glob("$langDir/v*", GLOB_ONLYDIR) ?: [] as $dir) {
            if (preg_match('/\/v([1-9]\d*)$/', $dir, $m) && (int) $m[1] > $bestN && is_file("$dir/document.pdf")) {
                $bestN = (int) $m[1];
                $best = "$dir/document.pdf";
            }
        }
        return $best;
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
     * The documents of one linked-document tab (rules, notifications, ...), newest first. Each file with
     * a `path` gets `f` and `href`, its /pdf URL (Url::linkedPdf; owner, 2026-11-24).
     *
     * `f` is the file's name on disk without ".pdf", lowercased (the export keeps names unique ignoring
     * case within a folder). A tab can list files from other folders; when two different files would get
     * the same `f`, the later one (in the tab's order) gets its folder in front (`rules-…`, `pdf-…`), and
     * `-2`, `-3` … if still taken. The tab's order is the export's, so `f` stays the same between requests.
     *
     * @return list<array<string, mixed>>
     */
    public function tab(string $jurisdiction, string $act, string $tab): array
    {
        if (!Jurisdiction::exists($jurisdiction) || !preg_match(self::SLUG, $act) || !preg_match(self::SLUG, $tab)) {
            return [];
        }
        $key = "tab:$jurisdiction/$act/$tab";
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }
        $items = $this->read("$jurisdiction/acts/$act/$tab.json")['items'] ?? [];
        $byName = [];
        foreach ($items as &$item) {
            if (!is_array($item['files'] ?? null)) {
                continue;
            }
            foreach ($item['files'] as &$file) {
                $path = (string) ($file['path'] ?? '');
                if (!preg_match('#/pdf/(?:([^/]+)/)?([A-Za-z0-9._-]+)\.pdf$#i', $path, $m)) {
                    continue;
                }
                $f = strtolower($m[2]);
                if (isset($byName[$f]) && $byName[$f] !== $path) {
                    $f = strtolower(($m[1] !== '' ? $m[1] : 'pdf') . '-' . $m[2]);
                    for ($n = 2, $base = $f; isset($byName[$f]) && $byName[$f] !== $path; $n++) {
                        $f = "$base-$n";
                    }
                }
                $byName[$f] = $path;
                $file['f'] = $f;
                $file['href'] = Url::linkedPdf($jurisdiction, $act, $tab, $f);
            }
            unset($file);
        }
        unset($item);
        return $this->cache[$key] = $items;
    }

    /**
     * The file of a linked-document tab whose `f` is $f (see tab()), or null. Adds `title`, `date`, `tab` and `item_files`
     * (every file of that document, for its other-language PDFs on the download page).
     *
     * @return array<string, mixed>|null
     */
    public function linkedFile(string $jurisdiction, string $act, string $tab, string $f): ?array
    {
        foreach ($this->tab($jurisdiction, $act, $tab) as $item) {
            foreach ($item['files'] ?? [] as $file) {
                if (($file['f'] ?? null) === $f) {
                    return $file + ['title' => (string) ($item['title'] ?? ''), 'date' => (string) ($item['date'] ?? ''), 'tab' => $tab, 'item_files' => $item['files']];
                }
            }
        }
        return null;
    }

    /**
     * Every PDF linked from the Act's tabs or from its sections and schedules, by checksum, each with `href`,
     * its one /pdf URL. A PDF in a tab ($tabs, in this order) keeps that tab's URL (Url::linkedPdf). One linked
     * only from sections or schedules gets /pdf?j=…&a=…&s={provision}&f={file} (Url::provisionPdf; owner,
     * 2026-11-26), from the first section (else schedule) that links it, and also `slug` (that provision),
     * `f`, `doc` (the linked document there: title, date, files …) and `file`.
     *
     * `f` is chosen as in tab(): the file name on disk without ".pdf", lowercased; when two different files of
     * one provision would get the same `f`, the later one gets its folder in front, then `-2`, `-3` …. Only
     * provisions with documents (`docs` in act.json) are read, in act.json's order, so URLs stay the same
     * between requests.
     *
     * @param array<string, mixed> $act act.json (with `jurisdiction`)
     * @param list<string> $tabs the linked-document tabs, in order
     *
     * @return array<string, array<string, mixed>>
     */
    public function pdfLinks(array $act, array $tabs): array
    {
        [$j, $a] = [(string) $act['jurisdiction'], (string) $act['slug']];
        $key = "links:$j/$a";
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $links = [];
        foreach ($tabs as $tab) {
            if (empty($act['tabs'][$tab])) {
                continue;
            }
            foreach ($this->tab($j, $a, $tab) as $item) {
                foreach ($item['files'] ?? [] as $file) {
                    if (isset($file['href'], $file['sha256'])) {
                        $links[(string) $file['sha256']] ??= ['href' => (string) $file['href']];
                    }
                }
            }
        }
        foreach (['SECTION' => 'sections', 'SCHEDULE' => 'schedules'] as $kind => $list) {
            foreach ((array) ($act[$list] ?? []) as $entry) {
                if (empty($entry['docs']) || !isset($entry['slug'])) {
                    continue;
                }
                $slug = (string) $entry['slug'];
                $byName = [];
                foreach ((array) ($this->provision($j, $a, $kind, $slug)['linked'] ?? []) as $doc) {
                    foreach ((array) ($doc['files'] ?? []) as $file) {
                        $sha = (string) ($file['sha256'] ?? '');
                        $path = (string) ($file['path'] ?? '');
                        if ($sha === '' || isset($links[$sha]) || !preg_match('#/pdf/(?:([^/]+)/)?([A-Za-z0-9._-]+)\.pdf$#i', $path, $m)) {
                            continue;
                        }
                        $f = strtolower($m[2]);
                        if (isset($byName[$f]) && $byName[$f] !== $path) {
                            $f = strtolower(($m[1] !== '' ? $m[1] : 'pdf') . '-' . $m[2]);
                            for ($n = 2, $base = $f; isset($byName[$f]) && $byName[$f] !== $path; $n++) {
                                $f = "$base-$n";
                            }
                        }
                        $byName[$f] = $path;
                        $links[$sha] = ['href' => Url::provisionPdf($j, $a, $slug, $f), 'slug' => $slug, 'f' => $f, 'doc' => (array) $doc, 'file' => $file];
                    }
                }
            }
        }
        return $this->cache[$key] = $links;
    }

    /**
     * The document linked only from provision $slug whose `f` is $f (pdfLinks()), or null.
     *
     * @param array<string, mixed> $act act.json (with `jurisdiction`)
     * @param list<string> $tabs the linked-document tabs, in order
     *
     * @return array<string, mixed>|null
     */
    public function provisionFile(array $act, array $tabs, string $slug, string $f): ?array
    {
        foreach ($this->pdfLinks($act, $tabs) as $link) {
            if (($link['slug'] ?? null) === $slug && $link['f'] === $f) {
                return $link;
            }
        }
        return null;
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
        // The file index is the export's own, but its paths still must stay inside content/: no "." or ".."
        // folder. Dots inside a name are fine ("…_dt_18..11.2019.pdf", "…30.05.2018...hindi.pdf").
        if (!is_string($relative) || !preg_match('#^(?:[A-Za-z0-9._-]+/)*[A-Za-z0-9_-][A-Za-z0-9._-]*\.pdf$#', $relative)
            || preg_match('#(?:^|/)\.{1,2}/#', $relative)) {
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
