<?php
/**
 * The Act's PDF as a thumbnail with its file name (like India Code). One PDF: the thumbnail opens it.
 * More than one language: clicking the thumbnail opens a small popup beside it with one button per
 * language (English, हिन्दी, English + हिन्दी …); each opens that PDF (owner, 2026-11-18): see
 * pdf_choices() and partials/pdf-menu.php. No QR code. Not archived yet → not shown (no links to
 * India Code, owner 2026-11-03).
 *
 * The thumbnail is a demo image for now; swap $thumb for real first-page thumbnails later.
 *
 * @var LegalQueryWeb\View $view
 * @var list<array{filename: string, language: string, size: ?int, sha256?: string}> $files
 */
use LegalQueryWeb\Url;

$thumb = $view->asset('/assets/img/pdf-thumb-demo.svg?v=2');
$choices = pdf_choices($files);
if (!$choices) {
    return;
}
$first = $choices[0];
$title = static fn(array $f): string => $f['filename'] . (!empty($f['size']) ? ' · ' . format_size($f['size']) : '');
?>
<div class="act-pdfs">
<?php if (count($choices) === 1): ?>
  <figure class="pdf-thumb">
    <a href="<?= e(Url::file($first)) ?>" target="_blank" rel="noopener" title="<?= e($title($first)) ?>">
      <img src="<?= e($thumb) ?>" width="85" height="110" alt="">
      <figcaption><?= e($first['filename']) ?></figcaption>
    </a>
  </figure>
<?php else: ?>
  <details class="pdf-pick pdf-thumb">
    <summary title="PDF available in <?= count($choices) ?> versions: choose a language">
      <img src="<?= e($thumb) ?>" width="85" height="110" alt="">
      <span class="pdf-thumb-name"><?= e($first['filename']) ?></span>
    </summary>
    <?= $view->partial('pdf-menu', ['choices' => $choices, 'download' => false]) ?>
  </details>
<?php endif; ?>
</div>
