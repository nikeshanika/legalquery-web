<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Seo;
use LegalQueryWeb\Url;

/**
 * Home page (laid out like India Code's home): search band, counts, latest Acts, browse by year,
 * browse by category. The lists are Central Acts (Jurisdiction::DEFAULT); the search and the State Acts
 * card cover the States and UTs too.
 */
final class HomeController extends Controller
{
    public function index(): void
    {
        $acts = $this->content->acts(Jurisdiction::DEFAULT);
        $manifest = $this->content->manifest();
        $counts = $this->content->counts(Jurisdiction::DEFAULT);
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
        ], [
            'title' => 'Laws of India – Central and State Acts, sections and PDFs',
            'description' => 'Read Central Acts and Acts of States and Union Territories section by section, '
                . 'with schedules, rules, notifications and official PDFs from India Code.',
            'nav' => 'home',
            'schema' => [Seo::website($this->config['site_name'] . ' Laws', $this->absolute())],
        ]);
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
