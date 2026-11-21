<?php
/**
 * The popup for a document's PDFs, used inside <details class="pdf-pick"> (site.js places it beside
 * the clicked PDF and closes it). One button per language (English, हिन्दी, English + हिन्दी …,
 * owner 2026-11-18), or one "Open PDF" button.
 *
 * With `pdf_captcha` on, the popup also asks a short sum every time (owner, 2026-11-19): site.js
 * fetches it from /pdf-question when the popup opens, and a button POSTs the answer to that PDF's URL
 * (FileController), which sends the PDF in a new tab. Without JavaScript the question stays hidden
 * and the PDF URL asks it as a page instead.
 *
 * @var LegalQueryWeb\View $view
 * @var list<array<string, mixed>> $choices pdf_choices()
 * @var bool $download Also a download button per PDF.
 */
use LegalQueryWeb\Url;

$captcha = !empty($view->config['pdf_captcha']);
$several = count($choices) > 1;
$tip = static fn(array $f): string => (!empty($f['ai']) ? 'AI translation, not official · ' : '') . $f['filename'] . (!empty($f['size']) ? ' · ' . format_size($f['size']) : '');
// AI translations (demo, owner 2026-11-21) are not the official text: say so under the buttons.
$aiNote = in_array(true, array_map(static fn(array $f): bool => !empty($f['ai']), $choices), true)
    ? '<p class="pdf-menu-note">AI translations are not official and not yet reviewed. Only the official PDF is the authoritative text.</p>'
    : '';
?>
<?php if ($captcha): ?>
<form class="pdf-menu" method="post" target="_blank" aria-label="Open the PDF">
  <p class="pdf-menu-title"><?= $several ? 'Choose language' : 'Open PDF' ?></p>
  <div class="pdf-q" hidden>
    <label>What is <span data-q>?</span> ?
      <input class="input" name="answer" inputmode="numeric" pattern="[0-9]{1,2}" maxlength="2" autocomplete="off" aria-label="Your answer">
    </label>
    <input type="hidden" name="token" value="">
  </div>
<?php foreach ($choices as $file): ?>
  <div class="pdf-menu-row">
    <button class="view" type="submit" formaction="<?= e(Url::pdf($file)) ?>" title="<?= e($tip($file)) ?>"<?= in_array($file['lang'], ['hi', 'bn'], true) ? ' lang="' . e($file['lang']) . '"' : '' ?>><?= e($several ? $file['label'] : 'Open PDF') ?></button>
<?php if ($download): ?>
    <button class="view" type="submit" formaction="<?= e(Url::pdf($file, true)) ?>" formtarget="_self" title="Download <?= e($file['filename']) ?>" aria-label="Download <?= e($file['label']) ?> PDF">⤓</button>
<?php endif; ?>
  </div>
<?php endforeach; ?>
<?= $aiNote ?>
</form>
<?php else: ?>
<div class="pdf-menu" role="group" aria-label="Choose the PDF language">
  <p class="pdf-menu-title">Choose language</p>
<?php foreach ($choices as $file): ?>
  <div class="pdf-menu-row">
    <a class="view" href="<?= e(Url::pdf($file)) ?>" target="_blank" rel="noopener" title="<?= e($tip($file)) ?>"<?= in_array($file['lang'], ['hi', 'bn'], true) ? ' lang="' . e($file['lang']) . '"' : '' ?>><?= e($file['label']) ?></a>
<?php if ($download): ?>
    <a class="view" href="<?= e(Url::pdf($file, true)) ?>" title="Download <?= e($file['filename']) ?>" aria-label="Download <?= e($file['label']) ?> PDF">⤓</a>
<?php endif; ?>
  </div>
<?php endforeach; ?>
<?= $aiNote ?>
</div>
<?php endif; ?>
