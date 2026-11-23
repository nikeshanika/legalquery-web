<?php
/**
 * The States and Union Territories on the site, in two groups (States, then Union Territories, as on
 * India Code), each linking to its Act list.
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, list<array{code: string, name: string, acts: int, sections: int}>> $groups
 *   heading ('States', 'Union Territories') => entries, A to Z; empty groups are left out.
 */
use LegalQueryWeb\Url;

?>
<?= $view->partial('crumbs', ['crumbs' => [['Home', Url::home()]], 'current' => 'States and Union Territories']) ?>
<div class="page-head">
  <h1>States and Union Territories</h1>
  <p>Acts of each State and Union Territory as listed on India Code, with sections where India Code has them, rules, notifications and official PDFs. More States are being added.</p>
</div>
<?php foreach ($groups as $heading => $entries): ?>
<?php if ($heading === 'Union Territories' && count($groups) > 1): ?>
<hr class="jur-divider">
<?php endif; ?>
<section class="jur-group" aria-labelledby="jur-<?= e(strtolower(str_replace(' ', '-', $heading))) ?>">
  <h2 id="jur-<?= e(strtolower(str_replace(' ', '-', $heading))) ?>"><?= e($heading) ?> <span class="count"><?= count($entries) ?></span></h2>
  <div class="cat-grid">
<?php foreach ($entries as $state): ?>
    <a class="card cat-card" href="<?= e(Url::actList($state['code'])) ?>"><strong><?= e($state['name']) ?></strong><span><?= number_format($state['acts']) ?> <?= $state['acts'] === 1 ? 'Act' : 'Acts' ?><?= $state['sections'] ? ' · ' . number_format($state['sections']) . ($state['sections'] === 1 ? ' section' : ' sections') : '' ?></span></a>
<?php endforeach; ?>
  </div>
</section>
<?php endforeach; ?>
