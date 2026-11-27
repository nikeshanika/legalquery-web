<?php
/**
 * Home page in the plain reference-site look (design "W", owner 2026-11-25): a welcome box with the counts,
 * search, then plain boxes (recent Acts, years, categories, and the States with the most Acts; while no State is
 * exported, what an Act page has instead). No outside reference links
 * (owner 2026-11-03).
 *
 * @var LegalQueryWeb\View $view
 * @var array{acts: int, state_acts: int, sections: int, schedules: int} $counts (sections, schedules: Central)
 * @var list<array<string, mixed>> $latest
 * @var array<string, int> $years
 * @var int $year_count
 * @var array<string, int> $categories linked-document tab => number of Acts
 * @var string $updated
 * @var list<array{code: string, name: string, acts: int}> $states the States / UTs with the most Acts (at most 10)
 * @var int $state_count every exported State / UT
 */
use LegalQueryWeb\Controller\ActController;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Url;

$several = count(Jurisdiction::codes()) > 1;
?>
<div class="home-welcome">
  <div>
    <h1>Welcome to <?= e($view->config['site_name']) ?></h1>
    <p>Central Acts and Acts of States and Union Territories of India, with sections, schedules, rules, notifications and official PDFs in English and <span lang="hi">हिन्दी</span>.</p>
  </div>
  <p class="home-counts">
    <a href="<?= e(Url::actList(Jurisdiction::DEFAULT)) ?>"><b><?= number_format($counts['acts']) ?></b> Central Acts</a><?php if ($several): ?> · <a href="<?= e(Url::states()) ?>"><b><?= number_format($counts['state_acts']) ?></b> State Acts</a><?php endif; ?><br>
    <?= number_format($counts['sections']) ?> sections · <?= number_format($counts['schedules']) ?> schedules · <?= number_format($year_count) ?> years
  </p>
</div>

<div class="home-search">
  <form class="hero-search" action="/search" method="get" role="search">
    <input class="input" type="search" name="q" placeholder="Enter an Act title, number or section, e.g. arbitration, 37 of 2020" aria-label="Search Acts and sections" autofocus>
    <button class="btn btn-primary" type="submit">Search</button>
  </form>
  <p class="note">Type words from an Act's title or a section heading, an Act number such as <em>37 of 2020</em>, or a year.</p>
</div>

<div class="home-grid">
  <section class="box">
    <h2>Recent Central Acts</h2>
    <div class="box-body">
      <ul class="recent-list">
<?php foreach ($latest as $act): ?>
        <li>
          <a href="<?= e(Url::act($act['jurisdiction'], $act['slug'])) ?>"><?= e($act['title']) ?></a>
<?php if ($act['act_number'] !== ''): ?>
          <span class="note">(Act <?= e($act['act_number']) ?> of <?= e($act['year']) ?>)</span>
<?php endif; ?>
        </li>
<?php endforeach; ?>
      </ul>
      <p><a class="box-more" href="<?= e(Url::actList(Jurisdiction::DEFAULT)) ?>">All <?= number_format($counts['acts']) ?> Central Acts →</a></p>
    </div>
  </section>
  <section class="box">
    <h2>Browse Central Acts by year</h2>
    <div class="box-body">
      <p class="year-chips">
<?php foreach ($years as $year => $n): ?>
        <a class="fchip" href="<?= e(Url::actList(Jurisdiction::DEFAULT, (string) $year)) ?>"><?= e($year) ?><span class="n">(<?= $n ?>)</span></a>
<?php endforeach; ?>
      </p>
<?php if ($updated !== ''): ?>
      <p class="note">Archive updated <?= e(format_date($updated)) ?>.</p>
<?php endif; ?>
      <p><a class="box-more" href="<?= e(Url::actList(Jurisdiction::DEFAULT)) ?>">Every year →</a></p>
    </div>
  </section>
  <section class="box">
    <h2>Browse by category</h2>
    <div class="box-body">
      <p class="inline-links">
        <a href="<?= e(Url::actList(Jurisdiction::DEFAULT)) ?>">Acts <span class="n">(<?= number_format($counts['acts']) ?>)</span></a>
<?php foreach ($categories as $tab => $n): ?>
        <a href="<?= e(Url::category($tab)) ?>"><?= e(collection_label(ActController::TABS[$tab], true)) ?> <span class="n">(under <?= number_format($n) ?> <?= $n === 1 ? 'Act' : 'Acts' ?>)</span></a>
<?php endforeach; ?>
      </p>
    </div>
  </section>
<?php if ($states): ?>
  <section class="box">
    <h2>Browse by State</h2>
    <div class="box-body">
      <p class="inline-links">
<?php foreach ($states as $state): ?>
        <a href="<?= e(Url::actList($state['code'])) ?>"><?= e($state['name']) ?> <span class="n">(<?= number_format($state['acts']) ?> <?= $state['acts'] === 1 ? 'Act' : 'Acts' ?>)</span></a>
<?php endforeach; ?>
      </p>
      <p><a class="box-more" href="<?= e(Url::states()) ?>">All <?= number_format($state_count) ?> States and Union Territories →</a></p>
    </div>
  </section>
<?php else: ?>
  <section class="box">
    <h2>What each Act page has</h2>
    <div class="box-body">
      <ul class="recent-list">
        <li><strong>Sections</strong>: the text of each section</li>
        <li><strong>Schedules</strong>: the text of each schedule</li>
        <li><strong>Rules, regulations, notifications and orders</strong> made under the Act</li>
        <li><strong>Official PDFs</strong> in English, <span lang="hi">हिन्दी</span> and State languages</li>
      </ul>
    </div>
  </section>
<?php endif; ?>
</div>
