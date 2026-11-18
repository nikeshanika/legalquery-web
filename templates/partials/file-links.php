<?php
/**
 * The button for a document's PDF. One PDF: "View" opens it. More than one language: "View" opens a
 * small popup beside it with one button per language (English, हिन्दी, English + हिन्दी …) that opens
 * that PDF (owner, 2026-11-18): see pdf_choices() and partials/pdf-menu.php. A file that is not
 * archived yet gets no button: no links to India Code (owner, 2026-11-03).
 *
 * @var LegalQueryWeb\View $view
 * @var list<array{filename: string, language: string, size: ?int, sha256?: string}> $files
 * @var bool $compact Small buttons (tables): no download button.
 */
$compact ??= false;
if (!$files) {
    echo '<span class="note">No PDF</span>';
    return;
}
$choices = pdf_choices($files);
if (!$choices) {
    return;
}
$file = $choices[0];
?>
<span class="views">
<?php if (count($choices) === 1): ?>
  <a class="view" href="<?= e(LegalQueryWeb\Url::file($file)) ?>" target="_blank" rel="noopener" title="<?= e($file['filename'] . ' · ' . format_size($file['size'] ?? null)) ?>">View</a>
<?php if (!$compact): ?>
  <a class="view" href="<?= e(LegalQueryWeb\Url::file($file, true)) ?>" title="Download <?= e($file['filename']) ?>">⤓</a>
<?php endif; ?>
<?php else: ?>
  <details class="pdf-pick">
    <summary class="view" title="PDF available in <?= count($choices) ?> versions: choose a language">View ▾</summary>
    <?= $view->partial('pdf-menu', ['choices' => $choices, 'download' => !$compact]) ?>
  </details>
<?php endif; ?>
</span>
