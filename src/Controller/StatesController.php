<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Url;

/**
 * /states: the States and Union Territories whose Acts are on the site (every exported
 * jurisdiction except central), A to Z, with their Act and section counts from manifest.json.
 * Their Acts are shown as India Code lists them (owner, 2026-11-17).
 */
final class StatesController extends Controller
{
    public function index(): void
    {
        $states = [];
        foreach (Jurisdiction::codes() as $code) {
            if ($code === Jurisdiction::DEFAULT) {
                continue;
            }
            $counts = $this->content->counts($code);
            $states[] = [
                'code' => $code,
                'name' => Jurisdiction::detail($code),
                'acts' => (int) ($counts['acts'] ?? count($this->content->acts($code))),
                'sections' => (int) ($counts['sections'] ?? 0),
            ];
        }
        if (!$states) {
            throw new HttpError(404);
        }
        usort($states, static fn(array $a, array $b): int => strcasecmp($a['name'], $b['name']));
        $this->view->page('states', ['states' => $states], [
            'title' => 'Acts of the States and Union Territories of India',
            'description' => 'State and Union Territory Acts of India as listed on India Code, with sections '
                . 'where available, rules, notifications and official PDFs.',
            'canonical' => Url::states(),
            'nav' => 'states',
        ]);
    }
}
