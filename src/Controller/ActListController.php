<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Pager;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Search;
use LegalQueryWeb\Url;

/**
 * /acts/central[/{year}][/page/N][?search=...]: paged list of Acts.
 */
final class ActListController extends Controller
{
    public function index(string $year = '', string $page = ''): void
    {
        $search = $this->queryString('search');
        // The year dropdown submits ?year=; send it to that year's own URL.
        if (array_key_exists('year', $this->query)) {
            $chosen = $this->queryString('year', 4);
            $target = preg_match('/^\d{4}$/', $chosen) ? Url::actList($chosen) : Url::actList();
            throw new Redirect(Url::paged($target, 1, ['search' => $search]), 302);
        }
        $base = Url::actList($year);
        $link = fn(int $n): string => Url::paged($base, $n, ['search' => $search]);
        $number = Pager::number($page, $link);

        $acts = $this->content->acts();
        if ($year !== '') {
            $acts = array_values(array_filter($acts, fn(array $a): bool => $a['year'] === $year));
            if (!$acts) {
                throw new HttpError(404);
            }
        }
        if ($search !== '') {
            $acts = Search::acts($acts, $search);
        }
        $pager = Pager::make($number, count($acts), $this->perPage(), $link);

        $title = $year === '' ? 'Central Acts' : "Central Acts of $year";
        $crumbs = [['Home', Url::home()]];
        if ($year !== '') {
            $crumbs[] = ['Central Acts', Url::actList()];
        }
        $this->view->page('act-list', [
            'heading' => $title,
            'crumbs' => $crumbs,
            'acts' => array_slice($acts, $pager['offset'], $pager['per_page']),
            'pager' => $pager,
            'search' => $search,
            'year' => $year,
            'years' => $this->content->years(),
            'total_all' => count($this->content->acts()),
        ], [
            'title' => $this->withPage($title, $number),
            'description' => $this->describePage($year === ''
                ? 'All Central Acts of India with full text, sections and official PDFs.'
                : "Central Acts of India enacted in $year, with full text, sections and official PDFs.", $number),
            'canonical' => Url::paged($base, $number),
            // Filtered results are thin, near-duplicate pages: keep them out of the index.
            'robots' => $search !== '' ? 'noindex, follow' : '',
            'prev' => $pager['prev'],
            'next' => $pager['next'],
        ]);
    }
}
