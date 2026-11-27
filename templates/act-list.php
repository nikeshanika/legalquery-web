<?php
/**
 * Paged list of one jurisdiction's Acts, all or one year, with title/number filter: a filter bar and a table
 * (Year, Act No., Short title, Sections), design "W" (owner 2026-11-25).
 *
 * @var LegalQueryWeb\View $view
 * @var string $heading
 * @var list<array{0: string, 1: string}> $crumbs
 * @var list<array<string, mixed>> $acts
 * @var array<string, mixed> $pager
 * @var string $jurisdiction e.g. central
 * @var string $search
 * @var string $year
 * @var array<string, int> $years
 * @var int $total_all
 */
use LegalQueryWeb\Url;

$action = Url::actList($jurisdiction, $year);
?>
<?= $view->partial('crumbs', ['crumbs' => $crumbs, 'current' => $heading]) ?>
<div class="page-head">
  <h1><?= e($heading) ?></h1>
<?php if ($jurisdiction === LegalQueryWeb\Jurisdiction::DEFAULT): ?>
  <p>Acts of Parliament with their full text, sections, schedules, rules, notifications and official PDFs.</p>
<?php else: ?>
  <p>Acts of <?= e(LegalQueryWeb\Jurisdiction::detail($jurisdiction)) ?>, with sections where available, rules, notifications and official PDFs.</p>
<?php endif; ?>
</div>
<form class="filter-bar" method="get" action="<?= e($action) ?>" role="search">
  <label for="f-q">Filter</label>
  <input type="search" class="input" id="f-q" name="search" value="<?= e($search) ?>" placeholder="Title or Act number, e.g. labour, 37 of 2020">
  <label for="f-year">Year</label>
  <select class="input" id="f-year" name="year" data-autosubmit>
    <option value="">All years (<?= number_format($total_all) ?>)</option>
<?php foreach ($years as $y => $n): ?>
    <option value="<?= e($y) ?>"<?= (string) $y === $year ? ' selected' : '' ?>><?= e($y) ?> (<?= $n ?>)</option>
<?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Apply</button>
<?php if ($search !== '' || $year !== ''): ?>
  <a href="<?= e(Url::actList($jurisdiction)) ?>">Reset</a>
<?php endif; ?>
</form>
<p class="results-bar"><?php if ($pager['total']): ?>Showing <?= $pager['from'] ?> to <?= $pager['to'] ?> of <?= number_format($pager['total']) ?> Acts<?php else: ?>No Acts<?php endif; ?></p>
<?php if ($acts): ?>
<div class="table-wrap">
  <table class="wikitable">
    <thead><tr><th scope="col">Year</th><th scope="col">Act No.</th><th scope="col">Short title</th><th scope="col" class="num">Sections</th></tr></thead>
    <tbody>
<?php foreach ($acts as $act): ?>
      <tr>
        <td class="nowrap"><?= e($act['year']) ?></td>
        <td class="nowrap"><?= e($act['act_number']) ?></td>
        <td><a href="<?= e(Url::act($act['jurisdiction'], $act['slug'])) ?>"><?= e($act['title']) ?></a></td>
        <td class="num"><?= $act['sections'] ? number_format($act['sections']) : '–' ?></td>
      </tr>
<?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<p class="empty pad">No Acts match these filters.</p>
<?php endif; ?>
<?= $view->partial('pager', ['pager' => $pager]) ?>
