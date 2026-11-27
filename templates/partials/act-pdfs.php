<?php
/**
 * The Act's PDF at the right of the tab row: a button with a PDF icon, "PDF · English" (design "W", owner 2026-11-25;
 * button with icon, owner 2026-11-26). Every PDF of the Act
 * is in the page (ActPdf::choices: official ones first, AI translations last); site.js shows the one in the language
 * picked in the header menu (languages without a PDF of this Act are disabled there), or the first one (English)
 * when the saved language has no PDF here or without
 * JavaScript. Each link goes to the PDF's download page (/pdf?…; the PDF question, the thumbnail, Open / Download,
 * the Act's other PDFs; owner 2026-11-24). No QR code. Not archived yet → not shown (no links to India Code,
 * owner 2026-11-03).
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act act.json
 */
use LegalQueryWeb\ActPdf;
use LegalQueryWeb\Url;

$choices = ActPdf::choices($act);
if (!$choices) {
    return;
}
$first = $choices[0];
$title = static fn(array $f): string => $f['filename'] . (!empty($f['size']) ? ' · ' . format_size($f['size']) : '');
// Page with a folded corner and a red "PDF" band; aria-hidden, the link text says PDF.
$icon = '<svg class="pdf-icon" width="18" height="22" viewBox="0 0 18 22" aria-hidden="true" focusable="false">'
    . '<path d="M2.5 1.5h8.5l4.5 4.5v14.5h-13z" fill="#fff" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>'
    . '<path d="M11 1.5V6h4.5" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/>'
    . '<rect x="0.5" y="10.5" width="13" height="6.5" rx="1" fill="#b3261e"/>'
    . '<text x="7" y="15.6" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="5.2" font-weight="700" fill="#fff">PDF</text>'
    . '</svg>';
$langOf = static fn(array $f): string => in_array($f['lang'], ['en', 'bi', 'unknown'], true) ? '' : ' lang="' . e($f['lang']) . '"';
?>
<span class="act-pdf" data-act-pdf>
<?php foreach ($choices as $i => $file): ?>
  <a class="act-pdf-link" href="<?= e(Url::pdf($file)) ?>" rel="nofollow" title="<?= e($title($file)) ?>" data-lang="<?= e($file['lang']) ?>"<?= !empty($file['ai']) ? ' data-ai' : '' ?><?= $i ? ' hidden' : '' ?>><?= $icon ?><span class="act-pdf-kind">PDF</span> <span class="act-pdf-lang"<?= $langOf($file) ?>><?= e($file['label']) ?></span></a>
<?php endforeach; ?>
</span>
