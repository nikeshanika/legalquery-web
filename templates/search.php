<?php
/**
 * Search results: Acts first, then sections and schedules.
 *
 * @var LegalQueryWeb\View $view
 * @var string $q
 * @var list<array<string, mixed>> $results
 * @var array<string, mixed> $pager
 */
use LegalQueryWeb\Url;

?>
<?= $view->partial('crumbs', ['crumbs' => [['Home', Url::home()]], 'current' => 'Search']) ?>
<div class="page-head">
  <h1>Search</h1>
</div>
<form class="card search-form" action="/search" method="get" role="search">
  <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Act title, Act number or section heading" aria-label="Search" autofocus>
  <button class="btn btn-primary" type="submit">Search</button>
</form>
<?php if ($q !== ''): ?>
<div class="card">
  <div class="results-bar"><span><?php if ($pager['total']): ?>Showing <b><?= $pager['from'] ?></b>–<b><?= $pager['to'] ?></b> of <b><?= number_format($pager['total']) ?></b> results for “<?= e($q) ?>”<?php else: ?>No results for “<?= e($q) ?>”<?php endif; ?></span></div>
<?php foreach ($results as $hit): ?>
<?php if ($hit['type'] === 'act'): ?>
  <?= $view->partial('act-rows', ['acts' => [$hit['act']]]) ?>
<?php else: ?>
  <div class="act-row">
    <div class="act-main">
      <h3><a href="<?= e($hit['url']) ?>"><span class="badge"><?= e($hit['label']) ?></span> <?= e($hit['heading']) ?></a></h3>
      <div class="meta"><?= e($hit['act']['title']) ?></div>
    </div>
  </div>
<?php endif; ?>
<?php endforeach; ?>
<?php if (!$results): ?>
  <p class="empty pad">Try fewer or different words. Search looks at Act titles, Act numbers, years and section headings.</p>
<?php endif; ?>
</div>
<?= $view->partial('pager', ['pager' => $pager]) ?>
<?php endif; ?>
