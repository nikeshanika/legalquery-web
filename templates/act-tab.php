<?php
/**
 * Act page, linked-document tab (Rules, Notifications, ...): paged table with PDFs. Every document of a tab has
 * the tab's type, so no RULE / NOTIFICATION label per row (owner, 2026-11-26).
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var list<array<string, mixed>> $tabs
 * @var string $label
 * @var list<array<string, mixed>> $items
 * @var array<string, mixed> $pager
 */
?>
<?= $view->partial('act-head', ['act' => $act, 'tabs' => $tabs]) ?>
<section class="card panel" aria-label="<?= e($label) ?>">
  <div class="listbar"><span>Showing <b><?= $pager['from'] ?></b>–<b><?= $pager['to'] ?></b> of <b><?= $pager['total'] ?></b> <?= e(strtolower($label)) ?></span></div>
  <div class="table-wrap">
    <table class="doctable">
      <thead><tr><th scope="col">Date</th><th scope="col">Document</th><th scope="col">PDF</th></tr></thead>
      <tbody>
<?php foreach ($items as $doc): ?>
        <tr>
          <td class="nowrap"><?= e(format_date($doc['date'])) ?></td>
          <td><div class="doc-title"><?= e($doc['title']) ?><?= $doc['state'] !== '' ? ' <span class="note">(' . e($doc['state']) . ')</span>' : '' ?></div></td>
          <td><?= $view->partial('file-links', ['files' => $doc['files'], 'compact' => true]) ?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= $view->partial('pager', ['pager' => $pager]) ?>
</section>
