<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Jurisdiction;
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

    public function sections(string $jurisdiction, string $act, string $page = ''): void
    {
        $row = $this->loadAct($jurisdiction, $act);
        $link = fn(int $n): string => Url::paged(Url::act($jurisdiction, $act), $n);
        $number = Pager::number($page, $link);
        $pager = Pager::make($number, count($row['sections']), $this->perPage(), $link);
        $this->view->page('act-sections', [
            'act' => $row,
            'tabs' => $this->tabs($row, ''),
            'sections' => $this->withText($row, 'SECTION', array_slice($row['sections'], $pager['offset'], $pager['per_page'])),
            'pager' => $pager,
        ], [
            'nav' => self::nav($jurisdiction),
            'title' => $this->withPage($this->name($row) . ' – Sections and full text', $number),
            'description' => $this->describePage($this->description($row), $number),
            'canonical' => $link($number),
            'prev' => $pager['prev'],
            'next' => $pager['next'],
            'type' => 'article',
            'schema' => [Seo::act($row, $this->absolute())],
        ]);
    }

    public function schedules(string $jurisdiction, string $act): void
    {
        $row = $this->loadAct($jurisdiction, $act);
        if (!$row['schedules']) {
            throw new HttpError(404);
        }
        $this->view->page('act-schedules', [
            'act' => $row,
            'tabs' => $this->tabs($row, 'schedules'),
            'schedules' => $this->withText($row, 'SCHEDULE', $row['schedules']),
        ], [
            'nav' => self::nav($jurisdiction),
            'title' => 'Schedules of ' . $this->name($row),
            'description' => Seo::description(sprintf(
                '%s: %d schedule%s with full text and official PDFs.',
                $this->name($row),
                count($row['schedules']),
                count($row['schedules']) === 1 ? '' : 's',
            )),
            'type' => 'article',
        ]);
    }

    public function details(string $jurisdiction, string $act): void
    {
        $row = $this->loadAct($jurisdiction, $act);
        $rows = array_filter([
            'Short title' => $row['title'],
            'Long title' => $row['long_title'],
            'Act number' => $this->number($row),
            'Act year' => $row['year'],
            'Enactment date' => format_date($row['enactment_date']),
            'In force from' => format_date($row['enforcement_date']),
            'Ministry' => $row['ministry'],
            'Department' => $row['department'],
            'Jurisdiction' => Jurisdiction::detail($jurisdiction),
            'Sections' => (string) count($row['sections']),
            'Schedules' => (string) count($row['schedules']),
            'Status' => $row['repeal_status'] === 'not_repealed' ? 'Not repealed' : '',
            'Last checked' => format_date($row['last_checked_at']),
        ], fn(string $v): bool => $v !== '' && $v !== '0');
        $this->view->page('act-details', [
            'act' => $row,
            'tabs' => $this->tabs($row, 'details'),
            'rows' => $rows,
        ], [
            'nav' => self::nav($jurisdiction),
            'title' => $this->name($row) . ' – Act details',
            'description' => $this->detailsDescription($row),
            'type' => 'article',
            'schema' => [Seo::act($row, $this->absolute())],
        ]);
    }

    public function tab(string $jurisdiction, string $act, string $tab, string $page = ''): void
    {
        $row = $this->loadAct($jurisdiction, $act);
        $docs = empty($row['tabs'][$tab]) ? [] : $this->content->tab($jurisdiction, $act, $tab);
        if (!$docs) {
            // Empty tabs are not linked anywhere; don't serve empty pages.
            throw new HttpError(404);
        }
        $link = fn(int $n): string => Url::paged(Url::act($jurisdiction, $act, $tab), $n);
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
            'nav' => self::nav($jurisdiction),
            'title' => $this->withPage("$label under " . $this->name($row), $number),
            'description' => $this->describePage(Seo::description("$label made under " . $this->name($row) . ', with official PDFs.', 150), $number),
            'canonical' => $link($number),
            'prev' => $pager['prev'],
            'next' => $pager['next'],
        ]);
    }

    /**
     * The old URL of a section (section-2a) or schedule (first-schedule): they have no page of
     * their own any more (owner, 2026-11-03), so 301 to where the Act page shows them.
     */
    public function provision(string $jurisdiction, string $act, string $provision): void
    {
        $row = $this->loadAct($jurisdiction, $act);
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
        [$where, $slug] = [$act['jurisdiction'], $act['slug']];
        $tabs = [['label' => 'Sections', 'count' => count($act['sections']), 'url' => Url::act($where, $slug), 'active' => $active === '']];
        if ($act['schedules']) {
            $tabs[] = ['label' => 'Schedules', 'count' => count($act['schedules']), 'url' => Url::act($where, $slug, 'schedules'), 'active' => $active === 'schedules'];
        }
        $tabs[] = ['label' => 'Act Details', 'count' => null, 'url' => Url::act($where, $slug, 'details'), 'active' => $active === 'details'];
        foreach (self::TABS as $tab => $collection) {
            if (!empty($act['tabs'][$tab])) {
                $tabs[] = [
                    'label' => collection_label($collection, true),
                    'count' => (int) $act['tabs'][$tab],
                    'url' => Url::act($where, $slug, $tab),
                    'active' => $active === $tab,
                ];
            }
        }
        return $tabs;
    }

    /**
     * Sections or schedules from act.json with their text (their own files) added, each linked PDF with
     * its /pdf URL (`href`); an entry whose file is missing is kept as it is and shows "no text".
     *
     * @param array<string, mixed> $act act.json
     * @param string $kind SECTION or SCHEDULE
     * @param list<array<string, mixed>> $list
     *
     * @return list<array<string, mixed>>
     */
    private function withText(array $act, string $kind, array $list): array
    {
        $links = null;
        return array_map(
            function (array $entry) use ($act, $kind, &$links): array {
                $item = $this->content->provision($act['jurisdiction'], $act['slug'], $kind, $entry['slug']);
                if ($item === null) {
                    return $entry + ['kind' => $kind];
                }
                // Each linked PDF's one /pdf URL (Content::pdfLinks): its tab's, or by the first section linking it.
                foreach ($item['linked'] ?? [] as $i => $doc) {
                    foreach ((array) ($doc['files'] ?? []) as $n => $file) {
                        $links ??= $this->content->pdfLinks($act, array_keys(self::TABS));
                        if (isset($links[$file['sha256'] ?? ''])) {
                            $item['linked'][$i]['files'][$n]['href'] = $links[$file['sha256']]['href'];
                        }
                    }
                }
                return $item;
            },
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
     * The Act's title, with the State/UT for a State or UT Act ("The X Act, 1955 – Ladakh"), so a
     * Central Act listed under a UT doesn't share its titles and descriptions with the Central page.
     *
     * @param array<string, mixed> $act
     */
    private function name(array $act): string
    {
        $where = (string) $act['jurisdiction'];
        return $where === Jurisdiction::DEFAULT ? $act['title'] : $act['title'] . ' – ' . Jurisdiction::detail($where);
    }

    /**
     * Act Details description: the summary or long title when it says more than the short title,
     * else the Act's facts. Never the Sections page's description.
     *
     * @param array<string, mixed> $act
     */
    private function detailsDescription(array $act): string
    {
        foreach ([$act['summary_html'] ?? '', $act['long_title'] ?? ''] as $text) {
            $text = Seo::description((string) $text);
            if (mb_strlen($text) >= 50 && mb_strtolower(trim($text, ' .')) !== mb_strtolower(trim((string) $act['title'], ' .'))) {
                return $text;
            }
        }
        $facts = array_filter([
            $act['act_number'] !== '' ? 'Act No. ' . $this->number($act) : '',
            ($act['enactment_date'] ?? '') !== '' ? 'enacted on ' . format_date($act['enactment_date']) : '',
            ($act['ministry'] ?? '') !== '' ? 'administered by the ' . $act['ministry'] : '',
        ]);
        return Seo::description('Details of ' . $this->name($act) . ($facts ? ': ' . implode(', ', $facts) : '') . '. Short and long title, dates, status and history.');
    }

    /**
     * @param array<string, mixed> $act
     */
    private function description(array $act): string
    {
        $parts = [$this->name($act)];
        if ($act['act_number'] !== '') {
            $parts[] = 'Act No. ' . $this->number($act);
        }
        $parts[] = $act['sections']
            ? count($act['sections']) . ' sections with full text and official PDF'
            : 'full text in the official PDF';
        return Seo::description(implode(' – ', $parts) . '.');
    }
}
