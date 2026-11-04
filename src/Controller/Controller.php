<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\Content;
use LegalQueryWeb\HttpError;
use LegalQueryWeb\Url;
use LegalQueryWeb\View;

/**
 * Shared by all controllers: content, view, settings and the query string.
 */
abstract class Controller
{
    /**
     * @param array<string, mixed> $config
     * @param array<string, mixed> $query $_GET
     */
    public function __construct(
        protected readonly Content $content,
        protected readonly View $view,
        protected readonly array $config,
        protected readonly array $query,
    ) {
    }

    protected function perPage(): int
    {
        return max(1, (int) ($this->config['per_page'] ?? 20));
    }

    /**
     * Where a section or schedule of an Act is shown (its Sections page N or the Schedules tab,
     * with the provision's anchor), or null when the Act has no such provision.
     *
     * @param array<string, mixed> $act act.json
     */
    protected function provisionUrl(array $act, string $slug): ?string
    {
        foreach ($act['sections'] as $i => $section) {
            if ($section['slug'] === $slug) {
                return Url::provision($act['slug'], $slug, 'SECTION', intdiv($i, $this->perPage()) + 1);
            }
        }
        foreach ($act['schedules'] as $schedule) {
            if ($schedule['slug'] === $slug) {
                return Url::provision($act['slug'], $slug, 'SCHEDULE');
            }
        }
        return null;
    }

    /**
     * A query-string value as a trimmed string ('' when missing or not a string).
     */
    protected function queryString(string $name, int $maxLength = 200): string
    {
        $value = $this->query[$name] ?? '';
        return is_string($value) ? mb_substr(trim($value), 0, $maxLength) : '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function loadAct(string $slug): array
    {
        return $this->content->act($slug) ?? throw new HttpError(404);
    }

    /**
     * Breadcrumb links before the current page: [label, url] pairs.
     *
     * @return list<array{0: string, 1: string}>
     */
    protected function crumbs(bool $actList = true): array
    {
        $crumbs = [['Home', Url::home()]];
        if ($actList) {
            $crumbs[] = ['Central Acts', Url::actList()];
        }
        return $crumbs;
    }

    /**
     * "Title – Page 2" so paged lists don't share one <title>.
     */
    protected function withPage(string $title, int $page): string
    {
        return $page > 1 ? "$title – Page $page" : $title;
    }

    /**
     * A meta description with "Page 2." added, so paged lists don't share one description.
     */
    protected function describePage(string $description, int $page): string
    {
        return $page > 1 ? "$description Page $page." : $description;
    }

    /**
     * Turns a site path into a full URL (for structured data).
     */
    protected function absolute(): \Closure
    {
        return $this->view->absolute(...);
    }
}
