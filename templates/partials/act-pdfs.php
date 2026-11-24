<?php
/**
 * The Act's PDF as a thumbnail with its file name (like India Code). The thumbnail goes to the PDF's
 * download page (/pdf?…; the PDF question, then Open / Download, and the Act's other PDFs; owner,
 * 2026-11-24, no popup). More than one language: a link per language under it (English, हिन्दी, ગુજરાતી …;
 * ActPdf). No QR code. Not archived yet → not shown (no links to India Code, owner 2026-11-03).
 *
 * The name under the thumbnail is cut at 12 characters + "…" (like India Code; owner 2026-11-19); the full
 * name is in the tooltip.
 *
 * The thumbnail is a demo image for now; swap $thumb for real first-page thumbnails later.
 *
 * @var LegalQueryWeb\View $view
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
$title = static fn(array $f): string => $f['filename'] . (!empty($f['size']) ? ' · ' . format_size($f['size']) : '');
$langOf = static fn(array $f): string => in_array($f['lang'], ['en', 'bi', 'unknown'], true) ? '' : ' lang="' . e($f['lang']) . '"';
?>
<div class="act-pdfs">
  <figure class="pdf-thumb">
    <a href="<?= e(Url::pdf($first)) ?>" rel="nofollow" title="<?= e($title($first)) ?>">
      <img src="<?= e($thumb) ?>" width="85" height="110" alt="">
      <figcaption><?= e(short_text($first['filename'])) ?></figcaption>
    </a>
  </figure>
<?php if (count($choices) > 1): ?>
  <p class="pdf-langs">
<?php foreach ($choices as $file): ?>
    <a class="view" href="<?= e(Url::pdf($file)) ?>" rel="nofollow" title="<?= e($title($file)) ?>"<?= $langOf($file) ?>><?= e($file['label']) ?></a>
<?php endforeach; ?>
  </p>
<?php endif; ?>
</div>
