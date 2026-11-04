<?php
/**
 * The one page shell: <head>, header, footer. Every page template is printed in $content.
 *
 * @var LegalQueryWeb\View $view
 * @var string $content
 * @var array{title: string, description: string, canonical: ?string, robots: string,
 *   prev: ?string, next: ?string, nav: string, type: string, schema: list<array<string, mixed>>} $meta
 */
use LegalQueryWeb\Seo;

$site = $view->config['site_name'];
$title = $meta['title'] . ' | ' . $site;
$image = (string) $view->config['share_image'];
$image = str_starts_with($image, 'http') ? $image : $view->absolute($image);
?>
<!doctype html>
<html lang="en-IN">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
<?php if ($meta['description'] !== ''): ?>
  <meta name="description" content="<?= e($meta['description']) ?>">
<?php endif; ?>
<?php if ($meta['robots'] !== ''): ?>
  <meta name="robots" content="<?= e($meta['robots']) ?>">
<?php endif; ?>
<?php if ($meta['canonical'] !== null): ?>
  <link rel="canonical" href="<?= e($view->absolute($meta['canonical'])) ?>">
<?php endif; ?>
<?php foreach (['prev', 'next'] as $rel): ?>
<?php if ($meta[$rel]): ?>
  <link rel="<?= $rel ?>" href="<?= e($view->absolute($meta[$rel])) ?>">
<?php endif; ?>
<?php endforeach; ?>
  <meta property="og:site_name" content="<?= e($site) ?> Laws">
  <meta property="og:type" content="<?= e($meta['type']) ?>">
  <meta property="og:title" content="<?= e($meta['title']) ?>">
<?php if ($meta['description'] !== ''): ?>
  <meta property="og:description" content="<?= e($meta['description']) ?>">
<?php endif; ?>
<?php if ($meta['canonical'] !== null): ?>
  <meta property="og:url" content="<?= e($view->absolute($meta['canonical'])) ?>">
<?php endif; ?>
  <meta property="og:locale" content="en_IN">
  <meta property="og:image" content="<?= e($image) ?>">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="theme-color" content="#1e3a8a">
  <link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml">
  <link rel="icon" href="/favicon.png" type="image/png" sizes="48x48">
  <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
  <link rel="stylesheet" href="/assets/css/site.css?v=9">
<?php foreach ($meta['schema'] as $block): ?>
  <?= Seo::script($block) ?>
<?php endforeach; ?>
</head>
<body>
<?= $view->partial('header', ['nav' => $meta['nav']]) ?>
<main class="container">
<?= $content ?>
</main>
<?= $view->partial('footer') ?>
<script src="/assets/js/site.js?v=2" defer></script>
</body>
</html>
