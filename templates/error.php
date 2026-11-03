<?php
/**
 * Error page (404, 500, 503 ...).
 *
 * @var int $status
 * @var string $heading
 * @var string $message
 */
use LegalQueryWeb\Url;

?>
<section class="card error-page">
  <p class="kicker">Error <?= (int) $status ?></p>
  <h1><?= e($heading) ?></h1>
<?php if ($message !== ''): ?>
  <p><?= e($message) ?></p>
<?php elseif ($status === 404): ?>
  <p>The page you asked for is not here. It may have moved, or the address may be mistyped.</p>
<?php endif; ?>
  <form class="hero-search" action="/search" method="get" role="search">
    <input class="input" type="search" name="q" placeholder="Search Acts and sections" aria-label="Search">
    <button class="btn btn-primary" type="submit">Search</button>
  </form>
  <p><a href="<?= e(Url::home()) ?>">Go to the home page</a> · <a href="<?= e(Url::actList()) ?>">All Central Acts</a></p>
</section>
