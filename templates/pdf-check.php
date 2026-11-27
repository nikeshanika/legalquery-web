<?php
/**
 * The download page of a PDF (FileController, PdfGate; owner, 2026-11-24): every GET of a PDF URL
 * without a pass lands here (typed, bookmarked, a search engine, an outside link). It says what the PDF
 * is, asks the short sum and POSTs the answer to the PDF URL: Open (inline, in a new tab) or Download.
 * A token works once, so after either button site.js swaps in a new question (/pdf-question): the page
 * never offers a used one (owner, 2026-11-24). site.js also checks the sum before sending, so a wrong or
 * expired answer shows its message here instead of in a new tab (the server checks again). Without
 * JavaScript a wrong answer or a used token gets this page again, with the message.
 *
 * @var array{a: int, b: int, token: string} $question
 * @var string $name PDF file name
 * @var string $open the PDF URL
 * @var string $download the PDF URL with ?download=1
 * @var bool $wantsDownload the URL asked for a download
 * @var bool $wrong the last answer was wrong
 * @var array{title: string, rows: array<string, string>, back: string, others: list<array{label: string, lang: string, href: string}>, notice: string} $info
 *      `lang`, `ai`: an Act PDF's language; with the `others` links (data-lang) site.js limits the header language
 *      menu to the languages this Act has a PDF in and opens the picked one's download page (owner, 2026-11-25);
 *      for a linked document (rule, notification …) its own language and its other-language PDFs (owner, 2026-11-26)
 *      `notice`: for an AI translation, the "not official" notice above the question (owner, 2026-11-25)
 *
 * The PDF thumbnail is shown here (owner, 2026-11-25), not on the Act page. It is a demo image for now; swap it
 * for real first-page thumbnails later.
 */

use LegalQueryWeb\Url;

$langOf = static fn(string $lang): string => in_array($lang, ['en', 'bi', 'unknown'], true) ? '' : ' lang="' . e($lang) . '"';
?>
<section class="card pdf-check"<?= ($info['lang'] ?? '') !== '' ? ' data-pdf-lang="' . e($info['lang']) . '"' . (!empty($info['ai']) ? ' data-pdf-ai' : '') : '' ?>>
  <div class="pdf-check-head">
    <img class="pdf-check-thumb" src="<?= e($view->asset('/assets/img/pdf-thumb-demo.svg?v=2')) ?>" width="85" height="110" alt="">
    <div>
      <p class="kicker">Download PDF</p>
      <h1><?= e($info['title']) ?></h1>
      <p class="note"><?= e($name) ?></p>
    </div>
  </div>
<?php if ($info['rows']): ?>
  <table class="pdf-check-info">
<?php foreach ($info['rows'] as $label => $value): ?>
    <tr><th scope="row"><?= e($label) ?></th><td><?= e($value) ?></td></tr>
<?php endforeach; ?>
  </table>
<?php endif; ?>
<?php if (($info['notice'] ?? '') !== ''): ?>
  <p class="pdf-check-ai" role="note"><?= e($info['notice']) ?></p>
<?php endif; ?>
  <p class="pdf-check-note">Rely only on the official record.</p>
  <form class="pdf-check-form" method="post" action="<?= e($wantsDownload ? $download : $open) ?>">
    <p>Before the PDF opens, answer this short question:</p>
    <p class="pdf-check-wrong" role="alert" data-wrong<?= $wrong ? '' : ' hidden' ?>>That answer was not right. Please try the new question.</p>
    <label for="pdf-answer">What is <span data-q data-a="<?= (int) $question['a'] ?>" data-b="<?= (int) $question['b'] ?>"><?= (int) $question['a'] ?> + <?= (int) $question['b'] ?></span> ?</label>
    <input type="hidden" name="token" value="<?= e($question['token']) ?>">
    <div class="pdf-check-row">
      <input class="input" id="pdf-answer" name="answer" inputmode="numeric" pattern="[0-9]{1,2}" maxlength="2"
             required autocomplete="off" autofocus aria-label="Your answer">
      <button class="btn btn-primary" type="submit" formaction="<?= e($open) ?>" formtarget="_blank">Open PDF</button>
      <button class="btn" type="submit" formaction="<?= e($download) ?>">Download</button>
    </div>
  </form>
<?php if ($info['others']): ?>
  <p class="pdf-check-others"><?= e($info['others_title'] ?? 'Other PDFs of this Act') ?>:
<?php foreach ($info['others'] as $i => $other): ?>
    <?= $i ? '·' : '' ?> <a href="<?= e(Url::pdf($other)) ?>" rel="nofollow" data-lang="<?= e($other['lang']) ?>"<?= !empty($other['ai']) ? ' data-ai' : '' ?><?= $langOf($other['lang']) ?>><?= e($other['label']) ?></a>
<?php endforeach; ?>
  </p>
<?php endif; ?>
  <p><a href="<?= e($info['back']) ?>">← Back to the page</a></p>
</section>
