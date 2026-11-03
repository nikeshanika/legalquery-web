<?php
/**
 * One section or schedule on its own page, with Previous / Next.
 *
 * @var LegalQueryWeb\View $view
 * @var array<string, mixed> $act
 * @var array<string, mixed> $item
 * @var string $heading
 * @var array{label: string, heading: string, url: string}|null $prev
 * @var array{label: string, heading: string, url: string}|null $next
 */
use LegalQueryWeb\Url;

?>
<?= $view->partial('crumbs', ['crumbs' => [['Home', Url::home()], ['Central Acts', Url::actList()], [$act['title'], Url::act($act['slug']) . '#' . $item['slug']]], 'current' => provision_label($item)]) ?>
<article class="card provision">
  <p class="kicker"><a href="<?= e(Url::act($act['slug'])) ?>"><?= e($act['title']) ?></a></p>
  <h1><?= e($heading) ?></h1>
  <?= $view->partial('provision-body', ['act' => $act, 'item' => $item]) ?>
</article>
<nav class="prevnext" aria-label="Previous and next">
<?php if ($prev): ?>
  <a class="card" href="<?= e($prev['url']) ?>" rel="prev"><span class="note">‹ <?= e($prev['label']) ?></span><?= e($prev['heading']) ?></a>
<?php else: ?>
  <span></span>
<?php endif; ?>
<?php if ($next): ?>
  <a class="card next" href="<?= e($next['url']) ?>" rel="next"><span class="note"><?= e($next['label']) ?> ›</span><?= e($next['heading']) ?></a>
<?php endif; ?>
</nav>
