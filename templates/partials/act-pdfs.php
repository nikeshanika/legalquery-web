<?php
/**
 * The Act's PDF as a thumbnail with its file name (like India Code). One PDF: the thumbnail opens it.
 * More than one language: clicking the thumbnail opens a small popup beside it with one button per
 * language (English, हिन्दी, English + हिन्दी …); each opens that PDF (owner, 2026-11-18): see
 * pdf_choices() and partials/pdf-menu.php. No QR code. Not archived yet → not shown (no links to
 * India Code, owner 2026-11-03).
 *
 * The name under the thumbnail is cut at 12 characters + "…" (like India Code; owner 2026-11-19); the full
 * name is in the tooltip.
 *
 * The thumbnail is a demo image for now; swap $thumb for real first-page thumbnails later.
 *
 * @var LegalQueryWeb\View $view
 * The PDFs open at their readable URLs (/acts/central/{act}.pdf, …-hindi.pdf; ActPdf).
 *
 * @var array<string, mixed> $act act.json
 */
use LegalQueryWeb\ActPdf;
use LegalQueryWeb\Url;

$thumb = $view->asset('/assets/img/pdf-thumb-demo.svg?v=2');
$choices = ActPdf::choices($act);
if (!$choices) {
    return;
}
$first = $choices[0];
// With the PDF question on, one PDF also opens the popup (it holds the question).
$popup = count($choices) > 1 || !empty($view->config['pdf_captcha']);
$title = static fn(array $f): string => $f['filename'] . (!empty($f['size']) ? ' · ' . format_size($f['size']) : '');
?>
<div class="act-pdfs">
<?php if (!$popup): ?>
  <figure class="pdf-thumb">
    <a href="<?= e(Url::pdf($first)) ?>" target="_blank" rel="noopener" title="<?= e($title($first)) ?>">
      <img src="<?= e($thumb) ?>" width="85" height="110" alt="">
      <figcaption><?= e(short_text($first['filename'])) ?></figcaption>
    </a>
  </figure>
<?php else: ?>
  <details class="pdf-pick pdf-thumb">
    <summary title="<?= e(count($choices) > 1 ? $first['filename'] . ' · available in ' . count($choices) . ' versions: choose a language' : $title($first)) ?>">
      <img src="<?= e($thumb) ?>" width="85" height="110" alt="">
      <span class="pdf-thumb-name"><?= e(short_text($first['filename'])) ?></span>
    </summary>
    <?= $view->partial('pdf-menu', ['choices' => $choices, 'download' => false]) ?>
  </details>
<?php endif; ?>
</div>
