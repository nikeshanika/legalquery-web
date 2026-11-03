<?php
/**
 * Sections or schedules that expand in place. Each row has the provision's slug as id,
 * so ".../act#section-2" opens Section 2; its own page is linked inside the row.
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var list<array<string, mixed>> $list
 * @var string $kind SECTION or SCHEDULE
 */
use LegalQueryWeb\Url;

?>
<div class="sec-list" data-sections>
  <div class="sec-list-head"><?= $kind === 'SCHEDULE' ? 'Schedule' : 'Section' ?></div>
<?php foreach ($list as $item): ?>
<?php $item['kind'] = $kind; $label = provision_label($item); ?>
  <details class="sec" id="<?= e($item['slug']) ?>">
    <summary>
      <span class="badge<?= $kind === 'SCHEDULE' ? ' gold' : '' ?>"><?= e($label) ?></span>
      <span class="sec-title"><?= e($item['heading'] !== '' ? $item['heading'] : $label) ?></span>
<?php if (!empty($item['linked'])): ?>
      <span class="sec-docs" title="Linked rules and notifications"><?= count($item['linked']) ?> docs</span>
<?php endif; ?>
      <span class="chev" aria-hidden="true">›</span>
    </summary>
    <div class="sec-body">
      <?= $view->partial('provision-body', ['act' => $act, 'item' => $item]) ?>
      <div class="sec-tools"><a class="linkbtn" href="<?= e(Url::provision($act['slug'], $item['slug'])) ?>">Open <?= e($label) ?> on its own page ›</a></div>
    </div>
  </details>
<?php endforeach; ?>
<?php if (!$list): ?>
  <p class="empty pad">No <?= $kind === 'SCHEDULE' ? 'schedules' : 'sections' ?> yet.</p>
<?php endif; ?>
</div>
