<?php
/**
 * Site footer with the India Code source note (plain text: no India Code URL anywhere on the site,
 * owner 2026-11-03). Only this site's pages and official sources; no links to legalquery.in
 * pages (owner, 2026-11-02); the policy pages are this site's own (PageController). Change the footer here only.
 *
 * @var LegalQueryWeb\View $view
 */
use LegalQueryWeb\Controller\PageController;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Url;

$columns = [
    'Laws' => [
        ['Home', Url::home()],
        [Jurisdiction::name(Jurisdiction::DEFAULT), Url::actList(Jurisdiction::DEFAULT)],
        ...(count(Jurisdiction::codes()) > 1 ? [['States and UTs', Url::states()]] : []),
        ['Search', Url::search()],
    ],
    'Policies' => array_map(
        static fn(string $slug, string $title): array => [$title, Url::page($slug)],
        array_keys(PageController::PAGES),
        array_values(PageController::PAGES),
    ),
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
        <a class="foot-brand" href="/"><img src="/assets/img/logo.svg?v=3" width="44" height="44" alt=""><span><?= e($view->config['site_name']) ?></span></a>
        <p><?= e($view->config['site_name']) ?> brings together the Central Acts and the Acts of the States and Union Territories of India, section by section, with their schedules, rules, notifications and official PDFs.</p>
        <p class="foot-contact"><strong>Email:</strong> <a href="mailto:contact@legalquery.in">contact@legalquery.in</a><br><strong>Phone:</strong> <a href="tel:+918272000923">+91-8272000923</a></p>
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
      Disclaimer: Content sourced from India Code, Government of India. Original legal text is presented without modification.
      This content is provided for informational purposes only and does not constitute legal advice.
      LegalQuery is not an official government website.
      <div class="foot-copy">© <?= date('Y') ?> <?= e($view->config['site_name']) ?>. All rights reserved.</div>
    </div>
  </div>
</footer>
