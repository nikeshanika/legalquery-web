<?php
/**
 * Top of every Act tab, laid out like India Code's Act page: breadcrumb, title, ministry box,
 * the Act's PDFs as plain links on the right (no PDF preview, no QR code: owner, 2026-11-02),
 * then the tabs.
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var list<array{label: string, count: ?int, url: string, active: bool}> $tabs
 */
use LegalQueryWeb\Url;

// Sections tab: the trail ends at the Act. Other tabs: the Act is a link and the tab is the current page.
$crumbs = [['Home', Url::home()], ['Central Acts', Url::actList()]];
$current = $act['title'];
foreach ($tabs as $tab) {
    if ($tab['active'] && $tab['url'] !== Url::act($act['slug'])) {
        $crumbs[] = [$act['title'], Url::act($act['slug'])];
        $current = $tab['label'];
    }
}
?>
<?= $view->partial('crumbs', ['crumbs' => $crumbs, 'current' => $current]) ?>
<header class="act-head">
  <div class="act-head-main">
    <h1><?= e($act['title']) ?></h1>
<?php if ($act['ministry'] !== ''): ?>
    <p class="ministry-box"><span class="ministry-icon" aria-hidden="true">🏛</span><b>Administered Ministry:</b> <?= e($act['ministry']) ?></p>
<?php endif; ?>
  </div>
  <?= $view->partial('act-pdfs', ['files' => $act['files']]) ?>
</header>
<nav class="tabs" aria-label="Act contents">
<?php foreach ($tabs as $tab): ?>
  <a class="tab<?= $tab['active'] ? ' active' : '' ?>" href="<?= e($tab['url']) ?>"<?= $tab['active'] ? ' aria-current="page"' : '' ?>><?= e($tab['label']) ?></a>
<?php endforeach; ?>
</nav>
<?php if (!$act['links_complete']): ?>
<p class="data-warn">Rules, notifications and other linked documents for this Act are still being collected.</p>
<?php endif; ?>
