<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Pagination for every list: crawlable links, page 1 on the plain URL, 404 past the end.
 */
final class Pager
{
    /**
     * @param callable(int): string $url Link to page N.
     *
     * @return array{page: int, last: int, total: int, from: int, to: int, offset: int, per_page: int,
     *   pages: list<array{number: int, url: string, current: bool}|null>, prev: ?string, next: ?string}
     */
    public static function make(int $page, int $total, int $perPage, callable $url): array
    {
        $perPage = max(1, $perPage);
        $last = max(1, (int) ceil($total / $perPage));
        if ($page < 1 || $page > $last) {
            throw new HttpError(404);
        }
        // First, last, and two either side of the current page; null marks a gap.
        $pages = [];
        if ($last > 1) {
            $previous = 0;
            foreach (array_unique([1, ...range(max(1, $page - 2), min($last, $page + 2)), $last]) as $n) {
                if ($n - $previous > 1) {
                    $pages[] = null;
                }
                $pages[] = ['number' => $n, 'url' => $url($n), 'current' => $n === $page];
                $previous = $n;
            }
        }
        return [
            'page' => $page,
            'last' => $last,
            'total' => $total,
            'from' => $total ? ($page - 1) * $perPage + 1 : 0,
            'to' => min($page * $perPage, $total),
            'offset' => ($page - 1) * $perPage,
            'per_page' => $perPage,
            'pages' => $pages,
            'prev' => $page > 1 ? $url($page - 1) : null,
            'next' => $page < $last ? $url($page + 1) : null,
        ];
    }

    /**
     * Page number from a /page/N route segment ('' = 1). "/page/1" redirects to the plain
     * URL; "/page/0" or "/page/01" is a 404.
     *
     * @param callable(int): string $url
     */
    public static function number(string $segment, callable $url): int
    {
        if ($segment === '') {
            return 1;
        }
        if (!preg_match('/^[1-9]\d{0,5}$/', $segment)) {
            throw new HttpError(404);
        }
        if ($segment === '1') {
            throw new Redirect($url(1), 301);
        }
        return (int) $segment;
    }
}
