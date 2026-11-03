<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\Redirect;
use LegalQueryWeb\Seo;
use LegalQueryWeb\Url;

/**
 * Home page (laid out like India Code's home): search band, counts, latest Acts, browse by year,
 * browse by category, official references.
 */
final class HomeController extends Controller
{
    public function index(): void
    {
        $acts = $this->content->acts();
        $manifest = $this->content->manifest();
        $this->view->page('home', [
            'counts' => [
                'acts' => count($acts),
                'sections' => (int) ($manifest['sections'] ?? array_sum(array_column($acts, 'sections'))),
                'schedules' => (int) ($manifest['schedules'] ?? array_sum(array_column($acts, 'schedules'))),
            ],
            'latest' => array_slice($acts, 0, 6),
            'years' => array_slice($this->content->years(), 0, 24, true),
            'year_count' => count($this->content->years()),
            'categories' => $this->content->categories(array_keys(ActController::TABS)),
            'updated' => (string) ($manifest['exported_at'] ?? ''),
        ], [
            'title' => 'Laws of India – Central Acts, sections and PDFs',
            'description' => 'Read Central Acts of India section by section, with schedules, rules, '
                . 'notifications and official PDFs from India Code.',
            'nav' => 'home',
            'schema' => [Seo::website($this->config['site_name'] . ' Laws', $this->absolute())],
        ]);
    }

    /**
     * /acts has no page of its own; the home page is the Laws landing.
     */
    public function acts(): void
    {
        throw new Redirect(Url::home(), 301);
    }
}
