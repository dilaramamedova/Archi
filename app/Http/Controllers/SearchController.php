<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\SpecialistProfile;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
        $productCount = 0;
        $masterCount = 0;
        $postCount = 0;

        $searchTerm = $query !== '' ? $query : __('search.default_query');
        $terms = SearchService::expandQuery($searchTerm);

        // Products -- fetch when showing "all" or "prod" tab
        if ($tab === 'all' || $tab === 'prod') {
            $productQuery = Product::visible()->approved();
            $productQuery = SearchService::buildProductQuery($productQuery, $terms);
            $productQuery->with(['images', 'category', 'reviews']);

            $productCount = $productQuery->count();
            $products = $productQuery->take(4)->get();
        }

        // Masters -- fetch when showing "all" or "usta" tab
        if ($tab === 'all' || $tab === 'usta') {
            $masterQuery = SpecialistProfile::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active));
            $masterQuery = SearchService::buildSpecialistQuery($masterQuery, $terms);
            $masterQuery->with('user');

            $masterCount = $masterQuery->count();
            $masters = $masterQuery->take(4)->get();
        }

        // Blog posts -- fetch when showing "all" or "blog" tab
        if ($tab === 'all' || $tab === 'blog') {
            $postQuery = BlogPost::published();
            $postQuery = SearchService::buildBlogQuery($postQuery, $terms);

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
            'productCount',
            'masterCount',
            'postCount',
            'totalCount',
        ));
    }

    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (mb_strlen($query) < 1) {
            return response()->json(['suggests' => [], 'products' => [], 'masters' => [], 'total' => 0]);
        }

        $terms = SearchService::expandQuery($query);
        $suggestions = SearchService::getSuggestions($query);

        // Find categories matching any term, to boost product results
        $matchingCategoryIds = SearchService::findMatchingCategoryIds($terms);

        $products = Product::visible()->approved()
            ->where(function ($q) use ($terms, $matchingCategoryIds) {
                foreach ($terms as $term) {
                    $q->orWhere('name', 'like', "%{$term}%")
                        ->orWhereHas('brand', fn ($bq) => $bq->where('name', 'like', "%{$term}%"))
                        ->orWhere('description', 'like', "%{$term}%");
                }

                // Include products from matching categories
                if (! empty($matchingCategoryIds)) {
                    $q->orWhereIn('category_id', $matchingCategoryIds);
                }

                // Also check category name directly
                $q->orWhereHas('category', function ($cq) use ($terms) {
                    $cq->where(function ($inner) use ($terms) {
                        foreach ($terms as $term) {
                            $inner->orWhere('name', 'like', "%{$term}%");
                        }
                    });
                });
            })
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

        $masters = SpecialistProfile::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('craft', 'like', "%{$term}%")
                        ->orWhere('skills', 'like', "%{$term}%");
                }

                $q->orWhereHas('specialty', function ($specialtyQuery) use ($terms) {
                    $specialtyQuery->where(function ($inner) use ($terms) {
                        foreach ($terms as $term) {
                            $inner->orWhere('name', 'like', "%{$term}%");
                        }
                    });
                });

                $q->orWhereHas('user', function ($uq) use ($terms) {
                    $uq->where(function ($inner) use ($terms) {
                        foreach ($terms as $term) {
                            $inner->orWhere('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%");
                        }
                    });
                });
            })
            ->with(['user', 'specialty'])
            ->take(2)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'initials' => mb_substr($s->user->first_name, 0, 1).mb_substr($s->user->last_name, 0, 1),
                'name' => $s->user->first_name.' '.mb_substr($s->user->last_name, 0, 1).'.',
                'role' => $s->specialty?->name ?? $s->craft,
                'rate' => '4.9',
            ]);

        $totalProducts = Product::visible()->approved();
        $totalProducts = SearchService::buildProductQuery($totalProducts, $terms)->count();

        $totalMasters = SpecialistProfile::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active));
        $totalMasters = SearchService::buildSpecialistQuery($totalMasters, $terms)->count();

        return response()->json([
            'suggests' => array_slice($suggestions, 0, 5),
            'products' => $products,
            'masters' => $masters,
            'total' => $totalProducts + $totalMasters,
        ]);
    }
}
