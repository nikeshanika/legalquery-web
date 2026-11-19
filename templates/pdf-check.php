<?php
/**
 * The short sum asked before a PDF opens (FileController, PdfGate). Posts back to the PDF's own URL.
 *
 * @var array{a: int, b: int, token: string} $question
 * @var string $name PDF file name
 * @var string $action the PDF URL
 * @var bool $wrong the last answer was wrong
 */
?>
<section class="card pdf-check">
  <p class="kicker">PDF</p>
  <h1><?= e($name) ?></h1>
  <form method="post" action="<?= e($action) ?>">
    <p>Before the PDF opens, answer this short question:</p>
<?php if ($wrong): ?>
    <p class="pdf-check-wrong" role="alert">That answer was not right, or the question expired. Please try this one.</p>
<?php endif; ?>
    <label for="pdf-answer">What is <?= (int) $question['a'] ?> + <?= (int) $question['b'] ?> ?</label>
    <input type="hidden" name="token" value="<?= e($question['token']) ?>">
    <div class="pdf-check-row">
      <input class="input" id="pdf-answer" name="answer" inputmode="numeric" pattern="[0-9]{1,2}" maxlength="2"
             required autocomplete="off" autofocus aria-label="Your answer">
      <button class="btn btn-primary" type="submit">Open PDF</button>
    </div>
  </form>
</section>
