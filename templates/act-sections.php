<?php
/**
 * Act page, "Sections" tab: a Contents column with this page's sections (links to #slug), and the sections,
 * which open in place. On phones and tablets (up to 900px) the Contents column is a bar that stays at the top of
 * the screen: it names the section being read and opens the list (site.js, owner 2026-11-27).
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var list<array<string, mixed>> $tabs
 * @var list<array<string, mixed>> $sections
 * @var array<string, mixed> $pager
 */
?>
<?= $view->partial('act-head', ['act' => $act, 'tabs' => $tabs]) ?>
<div class="act-sections<?= $sections ? '' : ' no-toc' ?>">
<?php if ($sections): ?>
<nav class="toc" aria-label="Contents" data-toc>
  <div class="toc-head">
    <span class="toc-current" data-toc-current>Contents</span>
    <button type="button" class="toc-toggle" aria-label="Show contents" aria-expanded="false" aria-controls="toc-list"><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M3 5l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
  </div>
  <div class="toc-list" id="toc-list">
  <ol>
<?php foreach ($sections as $item): ?>
    <li><a href="#<?= e($item['slug']) ?>"><span class="n"><?= e(trim((string) $item['number'])) ?></span><span><?= e($item['heading'] !== '' ? rtrim($item['heading'], '.') : provision_label($item)) ?></span></a></li>
<?php endforeach; ?>
  </ol>
<?php if ($pager['next']): ?>
  <p class="note"><a href="<?= e($pager['next']) ?>">Next sections →</a></p>
<?php endif; ?>
  </div>
</nav>
<?php endif; ?>
<section class="card panel" aria-label="Sections">
  <div class="listbar">
    <span class="tools">
      <button type="button" class="iconbtn" data-expand="all" title="Expand all" aria-label="Expand all" hidden><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M4 5l3-3 3 3M4 9l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
      <button type="button" class="iconbtn" data-expand="none" title="Collapse all" aria-label="Collapse all" hidden><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M4 2l3 3 3-3M4 12l3-3 3 3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    </span>
    <span class="listbar-right">
<?php if ($pager['total']): ?>
      <span>Showing <b><?= $pager['from'] ?></b> to <b><?= $pager['to'] ?></b> of <b><?= $pager['total'] ?></b> results</span>
<?php endif; ?>
      <?= $view->partial('pager', ['pager' => $pager, 'always' => true]) ?>
    </span>
  </div>
  <?= $view->partial('provision-list', ['act' => $act, 'list' => $sections, 'kind' => 'SECTION']) ?>
  <?= $view->partial('pager', ['pager' => $pager]) ?>
</section>
</div>
