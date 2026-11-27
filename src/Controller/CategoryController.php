<?php

declare(strict_types=1);

namespace LegalQueryWeb\Controller;

use LegalQueryWeb\HttpError;
use LegalQueryWeb\Jurisdiction;
use LegalQueryWeb\Pager;
use LegalQueryWeb\Url;

/**
 * /rules, /notifications, ... [/page/N]: Central Acts that have that kind of linked document,
 * newest first, each linking to the Act's tab (India Code's "Browse by Category"). Central only
 * (Jurisdiction::DEFAULT) until the owner decides how State Acts appear here.
 */
final class CategoryController extends Controller
{
    public function index(string $category, string $page = ''): void
    {
        $acts = array_values(array_filter(
            $this->content->acts(Jurisdiction::DEFAULT),
            fn(array $a): bool => in_array($category, $a['tabs'] ?? [], true),
        ));
        if (!$acts) {
            throw new HttpError(404);
        }
        $link = fn(int $n): string => Url::paged(Url::category($category), $n);
        $number = Pager::number($page, $link);
        $pager = Pager::make($number, count($acts), $this->perPage(), $link);

        $label = collection_label(ActController::TABS[$category], true);
        $this->view->page('category', [
            'heading' => $label,
            'tab' => $category,
            'acts' => array_slice($acts, $pager['offset'], $pager['per_page']),
            'pager' => $pager,
            'categories' => $this->content->categories(Jurisdiction::DEFAULT, array_keys(ActController::TABS)),
        ], [
            'title' => $this->withPage("$label of Central Acts", $number),
            'description' => $this->describePage("Central Acts of India with $label made under them, with official PDFs.", $number),
            'canonical' => $link($number),
            'prev' => $pager['prev'],
            'next' => $pager['next'],
        ]);
    }
}
