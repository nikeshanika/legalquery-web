<?php
/**
 * Act page, "Act Details" tab: metadata, summary, legislative history.
 * summary_html / history_html were sanitised by the scraper's export.
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var list<array<string, mixed>> $tabs
 * @var array<string, string> $rows
 */
?>
<?= $view->partial('act-head', ['act' => $act, 'tabs' => $tabs]) ?>
<section class="card panel" aria-label="Act details">
  <h2 class="h2">Act details</h2>
  <div class="table-wrap">
    <table class="kv">
      <tbody>
<?php foreach ($rows as $label => $value): ?>
        <tr><th scope="row"><?= e($label) ?></th><td><?= e($value) ?></td></tr>
<?php endforeach; ?>
        <tr><th scope="row">Source</th><td>India Code, Government of India</td></tr>
      </tbody>
    </table>
  </div>
<?php if ($act['summary_html'] !== ''): ?>
  <h2 class="h2" id="summary">Summary</h2>
  <div class="law-text"><?= $act['summary_html'] ?></div>
<?php endif; ?>
<?php if ($act['history_html'] !== ''): ?>
  <h2 class="h2" id="history">Legislative history</h2>
  <div class="law-text"><?= $act['history_html'] ?></div>
<?php endif; ?>
</section>
