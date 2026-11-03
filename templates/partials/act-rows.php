<?php
/**
 * Rows of Acts (list pages, home, search).
 *
 * @var list<array<string, mixed>> $acts index.json entries
 * @var string $open_tab Optional: the button opens this tab of each Act (category pages).
 */
use LegalQueryWeb\Url;

$open_tab ??= '';
?>
<?php foreach ($acts as $act): ?>
<div class="act-row">
  <div class="act-main">
    <h3><a href="<?= e(Url::act($act['slug'])) ?>"><?= e($act['title']) ?></a></h3>
    <div class="meta">
<?php $bits = array_filter([
    $act['act_number'] !== '' ? 'Act ' . $act['act_number'] . ' of ' . $act['year'] : '',
    $act['enactment_date'] !== '' ? 'Enacted ' . format_date($act['enactment_date']) : '',
    preg_replace('/^Ministry of /', '', (string) $act['ministry']),
]); ?>
      <?= e(implode(' · ', $bits)) ?>
    </div>
    <div class="chips">
      <span class="chip plain"><?= number_format($act['sections']) ?> sections</span>
<?php if ($act['schedules']): ?>
      <span class="chip plain"><?= number_format($act['schedules']) ?> schedules</span>
<?php endif; ?>
<?php foreach ($act['tabs'] ?? [] as $tab): ?>
      <span class="chip gold"><?= e(ucfirst($tab)) ?></span>
<?php endforeach; ?>
    </div>
  </div>
<?php if ($open_tab !== ''): ?>
  <div class="act-side"><a class="btn btn-outline sm" href="<?= e(Url::act($act['slug'], $open_tab)) ?>"><?= e(ucfirst($open_tab)) ?> ›</a></div>
<?php else: ?>
  <div class="act-side"><a class="btn btn-outline sm" href="<?= e(Url::act($act['slug'])) ?>">Read Act</a></div>
<?php endif; ?>
</div>
<?php endforeach; ?>
