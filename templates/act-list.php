<?php
/**
 * Paged list of Central Acts, all or one year, with title/number filter.
 *
 * @var LegalQueryWeb\View $view
 * @var string $heading
 * @var list<array{0: string, 1: string}> $crumbs
 * @var list<array<string, mixed>> $acts
 * @var array<string, mixed> $pager
 * @var string $search
 * @var string $year
 * @var array<string, int> $years
 * @var int $total_all
 */
use LegalQueryWeb\Url;

$action = Url::actList($year);
?>
<?= $view->partial('crumbs', ['crumbs' => $crumbs, 'current' => $heading]) ?>
<div class="page-head">
  <h1><?= e($heading) ?></h1>
  <p>Acts of Parliament with their full text, sections, schedules, rules, notifications and official PDFs, from India Code.</p>
</div>
<div class="list-layout">
  <form class="card filter-card" method="get" action="<?= e($action) ?>" role="search">
    <h2>Filter Acts</h2>
    <label for="f-q">Title or Act number</label>
    <input type="search" class="input" id="f-q" name="search" value="<?= e($search) ?>" placeholder="e.g. labour, 37 of 2020">
    <label for="f-year">Year</label>
    <select class="input" id="f-year" name="year" data-autosubmit>
      <option value="">All years (<?= number_format($total_all) ?>)</option>
<?php foreach ($years as $y => $n): ?>
      <option value="<?= e($y) ?>"<?= (string) $y === $year ? ' selected' : '' ?>><?= e($y) ?> (<?= $n ?>)</option>
<?php endforeach; ?>
    </select>
    <button class="btn btn-primary" type="submit">Apply</button>
<?php if ($search !== '' || $year !== ''): ?>
    <a class="btn btn-outline" href="<?= e(Url::actList()) ?>">Reset filters</a>
<?php endif; ?>
  </form>
  <div>
    <div class="card">
      <div class="results-bar">
        <span><?php if ($pager['total']): ?>Showing <b><?= $pager['from'] ?></b>–<b><?= $pager['to'] ?></b> of <b><?= number_format($pager['total']) ?></b> Acts<?php else: ?>No Acts<?php endif; ?></span>
      </div>
<?php if ($acts): ?>
      <?= $view->partial('act-rows', ['acts' => $acts]) ?>
<?php else: ?>
      <p class="empty pad">No Acts match these filters.</p>
<?php endif; ?>
    </div>
    <?= $view->partial('pager', ['pager' => $pager]) ?>
  </div>
</div>
