<?php
/**
 * A policy page (Privacy Policy, Terms of Service, Legal Disclaimer).
 *
 * @var LegalQueryWeb\View $view
 * @var string $title
 * @var string $body_html Trusted HTML from `pages/<slug>.html` (written by us, see PageController).
 */
use LegalQueryWeb\Url;

?>
<?= $view->partial('crumbs', ['crumbs' => [['Home', Url::home()]], 'current' => $title]) ?>
<div class="page-head">
  <h1><?= e($title) ?></h1>
</div>
<div class="policy">
<?= $body_html ?>
</div>
