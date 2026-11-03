<?php
/**
 * Pager with crawlable links (page 1 = plain URL, then /page/N).
 *
 * @var array{pages: list<array{number: int, url: string, current: bool}|null>, prev: ?string, next: ?string, page: int} $pager
 * @var bool $always Also show a one-page pager (toolbar above a list, like India Code).
 */
$always ??= false;
if (!$pager['pages'] && !$always) {
    return;
}
$pages = $pager['pages'] ?: [['number' => $pager['page'], 'url' => '', 'current' => true]];
?>
<nav class="pager<?= $always ? ' pager-top' : '' ?>" aria-label="Pages">
<?php if ($pager['prev']): ?>
  <a href="<?= e($pager['prev']) ?>" rel="prev">‹ Previous</a>
<?php else: ?>
  <span class="disabled">‹ Previous</span>
<?php endif; ?>
<?php foreach ($pages as $item): ?>
<?php if ($item === null): ?>
  <span class="gap" aria-hidden="true">…</span>
<?php elseif ($item['current']): ?>
  <span class="current" aria-current="page"><?= $item['number'] ?></span>
<?php else: ?>
  <a href="<?= e($item['url']) ?>"><?= $item['number'] ?></a>
<?php endif; ?>
<?php endforeach; ?>
<?php if ($pager['next']): ?>
  <a href="<?= e($pager['next']) ?>" rel="next">Next ›</a>
<?php else: ?>
  <span class="disabled">Next ›</span>
<?php endif; ?>
</nav>
