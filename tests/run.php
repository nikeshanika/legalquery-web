<?php

/**
 * Offline tests, no dependencies:  php tests/run.php
 *
 * Covers routing, canonical paths, pagination, URLs, search and the content reader
 * (against tests/fixtures/content). The full site is checked by hand in the browser.
 */

declare(strict_types=1);

require dirname(__DIR__) . '/src/bootstrap.php';

use LegalQueryWeb\App;
use LegalQueryWeb\Content;
use LegalQueryWeb\HttpError;
use LegalQueryWeb\Pager;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Router;
use LegalQueryWeb\Search;
use LegalQueryWeb\Seo;
use LegalQueryWeb\Url;

$failures = 0;
$count = 0;

function check(string $name, mixed $actual, mixed $expected): void
{
    global $failures, $count;
    $count++;
    if ($actual !== $expected) {
        $failures++;
        echo "FAIL $name\n  expected: " . var_export($expected, true) . "\n  actual:   " . var_export($actual, true) . "\n";
    }
}

function throws(string $name, callable $fn, string $class, ?int $status = null): void
{
    global $failures, $count;
    $count++;
    try {
        $fn();
    } catch (\Throwable $e) {
        if ($e instanceof $class && ($status === null || ($e->status ?? null) === $status)) {
            return;
        }
        $failures++;
        echo "FAIL $name: got " . get_class($e) . ' ' . $e->getMessage() . "\n";
        return;
    }
    $failures++;
    echo "FAIL $name: nothing thrown\n";
}

function route(string $path): ?string
{
    $match = Router::match($path);
    if (!$match) {
        return null;
    }
    [[$class, $method], $params] = $match;
    ksort($params);
    return substr($class, strrpos($class, '\\') + 1) . '::' . $method . ' ' . json_encode($params);
}

// ---- routing ----
check('home', route('/'), 'HomeController::index []');
check('list', route('/acts/central'), 'ActListController::index []');
check('list page', route('/acts/central/page/3'), 'ActListController::index {"page":"3"}');
check('year', route('/acts/central/2020'), 'ActListController::index {"year":"2020"}');
check('year page', route('/acts/central/2020/page/2'), 'ActListController::index {"page":"2","year":"2020"}');
check('act', route('/acts/central/companies-act-2013'), 'ActController::sections {"act":"companies-act-2013"}');
check('act page', route('/acts/central/companies-act-2013/page/4'), 'ActController::sections {"act":"companies-act-2013","page":"4"}');
check('details', route('/acts/central/companies-act-2013/details'), 'ActController::details {"act":"companies-act-2013"}');
check('schedules', route('/acts/central/companies-act-2013/schedules'), 'ActController::schedules {"act":"companies-act-2013"}');
check('tab', route('/acts/central/companies-act-2013/rules/page/2'), 'ActController::tab {"act":"companies-act-2013","page":"2","tab":"rules"}');
check('section', route('/acts/central/companies-act-2013/section-2a'), 'ActController::provision {"act":"companies-act-2013","provision":"section-2a"}');
check('schedule', route('/acts/central/companies-act-2013/first-schedule'), 'ActController::provision {"act":"companies-act-2013","provision":"first-schedule"}');
check('schedule 21', route('/acts/central/companies-act-2013/schedule-21'), 'ActController::provision {"act":"companies-act-2013","provision":"schedule-21"}');
check('search', route('/search/page/2'), 'SearchController::index {"page":"2"}');
check('file', route('/files/' . str_repeat('ab', 32) . '/A2026-9.pdf'), 'FileController::pdf {"name":"A2026-9.pdf","sha":"' . str_repeat('ab', 32) . '"}');
check('category', route('/rules'), 'CategoryController::index {"category":"rules"}');
check('category page', route('/notifications/page/2'), 'CategoryController::index {"category":"notifications","page":"2"}');
check('unknown category', route('/whatever'), null);
check('sitemap part', route('/sitemap-2.xml'), 'SeoController::sitemapPart {"part":"2"}');
check('file bad hash', route('/files/xyz/A.pdf'), null);
check('file traversal', route('/files/' . str_repeat('ab', 32) . '/../../config.php'), null);
check('unknown tab', route('/acts/central/companies-act-2013/whatever'), null);
check('bad slug', route('/acts/central/Companies_Act'), null);

// ---- canonical paths ----
check('trailing slash', App::canonicalPath('/acts/central/'), '/acts/central');
check('uppercase', App::canonicalPath('/Acts/Central'), '/acts/central');
check('double slash', App::canonicalPath('//acts//central'), '/acts/central');
check('index.php', App::canonicalPath('/index.php'), '/');
check('root', App::canonicalPath('/'), '/');
check('file case kept', App::canonicalPath('/files/ab/A2026-9.pdf'), '/files/ab/A2026-9.pdf');

// ---- URLs ----
check('paged 1', Url::paged('/acts/central', 1), '/acts/central');
check('paged 3', Url::paged('/acts/central', 3, ['search' => 'tax']), '/acts/central/page/3?search=tax');
check('paged drops empty', Url::paged('/acts/central', 2, ['search' => '']), '/acts/central/page/2');
check('category url', Url::category('rules'), '/rules');
check('file url', Url::file(['sha256' => 'ff', 'filename' => 'A 2026, 9.pdf']), '/files/ff/A-2026-9.pdf');
check('file url download', Url::file(['sha256' => 'ff', 'filename' => 'x'], true), '/files/ff/x.pdf?download=1');
// Sections and schedules have no page of their own (owner, 2026-11-03): they live in the Act's tabs.
check('section url', Url::provision('companies-act-2013', 'section-2a'), '/acts/central/companies-act-2013#section-2a');
check('section url page 2', Url::provision('companies-act-2013', 'section-25', 'SECTION', 2), '/acts/central/companies-act-2013/page/2#section-25');
check('schedule url', Url::provision('companies-act-2013', 'first-schedule', 'SCHEDULE'), '/acts/central/companies-act-2013/schedules#first-schedule');

// ---- pagination ----
$link = fn(int $n): string => Url::paged('/l', $n);
$p = Pager::make(5, 200, 20, $link);
check('pager window', array_map(fn($i) => $i['number'] ?? null, $p['pages']), [1, null, 3, 4, 5, 6, 7, null, 10]);
check('pager range', [$p['from'], $p['to'], $p['offset']], [81, 100, 80]);
check('pager prev/next', [$p['prev'], $p['next']], ['/l/page/4', '/l/page/6']);
check('one page has no links', Pager::make(1, 5, 20, $link)['pages'], []);
check('empty list is page 1', Pager::make(1, 0, 20, $link)['last'], 1);
throws('past the end', fn() => Pager::make(11, 200, 20, $link), HttpError::class, 404);
check('page segment', Pager::number('', $link), 1);
check('page segment 7', Pager::number('7', $link), 7);
throws('page/1 redirects', fn() => Pager::number('1', $link), Redirect::class);
throws('page/0', fn() => Pager::number('0', $link), HttpError::class, 404);
throws('page/01', fn() => Pager::number('01', $link), HttpError::class, 404);

// ---- search ----
$acts = [
    ['title' => 'The Finance Act, 2020', 'act_number' => '12', 'year' => '2020'],
    ['title' => 'The Companies Act, 2013', 'act_number' => '18', 'year' => '2013'],
    ['title' => 'The Taxation and Finance Laws Act, 2020', 'act_number' => '38', 'year' => '2020'],
];
check('words', Search::words('Finance, Act 2020!'), ['finance', 'act', '2020']);
check('hindi words', Search::words('विकसित भारत'), ['विकसित', 'भारत']);
check('search all words', array_column(Search::acts($acts, 'finance 2020'), 'act_number'), ['12', '38']);
check('phrase first', array_column(Search::acts($acts, 'finance laws'), 'act_number'), ['38']);
check('cited as number of year', array_column(Search::acts($acts, '18 of 2013'), 'act_number'), ['18']);
check('number and year', array_column(Search::acts($acts, '18 2013'), 'act_number'), ['18']);
check('empty query returns all', count(Search::acts($acts, '  ')), 3);
$rows = [['companies-act-2013', 'section-2', 'Section 2', 'Definitions.'], ['gone', 'section-1', 'Section 1', 'Definitions']];
$bySlug = ['companies-act-2013' => ['slug' => 'companies-act-2013', 'title' => 'The Companies Act, 2013']];
check('provision search', array_column(Search::provisions($rows, $bySlug, 'definitions'), 'slug'), ['section-2']);

// ---- content ----
$content = new Content(__DIR__ . '/fixtures/content');
check('ready', $content->ready(), true);
check('acts', array_column($content->acts(), 'slug'), ['kerala-alteration-of-name-act-2026']);
check('years', $content->years(), ['2026' => 1]);
check('categories', $content->categories(['rules', 'notifications']), []);
check('act', $content->act('kerala-alteration-of-name-act-2026')['act_number'] ?? null, '18');
check('missing act', $content->act('nope'), null);
check('bad slug never reads a file', $content->act('../index'), null);
check('pdf path', $content->pdfPath(str_repeat('0', 64)), null);
$kerala = 'kerala-alteration-of-name-act-2026';
check('act has no section text', array_key_exists('body_html', $content->act($kerala)['sections'][0]), false);
check('provision file', [$content->provision($kerala, 'SECTION', 'section-2')['heading'] ?? null, $content->provision($kerala, 'SECTION', 'section-2')['kind'] ?? null], ['Definitions', 'SECTION']);
check('missing provision', $content->provision($kerala, 'SCHEDULE', 'first-schedule'), null);
check('bad provision slug', $content->provision($kerala, 'SECTION', '../act'), null);
check('empty tab', $content->tab($kerala, 'rules'), []);
check('pdf path in act folder', str_ends_with((string) $content->act($kerala)['files'][0]['path'], '/pdf/A2026-18.pdf'), true);
// files.json paths must stay inside content/.
$tmp = sys_get_temp_dir() . '/lq-content-' . getmypid();
@mkdir("$tmp/central/acts/x/pdf", 0777, true);
file_put_contents("$tmp/central/acts/x/pdf/a.pdf", '%PDF');
file_put_contents("$tmp/files.json", json_encode([str_repeat('a', 64) => 'central/acts/x/pdf/a.pdf', str_repeat('b', 64) => '../config.php', str_repeat('c', 64) => 'central/../../x.pdf']));
file_put_contents("$tmp/manifest.json", '{"format":1}');
$tmpContent = new Content($tmp);
check('pdf via files.json', $tmpContent->pdfPath(str_repeat('a', 64)), "$tmp/central/acts/x/pdf/a.pdf");
check('pdf path traversal', [$tmpContent->pdfPath(str_repeat('b', 64)), $tmpContent->pdfPath(str_repeat('c', 64))], [null, null]);
check('old format not ready', $tmpContent->ready(), false);
array_map('unlink', ["$tmp/central/acts/x/pdf/a.pdf", "$tmp/files.json", "$tmp/manifest.json"]);

check('bad pdf hash', $content->pdfPath('../../x'), null);

// ---- seo ----
$abs = fn(string $p): string => 'https://example.org' . $p;
check('desc short', Seo::description('<p>An  Act <b>to</b>&nbsp;amend.</p>'), 'An Act to amend.');
$long = str_repeat('word ', 60);
$d = Seo::description($long);
check('desc cut at word', [mb_strlen($d) <= 160, str_ends_with($d, 'word…')], [true, true]);
check('desc hindi kept', Seo::description('धारा 2 परिभाषाएं'), 'धारा 2 परिभाषाएं');
$bc = Seo::breadcrumbs([['Home', '/'], ['Act', '/acts/central/x#section-2']], 'Section 2', $abs);
check('breadcrumb items', array_column($bc['itemListElement'], 'item'), ['https://example.org/', 'https://example.org/acts/central/x']);
check('breadcrumb last', end($bc['itemListElement']), ['@type' => 'ListItem', 'position' => 3, 'name' => 'Section 2']);
$fixture = $content->act('kerala-alteration-of-name-act-2026');
$law = Seo::act($fixture, $abs);
check('legislation', [$law['@type'], $law['url'], $law['legislationIdentifier'], $law['legislationDate']],
    ['Legislation', 'https://example.org/acts/central/kerala-alteration-of-name-act-2026', 'Act No. 18 of 2026', '2026-08-14']);
check('json-ld closes safely', str_contains(Seo::script(['name' => '</script><b>']), '</script><b>'), false);
check('website search', Seo::website('LQ', $abs)['potentialAction']['target']['urlTemplate'], 'https://example.org/search?q={search_term_string}');

// ---- helpers ----
check('date', format_date('2026-04-09'), '9 Apr 2026');
check('date passthrough', format_date('N/A'), 'N/A');
check('size', format_size(306176), '299 KB');
check('label section', provision_label(['number' => '2A']), 'Section 2A');
check('label schedule', provision_label(['kind' => 'SCHEDULE', 'slug' => 'first-schedule']), 'First Schedule');
check('heading', provision_heading(['number' => '2', 'heading' => 'Definitions.']), 'Section 2: Definitions');
check('heading repeat', provision_heading(['kind' => 'SCHEDULE', 'slug' => 'first-schedule', 'heading' => 'THE FIRST SCHEDULE']), 'First Schedule');
check('escape', e('<a href="x">'), '&lt;a href=&quot;x&quot;&gt;');
check('tidy text', tidy_text("7 \n \n\nTHE FIRST SCHEDULE \n\n\n \n1.   Act  \n \n "), "7\n\nTHE FIRST SCHEDULE\n\n1.   Act");

echo $failures ? "\n$failures of $count checks failed\n" : "$count checks passed\n";
exit($failures ? 1 : 0);
