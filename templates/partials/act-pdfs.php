<?php
/**
 * The Act's PDF as a thumbnail with its file name (like India Code). One PDF, English only for now,
 * with no language buttons (owner, 2026-11-04): see english_file(). The English/हिन्दी switch is in
 * git history (bca7d3a) for when Hindi comes back. No QR code. Not archived yet → not shown (no links
 * to India Code, owner 2026-11-03).
 *
 * The thumbnail is a demo image for now; swap $thumb for real first-page thumbnails later.
 *
 * @var list<array{filename: string, language: string, size: ?int, sha256?: string}> $files
 */
use LegalQueryWeb\Url;

$thumb = '/assets/img/pdf-thumb-demo.svg';
$file = english_file($files);
if (!$file) {
    return;
}
$url = Url::file($file);
?>
<div class="act-pdfs">
  <figure class="pdf-thumb">
    <a href="<?= e($url) ?>" target="_blank" rel="noopener" title="<?= e($file['filename'] . ($file['size'] ? ' · ' . format_size($file['size']) : '')) ?>">
      <img src="<?= e($thumb) ?>" width="85" height="110" alt="">
      <figcaption><?= e($file['filename']) ?></figcaption>
    </a>
  </figure>
</div>
