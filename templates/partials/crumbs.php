<?php
/**
 * Breadcrumb, with the same trail as schema.org BreadcrumbList for search engines.
 *
 * @var LegalQueryWeb\View $view
 * @var list<array{0: string, 1: string}> $crumbs Links before the current page.
 * @var string $current Current page label.
 */
use LegalQueryWeb\Seo;

?>
<nav class="crumbs" aria-label="Breadcrumb">
<?php foreach ($crumbs as [$label, $url]): ?>
  <a href="<?= e($url) ?>"><?= e($label) ?></a><span class="sep" aria-hidden="true">›</span>
<?php endforeach; ?>
  <span aria-current="page"><?= e($current) ?></span>
</nav>
<?= Seo::script(Seo::breadcrumbs($crumbs, $current, $view->absolute(...))) ?>
