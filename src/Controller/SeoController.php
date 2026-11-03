<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
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
     * Every indexable page: lists (all pages), Acts with their tabs, sections and schedules.
     * Search and filtered lists are left out (noindex).
     *
     * @return list<string>
     */
    private function urls(): array
    {
        $acts = $this->content->acts();
        $per = $this->perPage();
        $urls = [Url::home()];
        for ($n = 1, $last = max(1, (int) ceil(count($acts) / $per)); $n <= $last; $n++) {
            $urls[] = Url::paged(Url::actList(), $n);
        }
        foreach ($this->content->years() as $year => $count) {
            for ($n = 1, $last = (int) ceil($count / $per); $n <= $last; $n++) {
                $urls[] = Url::paged(Url::actList((string) $year), $n);
            }
        }
        foreach ($this->content->categories(array_keys(ActController::TABS)) as $tab => $count) {
            for ($n = 1, $last = (int) ceil($count / $per); $n <= $last; $n++) {
                $urls[] = Url::paged(Url::category($tab), $n);
            }
        }
        foreach ($acts as $act) {
            $urls[] = Url::act($act['slug']);
            $urls[] = Url::act($act['slug'], 'details');
            if ($act['schedules']) {
                $urls[] = Url::act($act['slug'], 'schedules');
            }
            foreach ($act['tabs'] ?? [] as $tab) {
                $urls[] = Url::act($act['slug'], $tab);
            }
        }
        foreach ($this->content->searchRows() as [$actSlug, $slug]) {
            $urls[] = Url::provision($actSlug, $slug);
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
