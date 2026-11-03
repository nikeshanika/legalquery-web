<?php
/**
 * Central Acts that have one kind of linked document (Rules, Notifications, ...).
 *
 * @var LegalQueryWeb\View $view
 * @var string $heading e.g. "Rules"
 * @var string $tab e.g. "rules"
 * @var list<array<string, mixed>> $acts
 * @var array<string, mixed> $pager
 * @var array<string, int> $categories tab => number of Acts
 */
use LegalQueryWeb\Controller\ActController;
use LegalQueryWeb\Url;

?>
<?= $view->partial('crumbs', ['crumbs' => [['Home', Url::home()]], 'current' => $heading]) ?>
<div class="page-head">
  <h1><?= e($heading) ?></h1>
  <p>Central Acts with <?= e(strtolower($heading)) ?> made under them, from India Code. Open an Act to read its <?= e(strtolower($heading)) ?> and their official PDFs.</p>
</div>
<div class="list-layout">
  <nav class="card filter-card" aria-label="Categories">
    <h2>Browse by category</h2>
    <div class="cat-links">
      <a href="<?= e(Url::actList()) ?>">Acts</a>
<?php foreach ($categories as $key => $n): ?>
      <a href="<?= e(Url::category($key)) ?>"<?= $key === $tab ? ' class="active" aria-current="page"' : '' ?>><?= e(collection_label(ActController::TABS[$key], true)) ?><span class="n"><?= number_format($n) ?></span></a>
<?php endforeach; ?>
    </div>
  </nav>
  <div>
    <div class="card">
      <div class="results-bar">
        <span>Showing <b><?= $pager['from'] ?></b>–<b><?= $pager['to'] ?></b> of <b><?= number_format($pager['total']) ?></b> Acts with <?= e(strtolower($heading)) ?></span>
      </div>
      <?= $view->partial('act-rows', ['acts' => $acts, 'open_tab' => $tab]) ?>
    </div>
    <?= $view->partial('pager', ['pager' => $pager]) ?>
  </div>
</div>
