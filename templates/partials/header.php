<?php
/**
 * Site header and main menu (design "L": navy bar with a gold rule; owner, 2026-11-25). Only this site's own pages: no links to legalquery.in pages
 * (owner, 2026-11-02). Change the menu here only.
 *
 * @var LegalQueryWeb\View $view
 * @var string $nav Active menu key ('home', 'acts', 'states', 'search' or '').
 *
 * Language menu (owner, 2026-11-25): one language for the whole site, kept in the browser (site.js, localStorage);
 * today it picks which PDF an Act page links ("PDF: हिन्दी"). site.js lists only the languages of the PDFs on the
 * page: on an Act page the Act's own PDFs, elsewhere the PDF links shown, and English alone on a page without PDFs. The pages themselves are the same for everyone, so they
 * stay cacheable. Hidden without JavaScript (then every Act page links its first PDF, English).
 */
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Url;

$menu = [
    ['Home', Url::home(), 'home'],
    [Jurisdiction::name(Jurisdiction::DEFAULT), Url::actList(Jurisdiction::DEFAULT), 'acts'],
    ...(count(Jurisdiction::codes()) > 1 ? [['States', Url::states(), 'states']] : []),
    ['Search', Url::search(), 'search'],
];
?>
<header class="site-header">
  <div class="container topbar">
    <a class="site-name" href="/">
      <img class="site-logo" src="/assets/img/logo.svg?v=3" width="52" height="52" alt="">
      <span class="site-name-text"><strong><?= e($view->config['site_name']) ?></strong></span>
    </a>
    <form class="top-search" action="/search" method="get" role="search">
      <input class="input" type="search" name="q" placeholder="Search Acts and sections" aria-label="Search Acts and sections">
      <button class="btn" type="submit">Search</button>
    </form>
    <label class="lang-pick" hidden title="Language">
      <span class="lang-label">Language</span>
      <select data-lang-pick>
<?php foreach (PDF_LANGUAGES as $code => [$label]): ?>
<?php if ($code !== 'bi'): ?>
        <option value="<?= e($code) ?>"<?= $code !== 'en' ? ' lang="' . e($code) . '"' : '' ?>><?= e($label) ?></option>
<?php endif; ?>
<?php endforeach; ?>
      </select>
    </label>
    <div class="top-actions">
      <button class="menu-toggle" type="button" aria-label="Menu" aria-expanded="false" aria-controls="mainnav">≡</button>
    </div>
    <nav class="mainnav" id="mainnav" aria-label="Main">
      <ul>
<?php foreach ($menu as [$label, $url, $key]): ?>
        <li><a href="<?= e($url) ?>"<?= $key === $nav ? ' class="active" aria-current="page"' : '' ?>><?= e($label) ?></a></li>
<?php endforeach; ?>
      </ul>
    </nav>
  </div>
</header>
