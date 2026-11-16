<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Pager;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Search;
use LegalQueryWeb\Url;

/**
 * /acts/{jurisdiction}[/{year}][/page/N][?search=...]: paged list of Acts (/acts/central ...).
 */
final class ActListController extends Controller
{
    public function index(string $jurisdiction, string $year = '', string $page = ''): void
    {
        $search = $this->queryString('search');
        // The year dropdown submits ?year=; send it to that year's own URL.
        if (array_key_exists('year', $this->query)) {
            $chosen = $this->queryString('year', 4);
            $target = Url::actList($jurisdiction, preg_match('/^\d{4}$/', $chosen) ? $chosen : '');
            throw new Redirect(Url::paged($target, 1, ['search' => $search]), 302);
        }
        $base = Url::actList($jurisdiction, $year);
        $link = fn(int $n): string => Url::paged($base, $n, ['search' => $search]);
        $number = Pager::number($page, $link);

        $acts = $this->content->acts($jurisdiction);
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

        $name = Jurisdiction::name($jurisdiction);
        $title = $year === '' ? $name : "$name of $year";
        $crumbs = [['Home', Url::home()]];
        if ($year !== '') {
            $crumbs[] = [$name, Url::actList($jurisdiction)];
        }
        $this->view->page('act-list', [
            'heading' => $title,
            'crumbs' => $crumbs,
            'acts' => array_slice($acts, $pager['offset'], $pager['per_page']),
            'pager' => $pager,
            'search' => $search,
            'jurisdiction' => $jurisdiction,
            'year' => $year,
            'years' => $this->content->years($jurisdiction),
            'total_all' => count($this->content->acts($jurisdiction)),
        ], [
            'title' => $this->withPage($title, $number),
            'description' => $this->describePage($year === ''
                ? "All $name of India with full text, sections and official PDFs."
                : "$name of India enacted in $year, with full text, sections and official PDFs.", $number),
            'canonical' => Url::paged($base, $number),
            // Filtered results are thin, near-duplicate pages: keep them out of the index.
            'robots' => $search !== '' ? 'noindex, follow' : '',
            'prev' => $pager['prev'],
            'next' => $pager['next'],
        ]);
    }
}
