<?php
/**
 * View / Download buttons for PDFs. A file that is not archived yet gets no button: no links to
 * India Code (owner, 2026-11-03).
 *
 * @var list<array{filename: string, language: string, size: ?int, sha256?: string}> $files
 * @var bool $compact Small buttons (tables).
 */
$compact ??= false;
if (!$files) {
    echo '<span class="note">No PDF</span>';
    return;
}
?>
<span class="views">
<?php foreach ($files as $file): ?>
<?php $lang = language_label($file['language']); ?>
<?php if (!empty($file['sha256'])): ?>
  <a class="view" href="<?= e(LegalQueryWeb\Url::file($file)) ?>" target="_blank" rel="noopener" title="<?= e($file['filename'] . ' · ' . format_size($file['size'])) ?>"<?= $file['language'] === 'hi' ? ' lang="hi"' : '' ?>><?= e($lang) ?></a>
<?php if (!$compact): ?>
  <a class="view" href="<?= e(LegalQueryWeb\Url::file($file, true)) ?>" title="Download <?= e($file['filename']) ?>">⤓</a>
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>
</span>
