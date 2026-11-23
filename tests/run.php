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
file_put_contents("$tmp/files.json", json_encode([str_repeat('d', 64) => 'central/acts/x/pdf/a.pdf']));
file_put_contents("$tmp/manifest.json", '{"format":2}');
$tmpContent = new Content($tmp);
check('pdf via files/<ab>.json', $tmpContent->pdfPath(str_repeat('a', 64)), "$tmp/central/acts/x/pdf/a.pdf");
check('pdf path traversal', [$tmpContent->pdfPath(str_repeat('b', 64)), $tmpContent->pdfPath(str_repeat('c', 64))], [null, null]);
check('format 2 files.json is not read', $tmpContent->pdfPath(str_repeat('d', 64)), null);
check('old format not ready', $tmpContent->ready(), false);
array_map('unlink', ["$tmp/central/acts/x/pdf/a.pdf", "$tmp/files/aa.json", "$tmp/files/bb.json", "$tmp/files/cc.json", "$tmp/files.json", "$tmp/manifest.json"]);
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

Jurisdiction::register(['puducherry' => 'Puducherry']);
check('jurisdiction short label', [Jurisdiction::short('central'), Jurisdiction::short('puducherry')], ['Central', 'Puducherry']);
Jurisdiction::register([]);

// ---- readable Act PDF URLs (owner, 2026-11-19) ----
check('act pdf route', route('/acts/central/companies-act-2013.pdf'), 'FileController::actPdf {"jurisdiction":"central","name":"companies-act-2013"}');
check('act pdf route hindi', route('/acts/central/companies-act-2013-hindi.pdf'), 'FileController::actPdf {"jurisdiction":"central","name":"companies-act-2013-hindi"}');
check('act pdf route bad name', [route('/acts/central/Companies.pdf'), route('/acts/central/x/y.pdf'), route('/acts/kerala/x.pdf')], [null, null, null]);
check('act pdf canonical path', App::canonicalPath('/Acts/Central/X-Act.PDF'), '/acts/central/x-act.pdf');
check('act pdf url', [Url::actPdf('central', 'x-act-2020'), Url::actPdf('central', 'x-act-2020-hindi', true)], ['/acts/central/x-act-2020.pdf', '/acts/central/x-act-2020-hindi.pdf?download=1']);
check('pdf url prefers href', [Url::pdf(['href' => '/acts/central/x.pdf'], true), Url::pdf(['sha256' => 'ff', 'filename' => 'a.pdf'])], ['/acts/central/x.pdf?download=1', '/files/ff/a.pdf']);
$names = fn(array $files): array => array_column(\LegalQueryWeb\ActPdf::choices(['jurisdiction' => 'central', 'slug' => 'x-act-1972', 'files' => $files]), 'href');
check('act pdf names', $names([$pdf('hi', true, 'hH1972-28.pdf'), $pdf('en', true, '197228.pdf'), $pdf('en', true, 'a1972-28.pdf')]), ['/acts/central/x-act-1972.pdf', '/acts/central/x-act-1972-english-2.pdf', '/acts/central/x-act-1972-hindi.pdf']);
check('act pdf names bilingual and unknown', $names([$pdf('bi'), $pdf('unknown'), $pdf('hi'), $pdf('hi', true, 'h2.pdf')]), ['/acts/central/x-act-1972-english-hindi.pdf', '/acts/central/x-act-1972-hindi.pdf', '/acts/central/x-act-1972-hindi-2.pdf', '/acts/central/x-act-1972.pdf']);
check('act pdf names only hindi', $names([$pdf('hi')]), ['/acts/central/x-act-1972-hindi.pdf']);
check('act pdf names not archived', $names([$pdf('en', false)]), []);
check('act pdf names state language', $names([$pdf('gu', true, 'act_no._26_of_2019.pdf'), $pdf('en', true, 'msmeact19.pdf')]), ['/acts/central/x-act-1972.pdf', '/acts/central/x-act-1972-gujarati.pdf']);
$gujAct = ['jurisdiction' => 'gujarat', 'slug' => 'x-act-2019', 'files' => [$pdf('en', true, 'a.pdf'), $pdf('hi'), $pdf('gu', true, 'g.pdf')]];
check('act pdf moved english-2', \LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019-english-2')['href'] ?? null, '/acts/gujarat/x-act-2019-gujarati.pdf');
check('act pdf moved not old', [\LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019'), \LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019-hindi'), \LegalQueryWeb\ActPdf::moved($gujAct, 'x-act-2019-english-3')], [null, null, null]);
check('act pdf moved alone', \LegalQueryWeb\ActPdf::moved(['jurisdiction' => 'kerala', 'slug' => 'y-act', 'files' => [$pdf('ml')]], 'y-act')['href'] ?? null, '/acts/kerala/y-act-malayalam.pdf');
check('act pdf candidates', \LegalQueryWeb\ActPdf::candidates('hindi-act-1962-english-hindi-2'), ['hindi-act-1962-english-hindi-2', 'hindi-act-1962', 'hindi-act-1962-english']);
check('act pdf candidates main', \LegalQueryWeb\ActPdf::candidates('hindi-sahitya-sammelan-act-1962'), ['hindi-sahitya-sammelan-act-1962']);
check('act pdf candidates hindi', \LegalQueryWeb\ActPdf::candidates('x-act-hindi'), ['x-act-hindi', 'x-act']);
check('act pdf candidates state language', [\LegalQueryWeb\ActPdf::candidates('x-act-gujarati'), \LegalQueryWeb\ActPdf::candidates('x-act-telugu-2')], [['x-act-gujarati', 'x-act'], ['x-act-telugu-2', 'x-act']]);
$keralaAct = $content->act('central', $kerala);
check('fixture act pdf', \LegalQueryWeb\ActPdf::find($keralaAct, $kerala)['href'] ?? null, "/acts/central/$kerala.pdf");
check('fixture act pdf missing', \LegalQueryWeb\ActPdf::find($keralaAct, "$kerala-hindi"), null);
check('tidy text', tidy_text("7 \n \n\nTHE FIRST SCHEDULE \n\n\n \n1.   Act  \n \n "), "7\n\nTHE FIRST SCHEDULE\n\n1.   Act");

echo $failures ? "\n$failures of $count checks failed\n" : "$count checks passed\n";
exit($failures ? 1 : 0);
