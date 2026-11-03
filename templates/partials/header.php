<?php
/**
 * Site header and main menu. Only this site's own pages: no links to legalquery.in pages
 * (owner, 2026-11-02). Change the menu here only.
 *
 * @var LegalQueryWeb\View $view
 * @var string $nav Active menu key ('home', 'acts', 'search' or '').
 */
use LegalQueryWeb\Url;

$menu = [
    ['Home', Url::home(), 'home'],
    ['Central Acts', Url::actList(), 'acts'],
    ['Search', Url::search(), 'search'],
];
?>
<header class="site-header">
  <div class="container topbar">
    <a class="site-name" href="/">⚖️ <?= e($view->config['site_name']) ?> <span class="site-tag">Laws</span></a>
    <form class="top-search" action="/search" method="get" role="search">
      <input class="input" type="search" name="q" placeholder="Search Acts and sections…" aria-label="Search Acts and sections">
    </form>
    <div class="top-actions">
      <button class="menu-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="mainnav">≡</button>
    </div>
  </div>
  <nav class="mainnav" id="mainnav" aria-label="Main">
    <div class="container">
      <ul>
<?php foreach ($menu as [$label, $url, $key]): ?>
        <li><a href="<?= e($url) ?>"<?= $key === $nav ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a></li>
<?php endforeach; ?>
      </ul>
    </div>
  </nav>
</header>
