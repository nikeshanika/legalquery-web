<?php
/**
 * The Act's PDF as a thumbnail with its file name (like India Code). One PDF per language: the
 * English one shows first; the Hindi one only when the reader picks हिन्दी (owner, 2026-11-02).
 * No QR code. Not archived yet → not shown (no links to India Code, owner 2026-11-03).
 *
 * The thumbnail is a demo image for now; swap $thumb for real first-page thumbnails later.
 *
 * @var list<array{filename: string, language: string, size: ?int, sha256?: string}> $files
 */
use LegalQueryWeb\Url;

$thumb = '/assets/img/pdf-thumb-demo.svg';

// First non-Hindi file (en, bi, unknown) and first Hindi file. Later files of the same language
// are skipped: some Hindi PDFs are labelled 'en' in the export.
$shown = [];
foreach ($files as $file) {
    $lang = $file['language'] === 'hi' ? 'hi' : 'en';
    $shown[$lang] ??= $file;
}
if (!$shown) {
    return;
}
$default = isset($shown['en']) ? 'en' : 'hi';
?>
<div class="act-pdfs" data-pdf-switch>
<?php if (count($shown) > 1): ?>
  <div class="pdf-langs" role="group" aria-label="PDF language" hidden>
    <button type="button" data-pdf-lang="en" aria-pressed="true">English</button>
    <button type="button" data-pdf-lang="hi" aria-pressed="false" lang="hi">हिन्दी</button>
  </div>
<?php endif; ?>
<?php foreach ($shown as $lang => $file): ?>
<?php
    if (empty($file['sha256'])) {
        continue;
    }
    $url = Url::file($file);
?>
  <figure class="pdf-thumb" data-lang="<?= $lang ?>"<?= $lang !== $default ? ' hidden' : '' ?>>
    <a href="<?= e($url) ?>" target="_blank" rel="noopener" title="<?= e($file['filename'] . ($file['size'] ? ' · ' . format_size($file['size']) : '')) ?>">
      <img src="<?= e($thumb) ?>" width="85" height="110" alt="">
      <figcaption><?= e($file['filename']) ?></figcaption>
    </a>
  </figure>
<?php endforeach; ?>
</div>
