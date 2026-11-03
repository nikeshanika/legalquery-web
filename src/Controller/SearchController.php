<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\Pager;
use LegalQueryWeb\Search;
use LegalQueryWeb\Url;

/**
 * /search?q=...: Acts first, then sections and schedules, in one paged list.
 */
final class SearchController extends Controller
{
    public function index(string $page = ''): void
    {
        $q = $this->queryString('q');
        $link = fn(int $n): string => Url::search($q, $n);
        $number = Pager::number($page, $link);

        $results = [];
        if ($q !== '') {
            $acts = $this->content->acts();
            foreach (Search::acts($acts, $q) as $act) {
                $results[] = ['type' => 'act', 'act' => $act];
            }
            $bySlug = array_column($acts, null, 'slug');
            foreach (Search::provisions($this->content->searchRows(), $bySlug, $q) as $hit) {
                $results[] = ['type' => 'provision'] + $hit;
            }
        }
        $pager = Pager::make($number, count($results), $this->perPage(), $link);
        $this->view->page('search', [
            'q' => $q,
            'results' => array_slice($results, $pager['offset'], $pager['per_page']),
            'pager' => $pager,
        ], [
            'title' => $this->withPage($q !== '' ? "Search: $q" : 'Search', $number),
            'robots' => 'noindex, follow',
            'canonical' => null,
            'nav' => 'search',
        ]);
    }
}
