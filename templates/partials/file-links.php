<?php
/**
 * The link to a document's PDF: "View" goes to its download page (/pdf?…; the PDF question, then Open /
 * Download; owner, 2026-11-24, no popup). In more than one language: one link per language (English,
 * हिन्दी, English + हिन्दी …; pdf_choices()). A file that is not archived yet gets no link: no links to
 * India Code (owner, 2026-11-03). `data-lang` (the PDF's language): on pages without an Act PDF, the header language
 * menu lists only these languages (site.js; owner, 2026-11-26).
 *
 * @var LegalQueryWeb\View $view
 * @var list<array{filename: string, language: string, size: ?int, sha256?: string}> $files
 * @var bool $compact Small buttons (tables). Same links; kept for the callers.
 */
use LegalQueryWeb\Url;

if (!$files) {
    echo '<span class="note">No PDF</span>';
    return;
}
$choices = pdf_choices($files);
if (!$choices) {
    return;
}
$several = count($choices) > 1;
$title = static fn(array $f): string => $f['filename'] . (!empty($f['size']) ? ' · ' . format_size($f['size']) : '');
// lang="hi", lang="gu" …: a link named in that language (not for English or English + हिन्दी).
$langOf = static fn(array $f): string => in_array($f['lang'], ['en', 'bi', 'unknown'], true) ? '' : ' lang="' . e($f['lang']) . '"';
?>
<span class="views">
<?php foreach ($choices as $file): ?>
  <a class="view" href="<?= e(Url::pdf($file)) ?>" rel="nofollow" title="<?= e($title($file)) ?>" data-lang="<?= e($file['lang']) ?>"<?= $several ? $langOf($file) : '' ?>><?= e($several ? $file['label'] : 'View') ?></a>
<?php endforeach; ?>
</span>
