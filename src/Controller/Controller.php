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
