<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\SpecialistProfile;
use App\Models\SubCategory;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class SearchController extends Controller
{
    /**
     * Full search-results page (/search?q=...&tab=all|prod|usta|blog).
     */
    public function index(Request $request): View
    {
        $q = $request->query('q');
        $query = is_string($q) && trim($q) !== '' ? trim($q) : '';
        $tab = $request->query('tab');
        $tab = is_string($tab) && in_array($tab, ['prod', 'usta', 'blog'], true) ? $tab : 'all';

        $products = collect();
        $masters = collect();
        $posts = collect();
        $classSuggestions = collect();
        $productCount = 0;
        $masterCount = 0;
        $postCount = 0;

        $searchTerm = $query !== '' ? $query : t('search.default_query');

        // Products -- fetch when showing "all" or "prod" tab
        if ($tab === 'all' || $tab === 'prod') {
            $productQuery = Product::visible()->approved();
            $productQuery = SearchService::buildProductQuery($productQuery, $searchTerm);
            $productQuery->with(['images', 'category', 'reviews']);
            SearchService::orderProductsByRelevance($productQuery, $searchTerm);

            $productCount = $productQuery->count();
            $products = $productQuery->take(4)->get();

            // Classifier dead-end (sheet 8, rec. R7): a colloquial query such as
            // "kvars vinil" resolves through search_synonyms to a product class
            // that simply has no listings yet. Instead of a bare "no results"
            // page, offer the class (and its section) in the catalog.
            if ($productCount === 0) {
                $classSuggestions = $this->classSuggestions($searchTerm);
            }
        }

        // Masters -- fetch when showing "all" or "usta" tab
        if ($tab === 'all' || $tab === 'usta') {
            $masterQuery = SpecialistProfile::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active));
            $masterQuery = SearchService::buildSpecialistQuery($masterQuery, $searchTerm);
            $masterQuery->with('user');

            $masterCount = $masterQuery->count();
            $masters = $masterQuery->take(4)->get();
        }

        // Blog posts -- fetch when showing "all" or "blog" tab
        if ($tab === 'all' || $tab === 'blog') {
            $postQuery = BlogPost::published();
            $postQuery = SearchService::buildBlogQuery($postQuery, $searchTerm);

            $postCount = $postQuery->count();
            $posts = $postQuery->latest('published_at')->take(4)->get();
        }

        $totalCount = 0;
        if ($tab === 'all') {
            $totalCount = $productCount + $masterCount + $postCount;
        } elseif ($tab === 'prod') {
            $totalCount = $productCount;
        } elseif ($tab === 'usta') {
            $totalCount = $masterCount;
        } elseif ($tab === 'blog') {
            $totalCount = $postCount;
        }

        return view('pages.search', compact(
            'query',
            'tab',
            'searchTerm',
            'products',
            'masters',
            'posts',
            'classSuggestions',
            'productCount',
            'masterCount',
            'postCount',
            'totalCount',
        ));
    }

    /**
     * Product classes the query resolved to (class name or seeded search
     * synonym), eager-loaded with the group/section needed to link into the
     * catalog. Capped so the suggestion strip stays a hint, not a second result
     * list.
     *
     * @return Collection<int, SubCategory>
     */
    private function classSuggestions(string $searchTerm): Collection
    {
        $ids = SearchService::findMatchingSubCategoryIds($searchTerm);

        if ($ids === []) {
            return collect();
        }

        return SubCategory::active()
            ->whereIn('id', $ids)
            ->with('category.parent')
            ->ordered()
            ->take(3)
            ->get();
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (mb_strlen($query) < 1) {
            return response()->json(['suggests' => [], 'products' => [], 'masters' => [], 'total' => 0]);
        }

        $suggestions = SearchService::getSuggestions($query);

        // buildProductQuery already matches on the category name, so the
        // autocomplete list uses exactly the same predicate as the counters.
        $productQuery = Product::visible()->approved();
        $productQuery = SearchService::buildProductQuery($productQuery, $query);

        SearchService::orderProductsByRelevance($productQuery, $query);

        $products = $productQuery
            ->with(['images', 'category'])
            ->take(3)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'cat' => $p->category?->name,
                'price' => number_format($p->price, 2).' ₼',
                'img' => $p->main_image_url,
            ]);

        $masterQuery = SpecialistProfile::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active));
        $masterQuery = SearchService::buildSpecialistQuery($masterQuery, $query);

        $masters = $masterQuery
            ->with(['user', 'specialty'])
            ->take(2)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'initials' => mb_substr($s->user->first_name, 0, 1).mb_substr($s->user->last_name, 0, 1),
                'name' => $s->user->first_name.' '.mb_substr($s->user->last_name, 0, 1).'.',
                'role' => $s->craft_label,
                'rate' => '4.9',
            ]);

        $totalProducts = Product::visible()->approved();
        $totalProducts = SearchService::buildProductQuery($totalProducts, $query)->count();

        $totalMasters = SpecialistProfile::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active));
        $totalMasters = SearchService::buildSpecialistQuery($totalMasters, $query)->count();

        return response()->json([
            'suggests' => array_slice($suggestions, 0, 5),
            'products' => $products,
            'masters' => $masters,
            'total' => $totalProducts + $totalMasters,
        ]);
    }
}
