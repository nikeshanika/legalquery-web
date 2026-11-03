<?php
/**
 * Home page, laid out like India Code's home (search band, counts, recent Acts, categories,
 * references)
 * in LegalQuery colours.
 *
 * @var LegalQueryWeb\View $view
 * @var array{acts: int, sections: int, schedules: int} $counts
 * @var list<array<string, mixed>> $latest
 * @var array<string, int> $years
 * @var int $year_count
 * @var array<string, int> $categories linked-document tab => number of Acts
 * @var string $updated
 */
use LegalQueryWeb\Controller\ActController;
use LegalQueryWeb\Url;

$stats = [
    ['Central Acts', $counts['acts'], 'blue', ['All Acts', Url::actList()]],
    ['Sections', $counts['sections'], 'gold', ['Search sections', Url::search()]],
    ['Schedules', $counts['schedules'], 'green', null],
    ['Years', $year_count, 'violet', null],
];
$references = [
    'Ministry links' => [
        ['Ministry of Law and Justice', 'https://lawmin.gov.in/'],
        ['Legislative Department', 'https://legislative.gov.in/'],
        ['Department of Legal Affairs', 'https://legalaffairs.gov.in/'],
    ],
    'Related portals' => [
        ['India.gov.in', 'https://www.india.gov.in/'],
        ['e-Gazette of India', 'https://egazette.gov.in/'],
    ],
    'Courts' => [
        ['Supreme Court of India', 'https://www.sci.gov.in/'],
        ['eCourts Services', 'https://ecourts.gov.in/'],
    ],
];
?>
<section class="home-band">
  <div class="container">
    <h1>Search Central Acts</h1>
    <p>Central Acts of India with sections, schedules, rules, notifications and official PDFs in English and हिन्दी.</p>
    <div class="home-search card">
      <form class="hero-search" action="/search" method="get" role="search">
        <input class="input" type="search" name="q" placeholder="Enter an Act title, number or section, e.g. arbitration, 37 of 2020" aria-label="Search Acts and sections" autofocus>
        <button class="btn btn-gold" type="submit">Search</button>
      </form>
      <p class="note"><strong>Search hint:</strong> type words from an Act's title or a section heading, an Act number such as <em>37 of 2020</em>, or a year.</p>
    </div>
  </div>
</section>

<div class="stat-cards">
<?php foreach ($stats as [$label, $n, $tone, $more]): ?>
  <div class="card stat-card <?= $tone ?>">
    <strong><?= number_format($n) ?></strong>
    <span><?= e($label) ?></span>
<?php if ($more): ?>
    <a href="<?= e($more[1]) ?>"><?= e($more[0]) ?> →</a>
<?php endif; ?>
  </div>
<?php endforeach; ?>
</div>

<div class="home-grid">
  <section class="card panel-card">
    <h2 class="panel-head blue">Recent Central Acts</h2>
    <ul class="recent-list">
<?php foreach ($latest as $act): ?>
      <li>
        <a href="<?= e(Url::act($act['slug'])) ?>"><?= e($act['title']) ?></a>
        <span class="note"><?= e(implode(' · ', array_filter([
            $act['act_number'] !== '' ? 'Act ' . $act['act_number'] . ' of ' . $act['year'] : '',
            format_date($act['enactment_date']),
        ]))) ?></span>
      </li>
<?php endforeach; ?>
    </ul>
    <a class="panel-more" href="<?= e(Url::actList()) ?>">View more →</a>
  </section>
  <section class="card panel-card">
    <h2 class="panel-head gold">Browse by year</h2>
    <div class="year-chips">
<?php foreach ($years as $year => $n): ?>
      <a class="fchip" href="<?= e(Url::actList((string) $year)) ?>"><?= e($year) ?><span class="n"><?= $n ?></span></a>
<?php endforeach; ?>
    </div>
<?php if ($updated !== ''): ?>
    <p class="note pad-x">Archive updated <?= e(format_date($updated)) ?>.</p>
<?php endif; ?>
    <a class="panel-more" href="<?= e(Url::actList()) ?>">Every year →</a>
  </section>
</div>

<section class="categories">
  <h2>Browse by category</h2>
  <div class="cat-grid">
    <a class="card cat-card" href="<?= e(Url::actList()) ?>"><strong>Acts</strong><span><?= number_format($counts['acts']) ?> Central Acts</span></a>
<?php foreach ($categories as $tab => $n): ?>
    <a class="card cat-card" href="<?= e(Url::category($tab)) ?>"><strong><?= e(collection_label(ActController::TABS[$tab], true)) ?></strong><span>under <?= number_format($n) ?> <?= $n === 1 ? 'Act' : 'Acts' ?></span></a>
<?php endforeach; ?>
  </div>
</section>

<section class="references">
  <h2>Important references</h2>
  <div class="ref-grid">
<?php foreach ($references as $title => $links): ?>
    <div class="card">
      <h3><?= e($title) ?></h3>
<?php foreach ($links as [$label, $url]): ?>
      <a href="<?= e($url) ?>" target="_blank" rel="noopener"><?= e($label) ?> ↗</a>
<?php endforeach; ?>
    </div>
<?php endforeach; ?>
  </div>
</section>
