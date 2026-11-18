<?php
/**
 * The popup listing a document's PDFs, one button per language, shown when the reader clicks a PDF
 * that exists in more than one language (owner, 2026-11-18). Used inside <details class="pdf-pick">
 * (works without JavaScript; site.js places it beside the clicked PDF and closes it).
 *
 * @var list<array<string, mixed>> $choices pdf_choices()
 * @var bool $download Also a download button per PDF.
 */
use LegalQueryWeb\Url;

?>
<div class="pdf-menu" role="group" aria-label="Choose the PDF language">
  <p class="pdf-menu-title">Choose language</p>
<?php foreach ($choices as $file): ?>
  <div class="pdf-menu-row">
    <a class="view" href="<?= e(Url::file($file)) ?>" target="_blank" rel="noopener" title="<?= e($file['filename'] . (!empty($file['size']) ? ' · ' . format_size($file['size']) : '')) ?>"<?= $file['lang'] === 'hi' ? ' lang="hi"' : '' ?>><?= e($file['label']) ?></a>
<?php if ($download): ?>
    <a class="view" href="<?= e(Url::file($file, true)) ?>" title="Download <?= e($file['filename']) ?>" aria-label="Download <?= e($file['label']) ?> PDF">⤓</a>
<?php endif; ?>
  </div>
<?php endforeach; ?>
</div>
