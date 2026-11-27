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
use LegalQueryWeb\Jurisdiction;
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
check('list', route('/acts/central'), 'ActListController::index {"jurisdiction":"central"}');
check('list page', route('/acts/central/page/3'), 'ActListController::index {"jurisdiction":"central","page":"3"}');
check('year', route('/acts/central/2020'), 'ActListController::index {"jurisdiction":"central","year":"2020"}');
check('year page', route('/acts/central/2020/page/2'), 'ActListController::index {"jurisdiction":"central","page":"2","year":"2020"}');
check('act', route('/acts/central/companies-act-2013'), 'ActController::sections {"act":"companies-act-2013","jurisdiction":"central"}');
check('act page', route('/acts/central/companies-act-2013/page/4'), 'ActController::sections {"act":"companies-act-2013","jurisdiction":"central","page":"4"}');
check('details', route('/acts/central/companies-act-2013/details'), 'ActController::details {"act":"companies-act-2013","jurisdiction":"central"}');
check('schedules', route('/acts/central/companies-act-2013/schedules'), 'ActController::schedules {"act":"companies-act-2013","jurisdiction":"central"}');
check('tab', route('/acts/central/companies-act-2013/rules/page/2'), 'ActController::tab {"act":"companies-act-2013","jurisdiction":"central","page":"2","tab":"rules"}');
check('section', route('/acts/central/companies-act-2013/section-2a'), 'ActController::provision {"act":"companies-act-2013","jurisdiction":"central","provision":"section-2a"}');
check('schedule', route('/acts/central/companies-act-2013/first-schedule'), 'ActController::provision {"act":"companies-act-2013","jurisdiction":"central","provision":"first-schedule"}');
check('schedule 21', route('/acts/central/companies-act-2013/schedule-21'), 'ActController::provision {"act":"companies-act-2013","jurisdiction":"central","provision":"schedule-21"}');
check('search', route('/search/page/2'), 'SearchController::index {"page":"2"}');
check('file', route('/files/' . str_repeat('ab', 32) . '/A2026-9.pdf'), 'FileController::pdf {"name":"A2026-9.pdf","sha":"' . str_repeat('ab', 32) . '"}');
check('category', route('/rules'), 'CategoryController::index {"category":"rules"}');
check('states', route('/states'), 'StatesController::index []');
check('policy page', route('/privacy-policy'), 'PageController::show {"page":"privacy-policy"}');
check('policy pages have their text', array_filter(array_keys(LegalQueryWeb\Controller\PageController::PAGES), static fn(string $p): bool => !is_file(dirname(__DIR__) . "/pages/$p.html")), []);
check('category page', route('/notifications/page/2'), 'CategoryController::index {"category":"notifications","page":"2"}');
check('unknown category', route('/whatever'), null);
check('sitemap part', route('/sitemap-2.xml'), 'SeoController::sitemapPart {"part":"2"}');
check('file bad hash', route('/files/xyz/A.pdf'), null);
check('file traversal', route('/files/' . str_repeat('ab', 32) . '/../../config.php'), null);
check('unknown tab', route('/acts/central/companies-act-2013/whatever'), null);
check('bad slug', route('/acts/central/Companies_Act'), null);
check('unknown jurisdiction', route('/acts/kerala'), null);
check('unknown jurisdiction act', route('/acts/kerala/companies-act-2013'), null);

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
check('states url', Url::states(), '/states');
check('policy page url', Url::page('legal-disclaimer'), '/legal-disclaimer');
check('no refund policy page', route('/refund-policy'), null);
check('robots.txt keeps crawlers off PDFs', (static function (): array {
    $code = (string) file_get_contents(dirname(__DIR__) . '/src/Controller/SeoController.php');
    return array_map(static fn(string $rule): bool => str_contains($code, $rule), ['Disallow: /pdf\\n', 'Disallow: /files/\\n', 'Disallow: /*.pdf$']);
})(), [true, true, true]);
check('file url', Url::file(['sha256' => 'ff', 'filename' => 'A 2026, 9.pdf']), '/files/ff/A-2026-9.pdf');
check('file url download', Url::file(['sha256' => 'ff', 'filename' => 'x'], true), '/files/ff/x.pdf?download=1');
// Sections and schedules have no page of their own (owner, 2026-11-03): they live in the Act's tabs.
check('act list url', [Url::actList('central'), Url::actList('central', '2020')], ['/acts/central', '/acts/central/2020']);
check('act url', Url::act('central', 'x', 'rules'), '/acts/central/x/rules');
check('jurisdictions', [Jurisdiction::codes(), Jurisdiction::name('central'), Jurisdiction::exists('../x')], [['central'], 'Central Acts', false]);
Jurisdiction::register(['central' => 'CENTRAL', 'kerala' => 'Kerala', 'andaman-and-nicobar-islands' => 'Andaman and Nicobar Islands', '../x' => 'Bad']);
check('registered jurisdictions', [Jurisdiction::codes(), Jurisdiction::name('central'), Jurisdiction::name('kerala'), Jurisdiction::detail('andaman-and-nicobar-islands'), Jurisdiction::exists('../x')],
    [['central', 'kerala', 'andaman-and-nicobar-islands'], 'Central Acts', 'Kerala Acts', 'Andaman and Nicobar Islands', false]);
check('state act route', Router::match('/acts/kerala/some-act-1990')[1]['jurisdiction'] ?? null, 'kerala');
check('state list route', Router::match('/acts/andaman-and-nicobar-islands/page/2')[1]['jurisdiction'] ?? null, 'andaman-and-nicobar-islands');
check('union territories', array_map([Jurisdiction::class, 'isUnionTerritory'], ['delhi', 'ladakh', 'puducherry', 'kerala', 'goa', 'central']), [true, true, true, false, false, false]);
Jurisdiction::register([]);
check('unregistered state has no route', Router::match('/acts/kerala/some-act-1990'), null);
check('section url', Url::provision('central', 'companies-act-2013', 'section-2a'), '/acts/central/companies-act-2013#section-2a');
check('section url page 2', Url::provision('central', 'companies-act-2013', 'section-25', 'SECTION', 2), '/acts/central/companies-act-2013/page/2#section-25');
check('schedule url', Url::provision('central', 'companies-act-2013', 'first-schedule', 'SCHEDULE'), '/acts/central/companies-act-2013/schedules#first-schedule');

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
check('acts', array_column($content->acts('central'), 'slug'), ['kerala-alteration-of-name-act-2026']);
check('years', $content->years('central'), ['2026' => 1]);
check('categories', $content->categories('central', ['rules', 'notifications']), []);
check('act', $content->act('central', 'kerala-alteration-of-name-act-2026')['act_number'] ?? null, '18');
check('missing act', $content->act('central', 'nope'), null);
check('bad slug never reads a file', $content->act('central', '../index'), null);
check('pdf path', $content->pdfPath(str_repeat('0', 64)), null);
$kerala = 'kerala-alteration-of-name-act-2026';
check('act has no section text', array_key_exists('body_html', $content->act('central', $kerala)['sections'][0]), false);
check('provision file', [$content->provision('central', $kerala, 'SECTION', 'section-2')['heading'] ?? null, $content->provision('central', $kerala, 'SECTION', 'section-2')['kind'] ?? null], ['Definitions', 'SECTION']);
check('missing provision', $content->provision('central', $kerala, 'SCHEDULE', 'first-schedule'), null);
check('bad provision slug', $content->provision('central', $kerala, 'SECTION', '../act'), null);
check('empty tab', $content->tab('central', $kerala, 'rules'), []);
check('act row knows its jurisdiction', [$content->acts('central')[0]['jurisdiction'], $content->act('central', $kerala)['jurisdiction']], ['central', 'central']);
check('unknown jurisdiction reads nothing', [$content->acts('../central'), $content->act('state', $kerala), $content->searchRows('x'), $content->years('x')], [[], null, [], []]);
check('pdf path in act folder', str_ends_with((string) $content->act('central', $kerala)['files'][0]['path'], '/pdf/A2026-18.pdf'), true);
// The file index (files/<ab>.json) paths must stay inside content/.
$tmp = sys_get_temp_dir() . '/lq-content-' . getmypid();
@mkdir("$tmp/central/acts/x/pdf", 0777, true);
@mkdir("$tmp/files", 0777, true);
file_put_contents("$tmp/central/acts/x/pdf/a.pdf", '%PDF');
file_put_contents("$tmp/files/aa.json", json_encode([str_repeat('a', 64) => 'central/acts/x/pdf/a.pdf']));
file_put_contents("$tmp/files/bb.json", json_encode([str_repeat('b', 64) => '../config.php']));
file_put_contents("$tmp/files/cc.json", json_encode([str_repeat('c', 64) => 'central/../../x.pdf', str_repeat('a', 64) => 'central/acts/x/pdf/a.pdf']));
file_put_contents("$tmp/files/ee.json", json_encode([str_repeat('e', 64) => 'central/acts/x/pdf/a_dt_18..11.2019.pdf', 'e' . str_repeat('f', 63) => './central/acts/x/pdf/a_dt_18..11.2019.pdf']));
file_put_contents("$tmp/central/acts/x/pdf/a_dt_18..11.2019.pdf", '%PDF');
file_put_contents("$tmp/files.json", json_encode([str_repeat('d', 64) => 'central/acts/x/pdf/a.pdf']));
file_put_contents("$tmp/manifest.json", '{"format":2}');
$tmpContent = new Content($tmp);
check('pdf via files/<ab>.json', $tmpContent->pdfPath(str_repeat('a', 64)), "$tmp/central/acts/x/pdf/a.pdf");
check('pdf path traversal', [$tmpContent->pdfPath(str_repeat('b', 64)), $tmpContent->pdfPath(str_repeat('c', 64))], [null, null]);
check('pdf name with ".." inside is fine, a "." folder is not', [$tmpContent->pdfPath(str_repeat('e', 64)), $tmpContent->pdfPath('e' . str_repeat('f', 63))], ["$tmp/central/acts/x/pdf/a_dt_18..11.2019.pdf", null]);
check('format 2 files.json is not read', $tmpContent->pdfPath(str_repeat('d', 64)), null);
check('old format not ready', $tmpContent->ready(), false);
// Linked documents' `f` (owner, 2026-11-24): lowercased name; a different file with the same name gets its folder.
file_put_contents("$tmp/central/acts/x/rules.json", json_encode(['items' => [
    ['title' => 'A', 'files' => [['path' => 'central/acts/x/pdf/rules/Ab_C.pdf', 'sha256' => str_repeat('1', 64)]]],
    ['title' => 'B', 'files' => [['path' => 'central/acts/x/pdf/notifications/ab_c.pdf', 'sha256' => str_repeat('2', 64)], ['filename' => 'no-path.pdf']]],
    ['title' => 'C', 'files' => [['path' => 'central/acts/x/pdf/rules/Ab_C.pdf', 'sha256' => str_repeat('1', 64)], ['path' => 'central/acts/x/pdf/ab_c.pdf', 'sha256' => str_repeat('3', 64)]]],
]]));
$fs = array_merge(...array_map(static fn(array $i): array => array_map(static fn(array $f): ?string => $f['f'] ?? null, $i['files']), $tmpContent->tab('central', 'x', 'rules')));
check('linked pdf names', $fs, ['ab_c', 'notifications-ab_c', null, 'ab_c', 'pdf-ab_c']);
check('linked pdf href', $tmpContent->tab('central', 'x', 'rules')[1]['files'][0]['href'] ?? null, '/pdf?j=central&a=x&d=rules&f=notifications-ab_c');
check('linked pdf by f', [$tmpContent->linkedFile('central', 'x', 'rules', 'pdf-ab_c')['sha256'] ?? null, $tmpContent->linkedFile('central', 'x', 'rules', 'nope')], [str_repeat('3', 64), null]);
unlink("$tmp/central/acts/x/rules.json");
array_map('unlink', ["$tmp/central/acts/x/pdf/a.pdf", "$tmp/central/acts/x/pdf/a_dt_18..11.2019.pdf", "$tmp/files/ee.json", "$tmp/files/aa.json", "$tmp/files/bb.json", "$tmp/files/cc.json", "$tmp/files.json", "$tmp/manifest.json"]);
check('jurisdictions from manifest', $content->jurisdictions(), ['central' => 'CENTRAL']);
check('counts per jurisdiction', [$content->counts('central')['acts'] ?? null, $content->counts('kerala')], [1, []]);

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
$fixture = $content->act('central', 'kerala-alteration-of-name-act-2026');
$law = Seo::act($fixture, $abs);
check('legislation', [$law['@type'], $law['url'], $law['legislationIdentifier'], $law['legislationDate']],
    ['Legislation', 'https://example.org/acts/central/kerala-alteration-of-name-act-2026', 'Act No. 18 of 2026', '2026-08-14']);
check('json-ld closes safely', str_contains(Seo::script(['name' => '</script><b>']), '</script><b>'), false);
check('website search', Seo::website('LQ', $abs)['potentialAction']['target']['urlTemplate'], 'https://example.org/search?q={search_term_string}');

// ---- helpers ----
check('date', format_date('2026-04-09'), '9 Apr 2026');
check('date passthrough', format_date('N/A'), 'N/A');
check('date year only (amendments)', format_date('2025'), '2025');
check('size', format_size(306176), '299 KB');
check('label section', provision_label(['number' => '2A']), 'Section 2A');
check('label schedule', provision_label(['kind' => 'SCHEDULE', 'slug' => 'first-schedule']), 'First Schedule');
check('heading', provision_heading(['number' => '2', 'heading' => 'Definitions.']), 'Section 2: Definitions');
check('heading repeat', provision_heading(['kind' => 'SCHEDULE', 'slug' => 'first-schedule', 'heading' => 'THE FIRST SCHEDULE']), 'First Schedule');
check('escape', e('<a href="x">'), '&lt;a href=&quot;x&quot;&gt;');
$pdf = fn(string $lang, bool $archived = true): array => ['filename' => "$lang.pdf", 'language' => $lang] + ($archived ? ['sha256' => 'x'] : []);
$pdf = fn(string $lang, bool $archived = true, string $name = '', string $sha = ''): array => ['filename' => ($name ?: "$lang.pdf"), 'language' => $lang] + ($archived ? ['sha256' => ($sha ?: "sha-$lang-$name")] : []);
$labels = fn(array $files): array => array_column(pdf_choices($files), 'label');
check('pdf choices order', $labels([$pdf('unknown'), $pdf('hi'), $pdf('bi'), $pdf('en')]), ['English', 'English + हिन्दी', 'हिन्दी', 'PDF']);
check('pdf choices skip not archived', $labels([$pdf('en', false), $pdf('hi')]), ['हिन्दी']);
check('pdf choices none', pdf_choices([$pdf('en', false)]), []);
check('pdf choices same file once', $labels([$pdf('en', true, 'a.pdf', 's1'), $pdf('en', true, 'b.pdf', 's1')]), ['English']);
check('pdf choices H name is Hindi', $labels([$pdf('en', true, 'H2018-17.pdf'), $pdf('en', true, 'A2018-17.pdf')]), ['English', 'हिन्दी']);
check('pdf choices numbered', $labels([$pdf('en', true, '197228.pdf'), $pdf('en', true, 'a1972-28.pdf'), $pdf('hi', true, 'hH1972-28.pdf')]), ['English', 'English 2', 'हिन्दी']);
check('pdf choices lang', array_column(pdf_choices([$pdf('en', true, 'h201949.pdf')]), 'lang'), ['hi']);
check('pdf choices state languages', $labels([$pdf('unknown'), $pdf('ml'), $pdf('gu'), $pdf('en'), $pdf('xx')]), ['English', 'ગુજરાતી', 'മലയാളം', 'PDF', 'PDF 2']);
// PDF question (PdfGate): signed token, in time, one use only.
$usedDir = sys_get_temp_dir() . '/lqw-test-used-' . bin2hex(random_bytes(4));
$gate = new \LegalQueryWeb\PdfGate(str_repeat('k', 32), $usedDir);
$q = $gate->question(1000);
$right = (string) ($q['a'] + $q['b']);
check('pdf gate wrong answer', $gate->check($q['token'], (string) ($q['a'] + $q['b'] + 1), 1000), false);
check('pdf gate expired', $gate->check($q['token'], $right, 1000 + \LegalQueryWeb\PdfGate::FORM_TTL + 1), false);
check('pdf gate bad input', [$gate->check($q['token'], 'abc', 1000), $gate->check('x', $right, 1000)], [false, false]);
check('pdf gate other secret', (new \LegalQueryWeb\PdfGate(str_repeat('z', 32), $usedDir))->check($q['token'], $right, 1000), false);
check('pdf gate right answer', $gate->check($q['token'], " $right ", 1000), true);
check('pdf gate token used once', $gate->check($q['token'], $right, 1000), false);
// Pass cookie after a right answer: same PDF path only, PASS_TTL seconds (viewer download, 2026-11-21).
$pass = $gate->pass('/acts/central/x-act-2026.pdf', 1000);
check('pdf pass same path', $gate->passes($pass, '/acts/central/x-act-2026.pdf', 1000 + \LegalQueryWeb\PdfGate::PASS_TTL), true);
check('pdf pass other path', $gate->passes($pass, '/acts/central/y-act-2026.pdf', 1000), false);
check('pdf pass expired', $gate->passes($pass, '/acts/central/x-act-2026.pdf', 1001 + \LegalQueryWeb\PdfGate::PASS_TTL), false);
check('pdf pass forged', [$gate->passes('9999999999.' . str_repeat('0', 64), '/acts/central/x-act-2026.pdf', 1000), $gate->passes('', '/a.pdf', 1000),
    (new \LegalQueryWeb\PdfGate(str_repeat('z', 32), $usedDir))->passes($pass, '/acts/central/x-act-2026.pdf', 1000)], [false, false, false]);
array_map('unlink', glob("$usedDir/*") ?: []);
rmdir($usedDir);
check('short text', [short_text('A2026-18.pdf'), short_text('a1972-28_hindi.pdf'), short_text('हिन्दी-अधिनियम-२०२६.pdf')], ['A2026-18.pdf', 'a1972-28_hin…', 'हिन्दी-अधिनियम-२०…']);
check('pdf question route', route('/pdf-question'), 'FileController::question []');
$pageOf = [\LegalQueryWeb\Controller\FileController::class, 'pageOf'];
check('direct pdf hit goes to its page', [
    $pageOf('/srv/content/gujarat/acts/x-act-2019/pdf/a2019-1.pdf'),
    $pageOf('/srv/content/central/acts/customs-act-1962/pdf/notifications/15-2001.pdf'),
    $pageOf('/srv/content/central/acts/x-act/pdf/unknowntab/a.pdf'),
    $pageOf('/srv/content/elsewhere/a.pdf'),
], ['/acts/gujarat/x-act-2019', '/acts/central/customs-act-1962/notifications', '/acts/central/x-act', null]);

Jurisdiction::register(['puducherry' => 'Puducherry']);
check('jurisdiction short label', [Jurisdiction::short('central'), Jurisdiction::short('puducherry')], ['Central', 'Puducherry']);
Jurisdiction::register([]);

// ---- readable Act PDF URLs (owner, 2026-11-19) ----
check('act pdf route', route('/acts/central/companies-act-2013.pdf'), 'FileController::actPdf {"jurisdiction":"central","name":"companies-act-2013"}');
check('act pdf route hindi', route('/acts/central/companies-act-2013-hindi.pdf'), 'FileController::actPdf {"jurisdiction":"central","name":"companies-act-2013-hindi"}');
check('act pdf route bad name', [route('/acts/central/Companies.pdf'), route('/acts/central/x/y.pdf'), route('/acts/kerala/x.pdf')], [null, null, null]);
check('act pdf canonical path', App::canonicalPath('/Acts/Central/X-Act.PDF'), '/acts/central/x-act.pdf');
// ---- /pdf URLs (owner, 2026-11-24); the old .pdf names above live on as 301s ----
check('pdf route', [route('/pdf'), route('/pdf/x'), route('/pdfs')], ['FileController::page []', null, null]);
check('act pdf url', [Url::actPdf('central', 'x-act-2020'), Url::actPdf('central', 'x-act-2020', 'hi', true), Url::actPdf('gujarat', 'x', 'en-2')],
    ['/pdf?j=central&a=x-act-2020', '/pdf?j=central&a=x-act-2020&v=hi&download=1', '/pdf?j=gujarat&a=x&v=en-2']);
check('linked pdf url', Url::linkedPdf('central', 'customs-act-1962', 'notifications', '15-2001', true), '/pdf?j=central&a=customs-act-1962&d=notifications&f=15-2001&download=1');
check('pdf url prefers href', [Url::pdf(['href' => '/pdf?j=central&a=x'], true), Url::pdf(['href' => '/acts/central/x.pdf'], true), Url::pdf(['sha256' => 'ff', 'filename' => 'a.pdf'])],
    ['/pdf?j=central&a=x&download=1', '/acts/central/x.pdf?download=1', '/files/ff/a.pdf']);
$hrefs = fn(array $files): array => array_column(\LegalQueryWeb\ActPdf::choices(['jurisdiction' => 'central', 'slug' => 'x-act-1972', 'files' => $files]), 'href');
check('act pdf hrefs', $hrefs([$pdf('hi', true, 'hH1972-28.pdf'), $pdf('en', true, '197228.pdf'), $pdf('en', true, 'a1972-28.pdf')]),
    ['/pdf?j=central&a=x-act-1972', '/pdf?j=central&a=x-act-1972&v=en-2', '/pdf?j=central&a=x-act-1972&v=hi']);
check('act pdf hrefs bilingual unknown state', $hrefs([$pdf('bi'), $pdf('unknown'), $pdf('hi'), $pdf('hi', true, 'h2.pdf'), $pdf('gu', true, 'g.pdf')]),
    ['/pdf?j=central&a=x-act-1972&v=bi', '/pdf?j=central&a=x-act-1972&v=hi', '/pdf?j=central&a=x-act-1972&v=hi-2', '/pdf?j=central&a=x-act-1972&v=gu', '/pdf?j=central&a=x-act-1972']);
check('act pdf by language', [\LegalQueryWeb\ActPdf::byLanguage(['jurisdiction' => 'central', 'slug' => 'x', 'files' => [$pdf('hi')]], 'hi')['href'] ?? null,
    \LegalQueryWeb\ActPdf::byLanguage(['jurisdiction' => 'central', 'slug' => 'x', 'files' => [$pdf('hi')]], '')], ['/pdf?j=central&a=x&v=hi', null]);
// Old .pdf names (`name`): still the downloaded file's name and the 301 source.
$names = fn(array $files): array => array_map(static fn(array $c): string => '/acts/central/' . $c['name'] . '.pdf', \LegalQueryWeb\ActPdf::choices(['jurisdiction' => 'central', 'slug' => 'x-act-1972', 'files' => $files]));
check('act pdf names', $names([$pdf('hi', true, 'hH1972-28.pdf'), $pdf('en', true, '197228.pdf'), $pdf('en', true, 'a1972-28.pdf')]), ['/acts/central/x-act-1972.pdf', '/acts/central/x-act-1972-english-2.pdf', '/acts/central/x-act-1972-hindi.pdf']);
check('act pdf names bilingual and unknown', $names([$pdf('bi'), $pdf('unknown'), $pdf('hi'), $pdf('hi', true, 'h2.pdf')]), ['/acts/central/x-act-1972-english-hindi.pdf', '/acts/central/x-act-1972-hindi.pdf', '/acts/central/x-act-1972-hindi-2.pdf', '/acts/central/x-act-1972.pdf']);
check('act pdf names only hindi', $names([$pdf('hi')]), ['/acts/central/x-act-1972-hindi.pdf']);
check('act pdf names not archived', $names([$pdf('en', false)]), []);
check('act pdf names state language', $names([$pdf('gu', true, 'act_no._26_of_2019.pdf'), $pdf('en', true, 'msmeact19.pdf')]), ['/acts/central/x-act-1972.pdf', '/acts/central/x-act-1972-gujarati.pdf']);
$gujAct = ['jurisdiction' => 'gujarat', 'slug' => 'x-act-2019', 'files' => [$pdf('en', true, 'a.pdf'), $pdf('hi'), $pdf('gu', true, 'g.pdf')]];
check('act pdf moved english-2', [\LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019-english-2')['name'] ?? null, \LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019-english-2')['href'] ?? null], ['x-act-2019-gujarati', '/pdf?j=gujarat&a=x-act-2019&v=gu']);
check('act pdf moved not old', [\LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019'), \LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019-hindi'), \LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019-english-3')], [null, null, null]);
check('act pdf moved alone', \LegalQueryWeb\ActPdf::moved(['jurisdiction' => 'kerala', 'slug' => 'y-act', 'files' => [$pdf('ml')]], 'y-act')['href'] ?? null, '/pdf?j=kerala&a=y-act&v=ml');
check('act pdf candidates', \LegalQueryWeb\ActPdf::candidates('hindi-act-1962-english-hindi-2'), ['hindi-act-1962-english-hindi-2', 'hindi-act-1962', 'hindi-act-1962-english']);
check('act pdf candidates main', \LegalQueryWeb\ActPdf::candidates('hindi-sahitya-sammelan-act-1962'), ['hindi-sahitya-sammelan-act-1962']);
check('act pdf candidates hindi', \LegalQueryWeb\ActPdf::candidates('x-act-hindi'), ['x-act-hindi', 'x-act']);
check('act pdf candidates state language', [\LegalQueryWeb\ActPdf::candidates('x-act-gujarati'), \LegalQueryWeb\ActPdf::candidates('x-act-telugu-2')], [['x-act-gujarati', 'x-act'], ['x-act-telugu-2', 'x-act']]);
$keralaAct = $content->act('central', $kerala);
check('fixture act pdf', \LegalQueryWeb\ActPdf::find($keralaAct, $kerala)['href'] ?? null, "/pdf?j=central&a=$kerala");
check('fixture act pdf missing', \LegalQueryWeb\ActPdf::find($keralaAct, "$kerala-hindi"), null);

// ---- AI translations as extra Act PDFs (demo, owner 2026-11-21) ----
check('act pdf candidates ai', [\LegalQueryWeb\ActPdf::candidates('x-act-hindi-ai'), \LegalQueryWeb\ActPdf::candidates('x-act-bengali-ai')], [['x-act-hindi-ai', 'x-act'], ['x-act-bengali-ai', 'x-act']]);
check('fixture no ai files when off', array_key_exists('ai_files', $keralaAct), false);
$aiDir = sys_get_temp_dir() . '/lqw-ai-' . bin2hex(random_bytes(4));
$src = (string) ($keralaAct['files'][0]['sha256'] ?? '');
foreach (['hi', 'bn', 'ta'] as $l) {
    mkdir("$aiDir/$src/$l/v1", 0777, true);
    file_put_contents("$aiDir/$src/$l/v1/document.pdf", "%PDF-1.4 $l");
}
$aiAct = (new Content(__DIR__ . '/fixtures/content', $aiDir))->act('central', $kerala);
$aiChoices = array_values(array_filter(\LegalQueryWeb\ActPdf::choices($aiAct), static fn(array $f): bool => !empty($f['ai'])));
check('ai choices', array_map(static fn(array $f): array => [$f['lang'], $f['href'], $f['label']], $aiChoices), [
    ['hi', "/pdf?j=central&a=$kerala&v=hi-ai", 'हिन्दी (AI अनुवाद)'],
    ['bn', "/pdf?j=central&a=$kerala&v=bn-ai", 'বাংলা (AI অনুবাদ)'],
    ['ta', "/pdf?j=central&a=$kerala&v=ta-ai", 'தமிழ் (AI மொழிபெயர்ப்பு)'],
]);
$withHindi = $aiAct;
$withHindi['files'][] = ['filename' => 'H2026-18.pdf', 'language' => 'hi', 'size' => 1, 'sha256' => str_repeat('a', 64), 'path' => 'x.pdf'];
check('no ai hindi when official hindi', array_column(array_filter(\LegalQueryWeb\ActPdf::choices($withHindi), static fn(array $f): bool => !empty($f['ai'])), 'lang'), ['bn', 'ta']);
$withBilingual = $aiAct;
$withBilingual['files'][] = ['filename' => 'b.pdf', 'language' => 'bi', 'size' => 1, 'sha256' => str_repeat('b', 64), 'path' => 'b.pdf'];
check('no ai hindi when english + hindi pdf', array_column(array_filter(\LegalQueryWeb\ActPdf::choices($withBilingual), static fn(array $f): bool => !empty($f['ai'])), 'lang'), ['bn', 'ta']);
check('ai hindi url 404 when official hindi', \LegalQueryWeb\ActPdf::find($withHindi, "$kerala-hindi-ai"), null);
check('ai choice by language', \LegalQueryWeb\ActPdf::byLanguage($aiAct, 'bn-ai')['name'] ?? null, "$kerala-bengali-ai");
check('ai choice found by name', \LegalQueryWeb\ActPdf::find($aiAct, "$kerala-bengali-ai")['path'] ?? null, "$aiDir/$src/bn/v1/document.pdf");
check('ai choices not in json-ld', count(Seo::act($aiAct, $abs)['encoding'] ?? []), count(Seo::act($keralaAct, $abs)['encoding'] ?? []));
$onlyOther = (new Content(__DIR__ . '/fixtures/content', $aiDir, [str_repeat('0', 64)]))->act('central', $kerala);
check('ai only list', $onlyOther['ai_files'], []);
// Newest version wins; a version folder without document.pdf and an unknown language are ignored.
foreach (['mr/v1', 'mr/v2', 'ur/v1'] as $d) {
    mkdir("$aiDir/$src/$d", 0777, true);
    file_put_contents("$aiDir/$src/$d/document.pdf", "%PDF-1.4 $d");
}
mkdir("$aiDir/$src/mr/v3", 0777, true);
$mrAct = (new Content(__DIR__ . '/fixtures/content', $aiDir))->act('central', $kerala);
$mr = \LegalQueryWeb\ActPdf::byLanguage($mrAct, 'mr-ai');
check('ai newest version', $mr['path'] ?? null, "$aiDir/$src/mr/v2/document.pdf");
check('ai marathi label and name', [$mr['label'] ?? null, $mr['name'] ?? null, $mr['href'] ?? null], ['मराठी (AI भाषांतर)', "$kerala-marathi-ai", "/pdf?j=central&a=$kerala&v=mr-ai"]);
check('ai unknown language ignored', \LegalQueryWeb\ActPdf::byLanguage($mrAct, 'ur-ai'), null);
$guNotice = \LegalQueryWeb\Controller\FileController::aiNotice('gu');
check('ai notice names the language', str_starts_with($guNotice, 'Please note: you are about to open or download the Gujarati translation of this Act.'), true);
check('ai notice: not official, official PDF rules', [str_contains($guNotice, 'not official'), str_contains($guNotice, 'Only the official PDF')], [true, true]);
check('ai notice odia', str_contains(\LegalQueryWeb\Controller\FileController::aiNotice('or'), 'the Odia translation'), true);
array_map('unlink', glob("$aiDir/$src/*/v*/document.*") ?: []);
array_map('rmdir', glob("$aiDir/$src/*/v*") ?: []);
array_map('rmdir', glob("$aiDir/$src/*") ?: []);
@rmdir("$aiDir/$src");
@rmdir($aiDir);
// PdfTitle: an incremental update gives the served PDF the site's title (owner, 2026-11-26).
$pdfDir = sys_get_temp_dir() . '/lqw-pdf-' . bin2hex(random_bytes(4));
mkdir($pdfDir);
$makePdf = static function (string $trailerExtra, bool $xrefStream) use ($pdfDir): string {
    $objs = ["<< /Type /Catalog /Pages 2 0 R /Metadata 4 0 R >>", "<< /Type /Pages /Kids [] /Count 0 >>", "<< /Title (Hkkjr ds jkti=) >>", "<< /Length 0 >>\nstream\n\nendstream"];
    $pdf = "%PDF-1.5\n";
    $at = [];
    foreach ($objs as $i => $o) {
        $at[$i + 1] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n$o\nendobj\n";
    }
    $xref = strlen($pdf);
    if ($xrefStream) {
        $pdf .= "5 0 obj\n<< /Type /XRef /Size 6 /W [1 4 2] /Root 1 0 R /Info 3 0 R$trailerExtra /Length 0 >>\nstream\n\nendstream\nendobj\n";
    } else {
        $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        foreach ($at as $o) {
            $pdf .= sprintf("%010d 00000 n \n", $o);
        }
        $pdf .= "trailer\n<< /Size 5 /Root 1 0 R /Info 3 0 R$trailerExtra >>\n";
    }
    $file = "$pdfDir/" . bin2hex(random_bytes(4)) . '.pdf';
    file_put_contents($file, $pdf . "startxref\n$xref\n%%EOF\n");
    return $file;
};
$plain = $makePdf('', false);
$suffix = \LegalQueryWeb\PdfTitle::suffix($plain, 'नियम  2017');
$whole = file_get_contents($plain) . $suffix;
preg_match_all('/startxref\s+(\d+)/', $whole, $sx);
$hexTitle = 'FEFF' . strtoupper(bin2hex(mb_convert_encoding('नियम 2017', 'UTF-16BE', 'UTF-8')));
check('pdf title: info object', str_contains($suffix, "5 0 obj\n<< /Title <$hexTitle> >>"), true);
check('pdf title: trailer', (bool) preg_match('#/Size 6 /Root 1 0 R /Info 5 0 R /Prev ' . $sx[1][0] . ' >>#', $suffix), true);
check('pdf title: startxref points at the new table', substr($whole, (int) end($sx[1]), 4), 'xref');
check('pdf title: catalog without xmp', [str_contains($suffix, "1 0 obj\n<< /Type /Catalog /Pages 2 0 R  >>"), str_contains($suffix, '/Metadata')], [true, false]);
preg_match('#xref\n1 1\n(\d{10}) 00000 n \n5 1\n(\d{10}) 00000 n #', $suffix, $offs);
check('pdf title: offsets', [substr($whole, (int) ($offs[1] ?? 0), 7), substr($whole, (int) ($offs[2] ?? 0), 7)], ['1 0 obj', '5 0 obj']);
$xs = $makePdf('', true);
$xsSuffix = \LegalQueryWeb\PdfTitle::suffix($xs, 'Rules');
check('pdf title: after an xref stream, an xref stream', (bool) preg_match('#7 0 obj\n<< /Type /XRef /Size 8 /W \[1 4 2\] /Index \[1 1 6 1 7 1\] /Root 1 0 R /Info 6 0 R /Prev \d+ /Length 21 >>#', $xsSuffix), true);
check('pdf title: encrypted, empty title or not a pdf → served as is', [
    \LegalQueryWeb\PdfTitle::suffix($makePdf(' /Encrypt 9 0 R', false), 'x'),
    \LegalQueryWeb\PdfTitle::suffix($plain, '  '),
    \LegalQueryWeb\PdfTitle::suffix(__FILE__, 'x'),
], ['', '', '']);
array_map('unlink', glob("$pdfDir/*.pdf") ?: []);
rmdir($pdfDir);
// ---- download pages, end to end (owner, 2026-11-26) ----
// A small content/ with real (tiny) PDFs: an Act PDF, a notification in the Notifications tab (English +
// Hindi), documents linked only from sections (/pdf?…&s={section}&f={file}) and one linked from nowhere,
// served at /files/. Checks each download page's title, rows, language (header menu), back link and "Other
// PDFs" links, the links on the Act page, the old /files/ URLs' redirects, and the served PDF's Title.
$dl = sys_get_temp_dir() . '/lqw-dl-' . bin2hex(random_bytes(4));
$tinyPdf = static function (string $title): string {
    $objs = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [] /Count 0 >>', "<< /Title ($title) >>"];
    $pdf = "%PDF-1.4\n";
    $at = [];
    foreach ($objs as $i => $o) {
        $at[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n$o\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 4\n0000000000 65535 f \n" . implode('', array_map(static fn(int $o): string => sprintf("%010d 00000 n \n", $o), $at));
    return $pdf . "trailer\n<< /Size 4 /Root 1 0 R /Info 3 0 R >>\nstartxref\n$xref\n%%EOF\n";
};
$a = 'test-act-2020';
$base = "central/acts/$a";
$pdfs = [  // key => [path in content/, Title metadata]; distinct titles, so distinct checksums
    'act' => ["$base/pdf/A2020-1.pdf", 'Microsoft Word - A2020-1'],
    'tab-en' => ["$base/pdf/notifications/n1_english.pdf", 'Untitled'],
    'tab-hi' => ["$base/pdf/notifications/n1_hindi.pdf", 'Hkkjr ds jkti='],
    'sec-bi' => ["$base/pdf/notifications/guidelines_2020.pdf", 'Hkkjr ds jkti= ds vlk/kkj'],
    'sec-en' => ["$base/pdf/notifications/guidelines_2020_english.pdf", 'C:\\\\Users\\\\x'],
    'rule' => ["$base/pdf/rules/Rules_notified.pdf", '117gi.p65'],
    'sec-dup' => ["$base/pdf/rules/guidelines_2020.pdf", 'Same name, other folder'],
    'orphan' => ["$base/pdf/orders/some_order-2019.pdf", 'Untitled order'],
];
$sha = [];
$index = [];
foreach ($pdfs as $key => [$rel, $meta]) {
    @mkdir(dirname("$dl/$rel"), 0777, true);
    file_put_contents("$dl/$rel", $tinyPdf($meta));
    $sha[$key] = hash_file('sha256', "$dl/$rel");
    $index[substr($sha[$key], 0, 2)][$sha[$key]] = $rel;
}
@mkdir("$dl/files", 0777, true);
foreach ($index as $ab => $map) {
    file_put_contents("$dl/files/$ab.json", json_encode($map));
}
$f = static fn(string $key, string $lang) => ['filename' => basename($pdfs[$key][0]), 'language' => $lang, 'size' => 100, 'sha256' => $sha[$key], 'path' => $pdfs[$key][0]];
file_put_contents("$dl/manifest.json", json_encode(['format' => 3, 'jurisdictions' => [['code' => 'central', 'name' => 'CENTRAL']]]));
file_put_contents("$dl/central/index.json", json_encode(['format' => 3, 'acts' => [['slug' => $a, 'jurisdiction' => 'central', 'title' => 'The Test Act, 2020', 'year' => '2020', 'tabs' => ['notifications']]]]));
$section = static fn(string $slug, string $n, int $docs) => ['slug' => $slug, 'number' => $n, 'heading' => "Heading $n.", 'docs' => $docs];
file_put_contents("$dl/$base/act.json", json_encode([
    'format' => 3, 'slug' => $a, 'title' => 'The Test Act, 2020', 'year' => '2020', 'act_number' => '1',
    'files' => [$f('act', 'en')],
    'sections' => [$section('section-1', '1', 0), $section('section-2', '2', 1), $section('section-3', '3', 2)],
    'schedules' => [], 'tabs' => ['notifications' => 1],
]));
file_put_contents("$dl/$base/notifications.json", json_encode(['items' => [
    ['title' => 'S.O. 1 (E): Tab notification', 'date' => '2020-01-05', 'files' => [$f('tab-en', 'en'), $f('tab-hi', 'hi')]],
]]));
@mkdir("$dl/$base/sections", 0777, true);
$provision = static fn(string $slug, string $n, array $linked) => ['slug' => $slug, 'number' => $n, 'heading' => "Heading $n.", 'body_html' => '<p>Text.</p>', 'linked' => $linked];
file_put_contents("$dl/$base/sections/section-1.json", json_encode($provision('section-1', '1', [])));
file_put_contents("$dl/$base/sections/section-2.json", json_encode($provision('section-2', '2', [
    ['collection' => 'NOTIFICATION', 'title' => 'Amendment in Guidelines, 2020', 'date' => '2020-12-09', 'files' => [$f('sec-bi', 'bi'), $f('sec-en', 'en')]],
    ['collection' => 'RULE', 'title' => 'Guidelines Rules, 2020', 'date' => '2020-03-01', 'files' => [$f('sec-dup', 'en')]],
])));
file_put_contents("$dl/$base/sections/section-3.json", json_encode($provision('section-3', '3', [
    ['collection' => 'RULE', 'title' => 'Test Rules, 2017', 'date' => '2017-06-15', 'files' => [$f('rule', 'hi')]],
    ['collection' => 'NOTIFICATION', 'title' => 'Same guidelines, linked again', 'date' => '2020-12-09', 'files' => [$f('sec-bi', 'bi')]],
    ['collection' => 'NOTIFICATION', 'title' => 'S.O. 1 (E): Tab notification', 'date' => '2020-01-05', 'files' => [$f('tab-en', 'en')]],
])));
// Each request in a PHP process of its own: FileController::send() flushes every output buffer before
// streaming a PDF, so its output can only be read from outside.
$dlRun = static function (string $uri, bool $gate = true) use ($dl): string {
    $code = 'require ' . var_export(dirname(__DIR__) . '/src/bootstrap.php', true) . ';'
        . ' parse_str((string) parse_url($argv[1], PHP_URL_QUERY), $_GET); $_SERVER["REQUEST_METHOD"] = "GET";'
        . ' (new LegalQueryWeb\App(["content_dir" => $argv[2], "site_url" => "https://example.org", "site_name" => "LQ",'
        . ' "per_page" => 20, "pdf_captcha" => $argv[3] === "1", "pdf_captcha_secret" => str_repeat("s", 32)]))->run("GET", $argv[1]);';
    return (string) shell_exec(implode(' ', array_map('escapeshellarg', [PHP_BINARY, '-r', $code, $uri, $dl, $gate ? '1' : '0'])) . ' 2>/dev/null');
};
$page = static function (string $html): array {
    preg_match('#<section class="card pdf-check"([^>]*)>#', $html, $sec);
    preg_match('#data-pdf-lang="([^"]*)"#', $sec[1] ?? '', $lang);
    preg_match('#<h1>(.*?)</h1>#s', $html, $h1);
    preg_match_all('#<th scope="row">(.*?)</th><td>(.*?)</td>#', $html, $rows);
    preg_match('#<a href="([^"]+)">← Back to the page</a>#', $html, $back);
    preg_match_all('#<a href="([^"]+)"[^>]*data-lang="([^"]+)"#', $html, $others);
    return [
        'lang' => $lang[1] ?? null,
        'title' => html_entity_decode(trim($h1[1] ?? ''), ENT_QUOTES),
        'rows' => array_combine($rows[1], array_map(static fn(string $v): string => html_entity_decode($v, ENT_QUOTES), $rows[2])),
        'back' => html_entity_decode($back[1] ?? '', ENT_QUOTES),
        'others' => array_combine(array_map(static fn(string $u): string => html_entity_decode($u, ENT_QUOTES), $others[1]), $others[2]),
    ];
};
$filesUrl = static fn(string $key): string => Url::file(['sha256' => $sha[$key], 'filename' => basename($pdfs[$key][0])]);
// Documents linked only from sections: /pdf?…&s={first section linking it}&f={file name} (owner, 2026-11-26).
$secUrl = [
    'sec-bi' => "/pdf?j=central&a=$a&s=section-2&f=guidelines_2020",
    'sec-en' => "/pdf?j=central&a=$a&s=section-2&f=guidelines_2020_english",
    'sec-dup' => "/pdf?j=central&a=$a&s=section-2&f=rules-guidelines_2020",
    'rule' => "/pdf?j=central&a=$a&s=section-3&f=rules_notified",
];
$tabEn = "/pdf?j=central&a=$a&d=notifications&f=n1_english";
$tabHi = "/pdf?j=central&a=$a&d=notifications&f=n1_hindi";
$dlContent = new Content($dl);
$dlAct = $dlContent->act('central', $a);
$hrefs = array_map(static fn(array $l): string => $l['href'], $dlContent->pdfLinks($dlAct, array_keys(LegalQueryWeb\Controller\ActController::TABS)));
check('dl pdf links: section docs by first section, same name gets its folder, tab doc keeps its tab url',
    [$hrefs[$sha['sec-bi']], $hrefs[$sha['sec-en']], $hrefs[$sha['sec-dup']], $hrefs[$sha['rule']], $hrefs[$sha['tab-en']], isset($hrefs[$sha['orphan']])],
    [$secUrl['sec-bi'], $secUrl['sec-en'], $secUrl['sec-dup'], $secUrl['rule'], $tabEn, false]);
// The Act page links them there (no /files/ link left).
$actHtml = html_entity_decode($dlRun("/acts/central/$a"), ENT_QUOTES);
check('dl act page links section docs by /pdf url', [str_contains($actHtml, '/files/'), array_values(array_filter($secUrl, static fn(string $u): bool => !str_contains($actHtml, "href=\"$u\"")))], [false, []]);
// Old /files/ URLs: 301 to that /pdf URL; only a PDF linked from nowhere stays there.
$hrefOf = new ReflectionMethod(LegalQueryWeb\Controller\FileController::class, 'hrefOf');
$fileCtl = (new ReflectionClass(LegalQueryWeb\Controller\FileController::class))->newInstanceWithoutConstructor();
(new ReflectionProperty(LegalQueryWeb\Controller\Controller::class, 'content'))->setValue($fileCtl, $dlContent);
check('dl old /files/ urls go to the /pdf url',
    array_map(static fn(string $k): ?string => $hrefOf->invoke($fileCtl, "$dl/{$pdfs[$k][0]}", $sha[$k]), ['sec-bi', 'rule', 'tab-hi', 'orphan']),
    [$secUrl['sec-bi'], $secUrl['rule'], $tabHi, null]);
check('dl unknown section or file: 404', array_map(static fn(string $u): bool => str_contains($dlRun($u), 'Page not found'),
    ["/pdf?j=central&a=$a&s=section-1&f=guidelines_2020", "/pdf?j=central&a=$a&s=section-2&f=nope", "/pdf?j=central&a=$a&s=section-2&f=guidelines_2020&v=hi"]), [true, true, true]);

// The Act's own PDF.
$p = $page($dlRun("/pdf?j=central&a=$a"));
check('dl act pdf: title, language, back', [$p['title'], $p['lang'], $p['rows']['Language'] ?? null, $p['back']], ['The Test Act, 2020', 'en', 'English', "/acts/central/$a"]);
// A notification in the Notifications tab: its own languages only, the other language linked.
$p = $page($dlRun($tabEn));
check('dl tab notification: title, date, language', [$p['title'], $p['rows']['Date'] ?? null, $p['rows']['Document'] ?? null, $p['lang']], ['S.O. 1 (E): Tab notification', '5 Jan 2020', 'Notification', 'en']);
check('dl tab notification: other pdf, back', [$p['others'], $p['back']], [[$tabHi => 'hi'], "/acts/central/$a/notifications"]);
check('dl tab notification hindi', [$page($dlRun($tabHi))['lang'], $page($dlRun($tabHi))['others']], ['hi', [$tabEn => 'en']]);
// Linked only from sections: title, date, language, other PDF and back to the section.
$p = $page($dlRun($secUrl['sec-bi']));
check('dl section doc: title and date', [$p['title'], $p['rows']['Date'] ?? null, $p['rows']['Under'] ?? null], ['Amendment in Guidelines, 2020', '9 Dec 2020', 'The Test Act, 2020']);
check('dl section doc: language bi (menu: English + हिन्दी)', $p['lang'], 'bi');
check('dl section doc: other pdf of the document', $p['others'], [$secUrl['sec-en'] => 'en']);
check('dl section doc: back to the first section linking it', $p['back'], "/acts/central/$a#section-2");
$p = $page($dlRun($secUrl['sec-en']));
check('dl english pdf of that document', [$p['lang'], $p['others'], $p['back']], ['en', [$secUrl['sec-bi'] => 'bi'], "/acts/central/$a#section-2"]);
check('dl same file name in another folder', $page($dlRun($secUrl['sec-dup']))['title'], 'Guidelines Rules, 2020');
// A rule in pdf/rules/ although the Act has no Rules tab: the section, not a 404 tab; Hindi only.
$p = $page($dlRun($secUrl['rule']));
check('dl section rule: title, language, back', [$p['title'], $p['lang'], $p['others'], $p['back']], ['Test Rules, 2017', 'hi', [], "/acts/central/$a#section-3"]);
// Linked nowhere: the file name as title, every language (no data-pdf-lang), the Act page.
$p = $page($dlRun($filesUrl('orphan')));
check('dl /files/ unlinked pdf', [$p['title'], $p['lang'], $p['back']], ['some order 2019', null, "/acts/central/$a"]);
// Every back link and every "Other PDFs" link opens a page (no 404).
$links = [];
foreach (array_merge(["/pdf?j=central&a=$a", $tabEn, $tabHi, $filesUrl('orphan')], array_values($secUrl)) as $u) {
    $p = $page($dlRun($u));
    $links[] = $p['back'];
    array_push($links, ...array_keys($p['others']));
}
$broken = array_values(array_filter(array_unique($links), static fn(string $u): bool => str_contains($dlRun(preg_replace('/#.*$/', '', $u)), 'Page not found')));
check('dl back and other links all open', $broken, []);
// Without the question the PDF itself is sent: same bytes, then the site's title (PdfTitle).
$served = $dlRun($secUrl['sec-bi'], false);
check('dl served pdf starts with the file', str_starts_with($served, (string) file_get_contents("$dl/{$pdfs['sec-bi'][0]}")), true);
$titleHex = 'FEFF' . strtoupper(bin2hex(mb_convert_encoding('Amendment in Guidelines, 2020', 'UTF-16BE', 'UTF-8')));
check('dl served pdf has the document title', str_contains($served, "/Title <$titleHex>"), true);
check('dl served act pdf has the act title', str_contains($dlRun("/pdf?j=central&a=$a", false), '/Title <FEFF' . strtoupper(bin2hex(mb_convert_encoding('The Test Act, 2020', 'UTF-16BE', 'UTF-8'))) . '>'), true);
array_map('unlink', array_merge(glob("$dl/$base/pdf/*/*.pdf") ?: [], glob("$dl/$base/pdf/*.pdf") ?: [], glob("$dl/$base/sections/*.json") ?: [], glob("$dl/$base/*.json") ?: [], glob("$dl/files/*.json") ?: [], glob("$dl/central/*.json") ?: [], ["$dl/manifest.json"]));
foreach (array_merge(glob("$dl/$base/pdf/*", GLOB_ONLYDIR) ?: [], ["$dl/$base/pdf", "$dl/$base/sections", "$dl/$base", "$dl/central/acts", "$dl/central", "$dl/files", $dl]) as $d) {
    @rmdir($d);
}

check('tidy text', tidy_text("7 \n \n\nTHE FIRST SCHEDULE \n\n\n \n1.   Act  \n \n "), "7\n\nTHE FIRST SCHEDULE\n\n1.   Act");

echo $failures ? "\n$failures of $count checks failed\n" : "$count checks passed\n";
exit($failures ? 1 : 0);
