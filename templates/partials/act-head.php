<?php
/**
 * Top of every Act tab (design "W", owner 2026-11-25): breadcrumb, title, a one-line introduction made from
 * the Act's own fields, and an infobox on the right with the facts. Then the tabs, with "PDF: English" (the PDF
 * in the language picked in the header menu; partials/act-pdfs.php) at the right of the tab row.
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var list<array{label: string, count: ?int, url: string, active: bool}> $tabs
 */
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Url;

// Sections tab: the trail ends at the Act. Other tabs: the Act is a link and the tab is the current page.
$crumbs = [['Home', Url::home()]];
if ($act['jurisdiction'] !== Jurisdiction::DEFAULT) {
    $crumbs[] = ['States', Url::states()];
}
$crumbs[] = [Jurisdiction::name($act['jurisdiction']), Url::actList($act['jurisdiction'])];
$current = $act['title'];
foreach ($tabs as $tab) {
    if ($tab['active'] && $tab['url'] !== Url::act($act['jurisdiction'], $act['slug'])) {
        $crumbs[] = [$act['title'], Url::act($act['jurisdiction'], $act['slug'])];
        $current = $tab['label'];
    }
}

$number = $act['act_number'] !== '' ? $act['act_number'] . ' of ' . $act['year'] : '';
$kind = $act['jurisdiction'] === Jurisdiction::DEFAULT ? 'a Central Act' : 'an Act of ' . Jurisdiction::detail($act['jurisdiction']);
// "NO MINISTRY / Legal Department" in the data: leave the placeholder out.
$ministry = trim((string) preg_replace('~^\s*no ministry\s*/?\s*~i', '', (string) $act['ministry']));
$nSections = count($act['sections']);
$nSchedules = count($act['schedules']);
$counts = array_values(array_filter([
    $nSections ? number_format($nSections) . ($nSections === 1 ? ' section' : ' sections') : '',
    $nSchedules ? number_format($nSchedules) . ($nSchedules === 1 ? ' schedule' : ' schedules') : '',
]));
$facts = array_filter([
    'Act No.' => $number,
    'Enacted' => format_date($act['enactment_date']),
    'In force from' => format_date($act['enforcement_date'] ?? ''),
    'Jurisdiction' => Jurisdiction::short($act['jurisdiction']),
    'Ministry' => $ministry,
    'Sections' => $nSections ? number_format($nSections) : '',
    'Schedules' => $nSchedules ? number_format($nSchedules) : '',
], static fn(string $v): bool => $v !== '');
?>
<?= $view->partial('crumbs', ['crumbs' => $crumbs, 'current' => $current]) ?>
<header class="act-head">
  <div class="act-head-main">
    <h1><?= e($act['title']) ?></h1>
    <p class="act-from">From <?= e($view->config['site_name']) ?></p>
    <p class="act-intro"><b><?= e($act['title']) ?></b><?= $number !== '' ? ' (Act No. ' . e($number) . ')' : '' ?> is <?= e($kind) ?><?= $act['enactment_date'] !== '' ? ', enacted on ' . e(format_date($act['enactment_date'])) : '' ?><?= $ministry !== '' ? ' and administered by the ' . e($ministry) : '' ?>.<?php if ($counts !== []): ?> It has <?= e(implode(' and ', $counts)) ?>.<?php endif; ?></p>
  </div>
  <aside class="infobox" aria-label="About this Act">
    <div class="infobox-title"><?= e($act['title']) ?></div>
    <table>
<?php foreach ($facts as $label => $value): ?>
      <tr><th scope="row"><?= e($label) ?></th><td><?= e($value) ?></td></tr>
<?php endforeach; ?>
    </table>
  </aside>
</header>
<div class="tabs-bar">
  <nav class="tabs" aria-label="Act contents">
<?php foreach ($tabs as $tab): ?>
    <a class="tab<?= $tab['active'] ? ' active' : '' ?>" href="<?= e($tab['url']) ?>"<?= $tab['active'] ? ' aria-current="page"' : '' ?>><?= e($tab['label']) ?></a>
<?php endforeach; ?>
  </nav>
  <?= $view->partial('act-pdfs', ['act' => $act]) ?>
</div>
<?php if (!$act['links_complete']): ?>
<p class="data-warn">Rules, notifications and other linked documents for this Act are still being collected.</p>
<?php endif; ?>
