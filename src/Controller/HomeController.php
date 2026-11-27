<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Seo;
use LegalQueryWeb\Url;

/**
 * Home page (laid out like India Code's home): search band, counts, latest Acts, browse by year,
 * browse by category, browse by State. The lists are Central Acts (Jurisdiction::DEFAULT); the search and the State Acts
 * card cover the States and UTs too.
 */
final class HomeController extends Controller
{
    public function index(): void
    {
        $acts = $this->content->acts(Jurisdiction::DEFAULT);
        $manifest = $this->content->manifest();
        $counts = $this->content->counts(Jurisdiction::DEFAULT);
        $states = $this->states();
        $this->view->page('home', [
            'counts' => [
                'acts' => count($acts),
                // Every exported State / UT (manifest.json), for the State Acts card.
                'state_acts' => $this->stateActs(),
                'sections' => (int) ($counts['sections'] ?? array_sum(array_column($acts, 'sections'))),
                'schedules' => (int) ($counts['schedules'] ?? array_sum(array_column($acts, 'schedules'))),
            ],
            'latest' => array_slice($acts, 0, 6),
            'years' => array_slice($this->content->years(Jurisdiction::DEFAULT), 0, 24, true),
            'year_count' => count($this->content->years(Jurisdiction::DEFAULT)),
            'categories' => $this->content->categories(Jurisdiction::DEFAULT, array_keys(ActController::TABS)),
            'updated' => (string) ($manifest['exported_at'] ?? ''),
            'states' => array_slice($states, 0, self::STATES_SHOWN),
            'state_count' => count($states),
        ], [
            'title' => 'Laws of India – Central and State Acts, sections and PDFs',
            'description' => 'Read Central Acts and Acts of States and Union Territories section by section, '
                . 'with schedules, rules, notifications and official PDFs.',
            'nav' => 'home',
            'schema' => [Seo::website($this->config['site_name'], $this->absolute())],
        ]);
    }

    /** States / UTs named in the home page's "Browse by State" box; the rest are behind "All … →". */
    private const STATES_SHOWN = 10;

    /**
     * The exported States and Union Territories, the one with the most Acts first (then A to Z).
     *
     * @return list<array{code: string, name: string, acts: int}>
     */
    private function states(): array
    {
        $states = [];
        foreach (Jurisdiction::codes() as $code) {
            if ($code !== Jurisdiction::DEFAULT) {
                $states[] = ['code' => $code, 'name' => Jurisdiction::detail($code), 'acts' => (int) ($this->content->counts($code)['acts'] ?? 0)];
            }
        }
        usort($states, static fn(array $a, array $b): int => $b['acts'] <=> $a['acts'] ?: strcasecmp($a['name'], $b['name']));
        return $states;
    }

    private function stateActs(): int
    {
        $total = 0;
        foreach (array_keys($this->content->jurisdictions()) as $code) {
            if ($code !== Jurisdiction::DEFAULT) {
                $total += (int) ($this->content->counts($code)['acts'] ?? 0);
            }
        }
        return $total;
    }

    /**
     * /acts has no page of its own; the home page is the Laws landing.
     */
    public function acts(): void
    {
        throw new Redirect(Url::home(), 301);
    }
}
