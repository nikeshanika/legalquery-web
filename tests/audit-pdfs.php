<?php

/**
 * Audit of every PDF the site offers, against the real content/ (not the fixtures):
 *
 *   php -d memory_limit=4G tests/audit-pdfs.php [--hash] [--text=N] [--csv=report.csv] [--only=central]
 *
 * For every Act PDF, every document in a Rules / Notifications / … tab and every document linked from a
 * section or schedule, it builds the download page's data with the site's own code (FileController::info,
 * the same as the page shows) and checks:
 *
 * - the PDF: listed in files/<ab>.json, the file exists, starts with %PDF-, ends with %%EOF (last 2 KB),
 *   its size matches the export's `size`; with --hash its sha256 matches too (reads every byte, slow);
 * - the link: a tab document's /pdf URL leads back to the same file; so does a section document's (its tab's
 *   URL when a tab lists it, else /pdf?…&s={first section linking it}&f=…), and its old /files/ URL goes there;
 * - the title: not empty, not junk (legacy-font Hindi, "Microsoft Word - …", "C:\…", "….p65", "Untitled");
 * - the language: set for the header menu (data-pdf-lang), as the export's `language`;
 * - the back link and every "Other PDFs" link open a page that exists (Act, tab with documents, section);
 * - PdfTitle can give the served PDF its title (else the browser shows the file's own Title);
 * - with --text=N: for N PDFs (spread over the list), the text's script matches the language (en: Latin,
 *   hi: Devanagari, bi: both); scanned PDFs (no text) are counted, not failed. Needs pdftotext.
 *
 * Prints a summary with up to 5 examples per problem; --csv writes every problem. Exit code 1 if any.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use LegalQueryWeb\ActPdf;
use LegalQueryWeb\Content;
use LegalQueryWeb\Controller\ActController;
use LegalQueryWeb\Controller\FileController;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\PdfTitle;
use LegalQueryWeb\Router;
use LegalQueryWeb\Url;
use LegalQueryWeb\View;

$opts = getopt('', ['hash', 'text::', 'csv:', 'only:']);
$config = require dirname(__DIR__) . '/config.php';
$dir = (string) $config['content_dir'];
$content = new Content($dir);
Jurisdiction::register($content->jurisdictions());
$view = new View(dirname(__DIR__) . '/templates', $config, '/');
$controller = new FileController($content, $view, $config, []);
$info = new ReflectionMethod(FileController::class, 'info');
$hrefOf = new ReflectionMethod(FileController::class, 'hrefOf');

$problems = [];
$counts = ['act pdf' => 0, 'tab document pdf' => 0, 'section document pdf' => 0];
$langs = [];
$checkedPdf = [];
$textQueue = [];
$problem = static function (string $kind, string $where, string $detail = '') use (&$problems): void {
    $problems[$kind][] = [$where, $detail];
};

function junk_title(string $t): ?string
{
    return match (true) {
        trim($t) === '' => 'empty',
        preg_match_all('/[ÉÆÇ®£¶ºÊ]/u', $t) >= 2, str_contains($t, 'à¤') => 'legacy-font or mis-decoded text',
        (bool) preg_match('/^Microsoft Word - /i', $t) => 'Word file name',
        (bool) preg_match('#^[A-Z]:\\\\#', $t) => 'Windows path',
        (bool) preg_match('/\.(p65|docx?|pdf|jpe?g)$/i', $t) => 'file name',
        (bool) preg_match('/^(untitled|document\d*)$/i', trim($t)) => 'placeholder',
        default => null,
    };
}

/** Whether a site URL opens a page that exists (not a 404). */
function url_ok(Content $content, string $url): bool
{
    $path = (string) parse_url($url, PHP_URL_PATH);
    parse_str((string) parse_url($url, PHP_URL_QUERY), $q);
    $anchor = (string) parse_url($url, PHP_URL_FRAGMENT);
    $match = Router::match($path);
    if ($match === null) {
        return false;
    }
    [[$class, $action], $p] = $match;
    if ($class === FileController::class && $action === 'pdf') {
        return $content->pdfPath($p['sha']) !== null;
    }
    if ($class === FileController::class && $action === 'page') {
        $act = $content->act((string) ($q['j'] ?? ''), (string) ($q['a'] ?? ''));
        if ($act === null) {
            return false;
        }
        if (isset($q['s'])) {
            return $content->provisionFile($act, array_keys(ActController::TABS), (string) $q['s'], (string) ($q['f'] ?? '')) !== null;
        }
        if (isset($q['f'])) {
            return $content->linkedFile($q['j'], $q['a'], (string) ($q['d'] ?? ''), (string) $q['f']) !== null;
        }
        return ActPdf::byLanguage($act, (string) ($q['v'] ?? '')) !== null;
    }
    if (!isset($p['act'], $p['jurisdiction'])) {
        return $path === '/';
    }
    $act = $content->act($p['jurisdiction'], $p['act']);
    if ($act === null) {
        return false;
    }
    if ($action === 'tab') {
        return !empty($act['tabs'][$p['tab']]) && $content->tab($p['jurisdiction'], $p['act'], $p['tab']) !== [];
    }
    if ($anchor !== '') {
        $slugs = array_merge(array_column($act['sections'] ?? [], 'slug'), array_column($act['schedules'] ?? [], 'slug'));
        return in_array($anchor, $slugs, true);
    }
    return true;
}

$checkPdf = static function (array $file, string $where) use ($content, $dir, $opts, $problem, &$checkedPdf): ?string {
    $sha = (string) ($file['sha256'] ?? '');
    $path = $content->pdfPath($sha);
    if ($path === null) {
        $problem('pdf not in the file index or missing', $where, $sha);
        return null;
    }
    if (isset($checkedPdf[$sha])) {
        return $path;
    }
    $checkedPdf[$sha] = true;
    $size = (int) filesize($path);
    $fh = fopen($path, 'rb');
    $head = (string) fread($fh, 8);
    fseek($fh, max(0, $size - 2048));
    $tail = (string) fread($fh, 2048);
    fclose($fh);
    if (!str_starts_with($head, '%PDF-')) {
        $problem('not a pdf (no %PDF- header)', $where, substr($path, strlen($dir) + 1));
    } elseif (!str_contains($tail, '%%EOF')) {
        $problem('pdf cut short (no %%EOF at the end)', $where, substr($path, strlen($dir) + 1));
    }
    if (isset($file['size']) && (int) $file['size'] !== $size) {
        $problem('size differs from the export', $where, "export {$file['size']}, file $size");
    }
    if (isset($opts['hash']) && hash_file('sha256', $path) !== $sha) {
        $problem('sha256 differs (wrong file)', $where, substr($path, strlen($dir) + 1));
    }
    return $path;
};

$checkPage = static function (array $data, string $where, string $kind, string $lang) use ($content, $problem, &$langs, &$textQueue): void {
    if (($why = junk_title((string) $data['title'])) !== null) {
        $problem("title: $why", $where, (string) $data['title']);
    }
    if ($data['lang'] === '') {
        $problem('no language for the header menu', $where);
    }
    $langs[$kind][$data['lang']] = ($langs[$kind][$data['lang']] ?? 0) + 1;
    if (!url_ok($content, (string) $data['back'])) {
        $problem('back link opens no page', $where, (string) $data['back']);
    }
    foreach ($data['others'] as $other) {
        if (!url_ok($content, (string) $other['href'])) {
            $problem('"Other PDFs" link opens no page', $where, (string) $other['href']);
        }
    }
};

foreach (array_keys($content->jurisdictions()) as $j) {
    if (isset($opts['only']) && $opts['only'] !== $j) {
        continue;
    }
    foreach ($content->acts($j) as $row) {
        $act = $content->act($j, (string) $row['slug']);
        if ($act === null) {
            $problem('act listed but no act.json', "$j/{$row['slug']}");
            continue;
        }
        $slug = (string) $act['slug'];
        // The Act's own PDFs.
        foreach (ActPdf::choices($act) as $choice) {
            if (!empty($choice['ai'])) {
                continue;
            }
            $counts['act pdf']++;
            $where = $choice['href'];
            $path = $checkPdf($choice, $where);
            if ($path === null) {
                continue;
            }
            $data = $info->invoke($controller, $path, basename($path), $act, $choice);
            $checkPage($data, $where, 'act pdf', (string) $choice['lang']);
            $textQueue[] = [$path, (string) $choice['lang'], $where];
            if (PdfTitle::suffix($path, (string) $data['title']) === '') {
                $problem('served pdf keeps its own title (PdfTitle gave up)', $where);
            }
        }
        // Documents in the tabs.
        foreach (array_keys(array_filter((array) ($act['tabs'] ?? []))) as $tab) {
            if (!isset(ActController::TABS[$tab])) {
                continue;
            }
            foreach ($content->tab($j, $slug, $tab) as $item) {
                foreach ((array) ($item['files'] ?? []) as $file) {
                    if (!isset($file['href'])) {
                        continue;
                    }
                    $counts['tab document pdf']++;
                    $where = (string) $file['href'];
                    $linked = $content->linkedFile($j, $slug, $tab, (string) $file['f']);
                    if ($linked === null || ($linked['sha256'] ?? null) !== ($file['sha256'] ?? null)) {
                        $problem('tab link leads to another file', $where, (string) ($linked['filename'] ?? 'none'));
                        continue;
                    }
                    $path = $checkPdf($file, $where);
                    if ($path === null) {
                        continue;
                    }
                    $data = $info->invoke($controller, $path, basename($path), $act, null, $linked);
                    $checkPage($data, $where, 'tab document pdf', (string) ($file['language'] ?? ''));
                    $textQueue[] = [$path, (string) $data['lang'], $where];
                    if (PdfTitle::suffix($path, (string) $data['title']) === '') {
                        $problem('served pdf keeps its own title (PdfTitle gave up)', $where);
                    }
                }
            }
        }
        // Documents linked from sections and schedules.
        foreach (['SECTION' => 'sections', 'SCHEDULE' => 'schedules'] as $pkind => $list) {
            foreach ((array) ($act[$list] ?? []) as $entry) {
                if (empty($entry['docs'])) {
                    continue;
                }
                $item = $content->provision($j, $slug, $pkind, (string) $entry['slug']);
                if (count((array) ($item['linked'] ?? [])) !== (int) $entry['docs']) {
                    $problem('docs count differs from the section file', "/acts/$j/$slug#{$entry['slug']}", "act.json {$entry['docs']}, file " . count((array) ($item['linked'] ?? [])));
                }
                foreach ((array) ($item['linked'] ?? []) as $doc) {
                    foreach ((array) ($doc['files'] ?? []) as $file) {
                        if (empty($file['sha256'])) {
                            continue;
                        }
                        $counts['section document pdf']++;
                        $where = "/acts/$j/$slug#{$entry['slug']}: " . ($file['filename'] ?? '');
                        $path = $checkPdf($file, $where);
                        if ($path === null) {
                            continue;
                        }
                        $link = $content->pdfLinks($act, array_keys(ActController::TABS))[(string) $file['sha256']] ?? null;
                        if ($link === null) {
                            $problem('section document has no /pdf URL', $where, (string) ($file['path'] ?? ''));
                            continue;
                        }
                        if (!isset($link['slug']) || $link['slug'] !== $entry['slug']) {
                            continue;  // listed in a tab, or linked from an earlier provision too: checked there
                        }
                        // Its /pdf?…&s=…&f=… URL leads back to the same file, and the old /files/ URL to it.
                        parse_str((string) parse_url((string) $link['href'], PHP_URL_QUERY), $q);
                        $back = $content->provisionFile($act, array_keys(ActController::TABS), (string) ($q['s'] ?? ''), (string) ($q['f'] ?? ''));
                        if (($back['file']['sha256'] ?? null) !== $file['sha256'] || ($q['j'] ?? '') !== $j || ($q['a'] ?? '') !== $slug) {
                            $problem('section document URL leads to another file', $where, (string) $link['href']);
                            continue;
                        }
                        // The old /files/ URL goes to a /pdf URL: this one, or the same file's under the Act whose folder
                        // holds it (files/<ab>.json keeps one copy per checksum, e.g. the Central Act's for a UT copy).
                        $old = $hrefOf->invoke($controller, $path, (string) $file['sha256']);
                        if ($old === null || !url_ok($content, (string) $old)) {
                            $problem('old /files/ URL goes to no /pdf URL', $where, (string) $old);
                        }
                        $data = $info->invoke($controller, $path, basename((string) $file['filename']), $act, null, null, $link);
                        $checkPage($data, $where, 'section document pdf', (string) ($file['language'] ?? ''));
                        $textQueue[] = [$path, (string) $data['lang'], $where];
                    }
                }
            }
        }
    }
}

// Language against the text's script, for a spread-out sample.
$textStats = [];
$n = isset($opts['text']) ? max(1, (int) ($opts['text'] ?: 200)) : 0;
if ($n > 0 && $textQueue) {
    $step = max(1, intdiv(count($textQueue), $n));
    for ($i = 0; $i < count($textQueue) && $i / $step < $n; $i += $step) {
        [$path, $lang, $where] = $textQueue[$i];
        $text = (string) shell_exec('pdftotext -l 5 ' . escapeshellarg($path) . ' - 2>/dev/null');
        $dev = preg_match_all('/[\x{0900}-\x{097F}]/u', $text);
        $lat = preg_match_all('/[A-Za-z]/', $text);
        if ($dev + $lat < 200) {
            $textStats['scanned (no text to check)'] = ($textStats['scanned (no text to check)'] ?? 0) + 1;
            continue;
        }
        $seen = match (true) {
            $dev > 0.15 * ($dev + $lat) && $lat > 0.15 * ($dev + $lat) => 'bi',
            $dev > $lat => 'hi',
            default => 'en',
        };
        $textStats["checked"] = ($textStats['checked'] ?? 0) + 1;
        $ok = match ($lang) {
            'en' => $seen !== 'hi',  // Gazette headers add some Devanagari to English PDFs
            'hi' => $seen !== 'en',
            'bi' => true,            // a bilingual Gazette can be mostly one script
            default => true,
        };
        if (!$ok) {
            $problem("language: marked $lang, text looks $seen", $where, sprintf('Devanagari %d, Latin %d', $dev, $lat));
        }
    }
}

echo "PDFs checked: " . json_encode($counts) . ', distinct files: ' . count($checkedPdf) . "\n";
foreach ($langs as $kind => $by) {
    arsort($by);
    echo "language ($kind): " . json_encode($by, JSON_UNESCAPED_UNICODE) . "\n";
}
if ($textStats) {
    echo 'text check: ' . json_encode($textStats) . "\n";
}
ksort($problems);
echo $problems ? "\nProblems:\n" : "\nNo problems found.\n";
foreach ($problems as $kind => $list) {
    echo sprintf("  %-55s %6d\n", $kind, count($list));
    foreach (array_slice($list, 0, 5) as [$where, $detail]) {
        echo "      $where" . ($detail !== '' ? "  [$detail]" : '') . "\n";
    }
}
if (isset($opts['csv'])) {
    $out = fopen((string) $opts['csv'], 'wb');
    fputcsv($out, ['problem', 'where', 'detail'], ',', '"', '');
    foreach ($problems as $kind => $list) {
        foreach ($list as [$where, $detail]) {
            fputcsv($out, [$kind, $where, $detail], ',', '"', '');
        }
    }
    fclose($out);
    echo "\nAll problems: {$opts['csv']}\n";
}
exit($problems ? 1 : 0);
