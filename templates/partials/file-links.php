<?php
/**
 * View / Download buttons for a document's PDF. One PDF, English only for now and no language on the
 * button (owner, 2026-11-04): see english_file(). A file that is not archived yet gets no button: no
 * links to India Code (owner, 2026-11-03).
 *
 * @var list<array{filename: string, language: string, size: ?int, sha256?: string}> $files
 * @var bool $compact Small buttons (tables).
 */
$compact ??= false;
if (!$files) {
    echo '<span class="note">No PDF</span>';
    return;
}
$file = english_file($files);
if (!$file) {
    return;
}
?>
<span class="views">
  <a class="view" href="<?= e(LegalQueryWeb\Url::file($file)) ?>" target="_blank" rel="noopener" title="<?= e($file['filename'] . ' · ' . format_size($file['size'])) ?>">View</a>
<?php if (!$compact): ?>
  <a class="view" href="<?= e(LegalQueryWeb\Url::file($file, true)) ?>" title="Download <?= e($file['filename']) ?>">⤓</a>
<?php endif; ?>
</span>
