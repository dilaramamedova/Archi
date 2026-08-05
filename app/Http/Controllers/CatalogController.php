<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SpecialistProfile;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::roots()->active()->ordered()
            ->withCount(['products' => fn ($q) => $q->visible()->approved()])
            ->with(['children' => fn ($q) => $q->active()->ordered()])
            ->get();

        $query = Product::visible()->approved()->with(['images', 'category']);

        // Resolve the selected category model (if any) for breadcrumbs / page title
        $selectedCategory = null;

        if ($request->filled('category')) {
            $category = Category::where('slug', $request->category)->first();
            if ($category) {
                $selectedCategory = $category;
                $childIds = $category->children()->pluck('id')->push($category->id);
                $query->whereIn('category_id', $childIds);
            }
        }

        if ($request->filled('q')) {
            $query->search($request->q);
        }

        // Sort — keys match the template sort menu data-sort values
        if ($request->filled('sort')) {
            match ($request->sort) {
                'cheap'  => $query->orderBy('price'),
                'exp'    => $query->orderByDesc('price'),
                'new'    => $query->latest(),
                'rating' => $query->orderByDesc('views_count'), // approximate popularity/rating
                'pop'    => $query->popular(),
                // Legacy keys kept for backwards compat
                'price_asc'  => $query->orderBy('price'),
                'price_desc' => $query->orderByDesc('price'),
                'newest'     => $query->latest(),
                'popular'    => $query->popular(),
                default      => $query->latest(),
            };
        } else {
            $query->popular();
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->boolean('free_delivery')) {
            $query->where('free_delivery', true);
        }

        if ($request->boolean('on_sale')) {
            $query->onSale();
        }

        // Brand filter — accepts comma-separated brand slugs
        if ($request->filled('brand')) {
            $brandSlugs = array_filter(explode(',', $request->input('brand')));
            if (! empty($brandSlugs)) {
                $brandIds = Brand::whereIn('slug', $brandSlugs)->pluck('id');
                if ($brandIds->isNotEmpty()) {
                    $query->whereIn('brand_id', $brandIds);
                }
            }
        }

        // Size filter — matches against the specifications JSON column
        if ($request->filled('size')) {
            $sizes = array_filter(explode(',', $request->input('size')));
            if (! empty($sizes)) {
                $query->where(function ($q) use ($sizes) {
                    foreach ($sizes as $size) {
                        $q->orWhere('specifications', 'like', '%' . $size . '%');
                    }
                });
            }
        }

        // Surface filter — matches against the specifications JSON column
        if ($request->filled('surface')) {
            $surfaces = array_filter(explode(',', $request->input('surface')));
            if (! empty($surfaces)) {
                $query->where(function ($q) use ($surfaces) {
                    foreach ($surfaces as $surface) {
                        $q->orWhere('specifications', 'like', '%' . $surface . '%');
                    }
                });
            }
        }

        // In-stock only filter
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }

        $products = $query->paginate(24)->withQueryString();

        // Actual price range from visible/approved products (for slider defaults)
        $priceRange = [
            'min' => (int) Product::visible()->approved()->min('price'),
            'max' => (int) Product::visible()->approved()->max('price'),
        ];
        // Guard against empty catalogue
        if ($priceRange['max'] === 0) {
            $priceRange = ['min' => 0, 'max' => 100];
        }

        // Featured specialists (same query as product page)
        $featuredSpecialists = SpecialistProfile::where('is_featured', true)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with('user')
            ->take(4)
            ->get();

        // Latest approved reviews (across all products)
        $latestReviews = Review::where('status', 'approved')
            ->with('user')
            ->latest()
            ->take(5)
            ->get();

        $filterBrands = Brand::active()->showInFilters()->ordered()
            ->withCount(['products' => fn ($q) => $q->visible()->approved()])
            ->get();

        return view('pages.catalog', compact(
            'categories',
            'products',
            'selectedCategory',
            'priceRange',
            'featuredSpecialists',
            'latestReviews',
            'filterBrands',
        ));
    }
}
