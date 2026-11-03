<?php
/**
 * A schedule as India Code shows it (items/<uuid>): a "Document" panel with the PDF and an
 * "Item Metadata" table. No QR code. The text the export has for schedules is extracted from the
 * PDF (page numbers, broken tables), so it is not shown: the PDF is the schedule.
 *
 * The thumbnail is the demo image, like the Act's PDF (see act-pdfs.php).
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var array<string, mixed> $item
 */
use LegalQueryWeb\Url;

$thumb = '/assets/img/pdf-thumb-demo.svg';
$meta = array_filter([
    'Published date' => format_date($act['enactment_date']),
    'Act Name' => $act['title'],
    'Collection' => 'SCHEDULE',
    'Ministry Name' => $act['ministry'],
    'Jurisdiction' => strtoupper((string) $act['jurisdiction']),
    'Title' => $item['heading'] !== '' ? $item['heading'] : provision_label($item),
], fn(string $v): bool => $v !== '');
?>
<div class="item-panels">
  <section class="item-panel" aria-label="Document">
    <h3><svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><rect x="2" y="2" width="12" height="12" rx="2" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="M2.5 11.5l3.5-3.5 3 3 2-2 2.5 2.5" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg> Document</h3>
<?php foreach ($item['files'] ?? [] as $file): ?>
<?php
    if (empty($file['sha256'])) {
        continue; // not archived yet: no link (no links to India Code, owner 2026-11-03)
    }
    $url = Url::file($file);
?>
    <div class="item-file">
      <a class="item-thumb" href="<?= e($url) ?>" target="_blank" rel="noopener" tabindex="-1" aria-hidden="true"><img src="<?= e($thumb) ?>" width="85" height="110" alt=""></a>
      <div>
        <p class="item-name"<?= $file['language'] === 'hi' ? ' lang="hi"' : '' ?>><?= e($file['filename']) ?></p>
<?php if ($file['size']): ?>
        <p class="item-size"><?= e(format_size($file['size'])) ?></p>
<?php endif; ?>
        <p class="item-btns">
          <a class="item-btn" href="<?= e($url) ?>" target="_blank" rel="noopener"><svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" fill="none" stroke="currentColor" stroke-width="1.4"/><circle cx="8" cy="8" r="2" fill="none" stroke="currentColor" stroke-width="1.4"/></svg> View</a>
          <a class="item-btn" href="<?= e(Url::file($file, true)) ?>"><svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 2v8M4.5 6.5L8 10l3.5-3.5M2.5 13.5h11" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg> Download</a>
        </p>
      </div>
    </div>
<?php endforeach; ?>
<?php if (empty($item['files'])): ?>
    <p class="empty">No PDF is published for this schedule.</p>
<?php endif; ?>
  </section>
  <section class="item-panel" aria-label="Item metadata">
    <h3><svg width="16" height="16" viewBox="0 0 16 16" aria-hidden="true"><path d="M4 1.5h5.5L13 5v9.5H4z" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M6 8h5M6 11h5" stroke="currentColor" stroke-width="1.4"/></svg> Item Metadata</h3>
    <table class="item-meta">
      <thead><tr><th scope="col">Field</th><th scope="col">Value</th></tr></thead>
      <tbody>
<?php foreach ($meta as $field => $value): ?>
        <tr><th scope="row"><?= e($field) ?></th><td><?= e($value) ?></td></tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
