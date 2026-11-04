<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Pager;
use LegalQueryWeb\Redirect;
use LegalQueryWeb\Seo;
use LegalQueryWeb\Url;

/**
 * An Act and its tabs: Sections (paged), Schedules, Act Details, linked documents. Sections and
 * schedules are shown only inside these tabs; their old single-page URLs redirect there.
 */
final class ActController extends Controller
{
    /**
     * Tab URL segment => linked-document collection.
     */
    public const TABS = [
        'rules' => 'RULE',
        'notifications' => 'NOTIFICATION',
        'regulations' => 'REGULATION',
        'orders' => 'ORDER',
        'ordinances' => 'ORDINANCE',
        'statutes' => 'STATUTE',
        'circulars' => 'CIRCULAR',
        'appendices' => 'APPENDIX',
        'amendments' => 'ACT_AMENDMENT',
    ];

    public function sections(string $act, string $page = ''): void
    {
        $row = $this->loadAct($act);
        $link = fn(int $n): string => Url::paged(Url::act($act), $n);
        $number = Pager::number($page, $link);
        $pager = Pager::make($number, count($row['sections']), $this->perPage(), $link);
        $this->view->page('act-sections', [
            'act' => $row,
            'tabs' => $this->tabs($row, ''),
            'sections' => $this->withText($act, 'SECTION', array_slice($row['sections'], $pager['offset'], $pager['per_page'])),
            'pager' => $pager,
        ], [
            'title' => $this->withPage($row['title'] . ' – Sections and full text', $number),
            'description' => $this->describePage($this->description($row), $number),
            'canonical' => $link($number),
            'prev' => $pager['prev'],
            'next' => $pager['next'],
            'type' => 'article',
            'schema' => [Seo::act($row, $this->absolute())],
        ]);
    }

    public function schedules(string $act): void
    {
        $row = $this->loadAct($act);
        if (!$row['schedules']) {
            throw new HttpError(404);
        }
        $this->view->page('act-schedules', [
            'act' => $row,
            'tabs' => $this->tabs($row, 'schedules'),
            'schedules' => $this->withText($act, 'SCHEDULE', $row['schedules']),
        ], [
            'title' => 'Schedules of ' . $row['title'],
            'description' => sprintf(
                '%s: %d schedule%s with full text and official PDFs.',
                $row['title'],
                count($row['schedules']),
                count($row['schedules']) === 1 ? '' : 's',
            ),
            'type' => 'article',
        ]);
    }

    public function details(string $act): void
    {
        $row = $this->loadAct($act);
        $rows = array_filter([
            'Short title' => $row['title'],
            'Long title' => $row['long_title'],
            'Act number' => $this->number($row),
            'Act year' => $row['year'],
            'Enactment date' => format_date($row['enactment_date']),
            'In force from' => format_date($row['enforcement_date']),
            'Ministry' => $row['ministry'],
            'Department' => $row['department'],
            'Jurisdiction' => 'Central (Parliament of India)',
            'Sections' => (string) count($row['sections']),
            'Schedules' => (string) count($row['schedules']),
            'Status' => $row['repeal_status'] === 'not_repealed' ? 'Not repealed' : '',
            'Last checked on India Code' => format_date($row['last_checked_at']),
        ], fn(string $v): bool => $v !== '' && $v !== '0');
        $this->view->page('act-details', [
            'act' => $row,
            'tabs' => $this->tabs($row, 'details'),
            'rows' => $rows,
        ], [
            'title' => $row['title'] . ' – Act details',
            'description' => Seo::description(
                ($row['summary_html'] ?? '') ?: (($row['long_title'] ?? '') ?: $this->description($row)),
            ),
            'type' => 'article',
            'schema' => [Seo::act($row, $this->absolute())],
        ]);
    }

    public function tab(string $act, string $tab, string $page = ''): void
    {
        $row = $this->loadAct($act);
        $docs = empty($row['tabs'][$tab]) ? [] : $this->content->tab($act, $tab);
        if (!$docs) {
            // Empty tabs are not linked anywhere; don't serve empty pages.
            throw new HttpError(404);
        }
        $link = fn(int $n): string => Url::paged(Url::act($act, $tab), $n);
        $number = Pager::number($page, $link);
        $pager = Pager::make($number, count($docs), $this->perPage(), $link);
        $items = array_slice($docs, $pager['offset'], $pager['per_page']);
        $label = collection_label(self::TABS[$tab], true);
        $this->view->page('act-tab', [
            'act' => $row,
            'tabs' => $this->tabs($row, $tab),
            'label' => $label,
            'items' => $items,
            'pager' => $pager,
        ], [
            'title' => $this->withPage("$label under " . $row['title'], $number),
            'description' => $this->describePage("$label made under " . $row['title'] . ', with official PDFs.', $number),
            'canonical' => $link($number),
            'prev' => $pager['prev'],
            'next' => $pager['next'],
        ]);
    }

    /**
     * The old URL of a section (section-2a) or schedule (first-schedule): they have no page of
     * their own any more (owner, 2026-11-03), so 301 to where the Act page shows them.
     */
    public function provision(string $act, string $provision): void
    {
        $row = $this->loadAct($act);
        throw new Redirect($this->provisionUrl($row, $provision) ?? throw new HttpError(404), 301);
    }

    /**
     * Tabs as on India Code: Sections, Schedules (if any), Act Details, then every
     * linked-document collection that has items. Each tab is its own URL.
     *
     * @param array<string, mixed> $act
     *
     * @return list<array{label: string, count: ?int, url: string, active: bool}>
     */
    private function tabs(array $act, string $active): array
    {
        $slug = $act['slug'];
        $tabs = [['label' => 'Sections', 'count' => count($act['sections']), 'url' => Url::act($slug), 'active' => $active === '']];
        if ($act['schedules']) {
            $tabs[] = ['label' => 'Schedules', 'count' => count($act['schedules']), 'url' => Url::act($slug, 'schedules'), 'active' => $active === 'schedules'];
        }
        $tabs[] = ['label' => 'Act Details', 'count' => null, 'url' => Url::act($slug, 'details'), 'active' => $active === 'details'];
        foreach (self::TABS as $tab => $collection) {
            if (!empty($act['tabs'][$tab])) {
                $tabs[] = [
                    'label' => collection_label($collection, true),
                    'count' => (int) $act['tabs'][$tab],
                    'url' => Url::act($slug, $tab),
                    'active' => $active === $tab,
                ];
            }
        }
        return $tabs;
    }

    /**
     * Sections or schedules from act.json with their text (their own files) added; an entry whose
     * file is missing is kept as it is and shows "no text".
     *
     * @param string $kind SECTION or SCHEDULE
     * @param list<array<string, mixed>> $list
     *
     * @return list<array<string, mixed>>
     */
    private function withText(string $act, string $kind, array $list): array
    {
        return array_map(
            fn(array $entry): array => $this->content->provision($act, $kind, $entry['slug']) ?? $entry + ['kind' => $kind],
            $list,
        );
    }

    /**
     * @param array<string, mixed> $act
     */
    private function number(array $act): string
    {
        return $act['act_number'] !== '' ? $act['act_number'] . ' of ' . $act['year'] : '';
    }

    /**
     * @param array<string, mixed> $act
     */
    private function description(array $act): string
    {
        $parts = [$act['title']];
        if ($act['act_number'] !== '') {
            $parts[] = 'Act No. ' . $this->number($act);
        }
        $parts[] = count($act['sections']) . ' sections with full text and official PDF';
        return implode(' – ', $parts) . '.';
    }
}
