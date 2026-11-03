<?php
/**
 * Act page, "Schedules" tab.
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var list<array<string, mixed>> $tabs
 * @var list<array<string, mixed>> $schedules With their text.
 */
?>
<?= $view->partial('act-head', ['act' => $act, 'tabs' => $tabs]) ?>
<section class="card panel" aria-label="Schedules">
  <div class="listbar">
    <span class="tools">
      <button type="button" class="iconbtn" data-expand="all" title="Expand all" aria-label="Expand all" hidden><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M4 5l3-3 3 3M4 9l3 3 3-3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
      <button type="button" class="iconbtn" data-expand="none" title="Collapse all" aria-label="Collapse all" hidden><svg width="14" height="14" viewBox="0 0 14 14" aria-hidden="true"><path d="M4 2l3 3 3-3M4 12l3-3 3 3" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    </span>
    <span class="listbar-right"><span><b><?= count($act['schedules']) ?></b> <?= count($act['schedules']) === 1 ? 'schedule' : 'schedules' ?></span></span>
  </div>
  <?= $view->partial('provision-list', ['act' => $act, 'list' => $schedules, 'kind' => 'SCHEDULE']) ?>
</section>
