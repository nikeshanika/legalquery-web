<?php
/**
 * The States and Union Territories on the site, each linking to its Act list.
 *
 * @var LegalQueryWeb\View $view
 * @var list<array{code: string, name: string, acts: int, sections: int}> $states
 */
use LegalQueryWeb\Url;

?>
<?= $view->partial('crumbs', ['crumbs' => [['Home', Url::home()]], 'current' => 'States and Union Territories']) ?>
<div class="page-head">
  <h1>States and Union Territories</h1>
  <p>Acts of each State and Union Territory as listed on India Code, with sections where India Code has them, rules, notifications and official PDFs. More States are being added.</p>
</div>
<section class="categories" aria-label="States and Union Territories">
  <div class="cat-grid">
<?php foreach ($states as $state): ?>
    <a class="card cat-card" href="<?= e(Url::actList($state['code'])) ?>"><strong><?= e($state['name']) ?></strong><span><?= number_format($state['acts']) ?> <?= $state['acts'] === 1 ? 'Act' : 'Acts' ?><?= $state['sections'] ? ' · ' . number_format($state['sections']) . ($state['sections'] === 1 ? ' section' : ' sections') : '' ?></span></a>
<?php endforeach; ?>
  </div>
</section>
