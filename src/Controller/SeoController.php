<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Url;

/**
 * sitemap.xml and robots.txt (both need the site URL from config). Up to 50,000 URLs,
 * /sitemap.xml is the sitemap itself; above that it is an index of /sitemap-1.xml, -2, ...
 */
final class SeoController extends Controller
{
    /** Most URLs one sitemap file may hold (sitemaps.org). */
    public const LIMIT = 50000;

    public function sitemap(): void
    {
        $urls = $this->urls();
        if (count($urls) <= self::LIMIT) {
            $this->urlset($urls);
            return;
        }
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
        echo '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
        for ($n = 1, $last = (int) ceil(count($urls) / self::LIMIT); $n <= $last; $n++) {
            echo '<sitemap><loc>', e($this->view->absolute("/sitemap-$n.xml")), '</loc></sitemap>', "\n";
        }
        echo '</sitemapindex>', "\n";
    }

    /**
     * /sitemap-N.xml: part N of the index (404 while one sitemap is enough).
     */
    public function sitemapPart(string $part): void
    {
        $urls = $this->urls();
        $n = (int) $part;
        if ((string) $n !== $part || count($urls) <= self::LIMIT || $n < 1 || ($n - 1) * self::LIMIT >= count($urls)) {
            throw new HttpError(404);
        }
        $this->urlset(array_slice($urls, ($n - 1) * self::LIMIT, self::LIMIT));
    }

    /**
     * Every indexable page: lists (all pages), Acts (every Sections page) with their tabs (every page).
     * Search and filtered lists are left out (noindex).
     *
     * @return list<string>
     */
    private function urls(): array
    {
        $per = $this->perPage();
        $urls = [Url::home()];
        if (count(Jurisdiction::codes()) > 1) {
            $urls[] = Url::states();
        }
        $acts = [];
        foreach (Jurisdiction::codes() as $code) {
            $list = $this->content->acts($code);
            if (!$list) {
                continue;
            }
            $acts = [...$acts, ...$list];
            for ($n = 1, $last = (int) ceil(count($list) / $per); $n <= $last; $n++) {
                $urls[] = Url::paged(Url::actList($code), $n);
            }
            foreach ($this->content->years($code) as $year => $count) {
                for ($n = 1, $last = (int) ceil($count / $per); $n <= $last; $n++) {
                    $urls[] = Url::paged(Url::actList($code, (string) $year), $n);
                }
            }
        }
        foreach ($this->content->categories(Jurisdiction::DEFAULT, array_keys(ActController::TABS)) as $tab => $count) {
            for ($n = 1, $last = (int) ceil($count / $per); $n <= $last; $n++) {
                $urls[] = Url::paged(Url::category($tab), $n);
            }
        }
        foreach ($acts as $act) {
            [$where, $slug] = [$act['jurisdiction'], $act['slug']];
            $urls[] = Url::act($where, $slug);
            // Sections are paged; their text is only on these pages (no page per section).
            for ($n = 2, $last = (int) ceil((int) ($act['sections'] ?? 0) / $per); $n <= $last; $n++) {
                $urls[] = Url::paged(Url::act($where, $slug), $n);
            }
            $urls[] = Url::act($where, $slug, 'details');
            if ($act['schedules']) {
                $urls[] = Url::act($where, $slug, 'schedules');
            }
            // Linked-document tabs, every page: the counts are in act.json (index.json has the names only).
            $counts = ($act['tabs'] ?? []) ? ($this->content->act($where, $slug)['tabs'] ?? []) : [];
            foreach ($act['tabs'] ?? [] as $tab) {
                $urls[] = Url::act($where, $slug, $tab);
                for ($n = 2, $last = (int) ceil((int) ($counts[$tab] ?? 0) / $per); $n <= $last; $n++) {
                    $urls[] = Url::paged(Url::act($where, $slug, $tab), $n);
                }
            }
        }
        return $urls;
    }

    /**
     * @param list<string> $urls
     */
    private function urlset(array $urls): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
        foreach ($urls as $url) {
            echo '<url><loc>', e($this->view->absolute($url)), '</loc></url>', "\n";
        }
        echo '</urlset>', "\n";
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\nDisallow: /search\n\nSitemap: ", $this->view->absolute('/sitemap.xml'), "\n";
    }
}
