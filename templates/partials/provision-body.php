<?php
/**
 * Text of a section or schedule, footnotes, linked documents. body_html / footnote_html were sanitised by
 * the scraper's export. Schedules have only body_text (extracted from the PDF) and no PDF of their own: the
 * Act's PDF contains them (owner, 2026-11-03).
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var array<string, mixed> $item
 */
$linked = $item['linked'] ?? [];
$noun = ($item['kind'] ?? 'SECTION') === 'SCHEDULE' ? 'schedule' : 'section';
?>
<?php if (($item['body_html'] ?? '') !== ''): ?>
<div class="law-text"><?= $item['body_html'] ?></div>
<?php elseif (($item['body_text'] ?? '') !== ''): ?>
<pre class="schedule-text"><?= e(tidy_text($item['body_text'])) ?></pre>
<?php else: ?>
<p class="empty">No text is published for this <?= $noun ?>.</p>
<?php endif; ?>
<?php if (($item['footnote_html'] ?? '') !== ''): ?>
<div class="footnotes"><?= $item['footnote_html'] ?></div>
<?php endif; ?>
<?php if ($linked): ?>
<div class="related">
  <h4>Rules, notifications and other documents under this <?= $noun ?> <span class="n"><?= count($linked) ?></span></h4>
  <ul class="doclist">
<?php foreach ($linked as $doc): ?>
    <li>
      <span class="d"><?= e(format_date($doc['date'])) ?></span>
      <span class="t"><span class="type t-<?= e($doc['collection']) ?>"><?= e(collection_label($doc['collection'])) ?></span> <?= e($doc['title']) ?></span>
      <?= $view->partial('file-links', ['files' => $doc['files'], 'compact' => true]) ?>
    </li>
<?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
