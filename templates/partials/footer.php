<?php
/**
 * Site footer with the India Code source note (plain text: no India Code URL anywhere on the site,
 * owner 2026-11-03). Only this site's pages and official references; no links to legalquery.in
 * pages (owner, 2026-11-02). Change the footer here only.
 *
 * @var LegalQueryWeb\View $view
 */
use LegalQueryWeb\Url;

$columns = [
    'Laws' => [
        ['Home', Url::home()],
        ['Central Acts', Url::actList()],
        ['Search', Url::search()],
    ],
    'Official sources' => [
        ['Legislative Department', 'https://legislative.gov.in/'],
        ['e-Gazette of India', 'https://egazette.gov.in/'],
    ],
];
?>
<footer class="site-footer">
  <div class="container">
    <div class="foot-grid">
      <div>
        <h3><?= e($view->config['site_name']) ?> Laws</h3>
        <p>Central Acts of India section by section, with schedules, rules, notifications and official PDFs.</p>
      </div>
<?php foreach ($columns as $title => $links): ?>
      <div>
        <h3><?= e($title) ?></h3>
<?php foreach ($links as [$label, $url]): ?>
        <a href="<?= e($url) ?>"<?= str_starts_with($url, 'http') ? ' target="_blank" rel="noopener"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
      </div>
<?php endforeach; ?>
    </div>
    <div class="source-note">
      Source: India Code, Government of India.
      Shown for information; this is not the official certified copy and not legal advice.
      <br>© <?= date('Y') ?> <?= e($view->config['site_name']) ?>. All rights reserved.
    </div>
  </div>
</footer>
