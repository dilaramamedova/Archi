<?php

namespace App\Http\Controllers;

use App\Enums\AttributeFieldType;
use App\Enums\UserStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\SpecialistProfile;
use App\Models\SubCategory;
use App\Services\SearchService;
use App\Support\CatalogNavigation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CatalogController extends Controller
{
    /**
     * The customer's five navigation clusters live in App\Support\CatalogNavigation
     * — the catalog rail, the header mega panel and the mobile drawer all read
     * that one definition.
     */
    public function index(Request $request)
    {
        $query = Product::visible()->approved()->with(['images', 'category']);

        // ── Classifier drill-down: ?class= > ?group= > ?section= ──────────
        $selectedSection = null; // root category (Bölmə)
        $selectedGroup = null;   // child category (group)
        $selectedClass = null;   // SubCategory (product class)

        if ($request->filled('class')) {
            $selectedClass = SubCategory::active()->where('slug', $request->input('class'))
                ->with('category.parent')->first();
            if ($selectedClass) {
                $selectedGroup = $selectedClass->category;
                $selectedSection = $selectedGroup?->parent;
                $query->where('sub_category_id', $selectedClass->id);
            }
        } elseif ($request->filled('group')) {
            $selectedGroup = Category::active()->whereNotNull('parent_id')
                ->where('slug', $request->input('group'))->with('parent')->first();
            if ($selectedGroup) {
                $selectedSection = $selectedGroup->parent;
                $classIds = SubCategory::where('category_id', $selectedGroup->id)->pluck('id');
                $groupId = $selectedGroup->id;
                $query->where(fn ($q) => $q->where('category_id', $groupId)->orWhereIn('sub_category_id', $classIds));
            }
        } elseif ($request->filled('section')) {
            $selectedSection = Category::active()->roots()->where('slug', $request->input('section'))->first();
            if ($selectedSection) {
                $groupIds = $selectedSection->children()->pluck('id');
                $classIds = SubCategory::whereIn('category_id', $groupIds)->pluck('id');
                $categoryIds = $groupIds->push($selectedSection->id);
                $query->where(fn ($q) => $q->whereIn('category_id', $categoryIds)->orWhereIn('sub_category_id', $classIds));
            }
        }

        // Legacy ?category= filter — kept for old links (footer, home, breadcrumbs).
        // Only ACTIVE categories may filter: the seven retired root categories
        // (kafel, dam-ortukleri, laminat, elektrik, santexnika, kerpic, sement)
        // were deactivated by the classifier import, and an old link to one of
        // them used to render a 200 page titled with the retired name and zero
        // products. An unknown slug has always fallen through to the unfiltered
        // catalog, so a retired slug now behaves identically instead of
        // dead-ending on a 404 — old bookmarks and external links still land on
        // a useful page.
        $selectedCategory = null;
        if (! $selectedSection && ! $selectedGroup && ! $selectedClass && $request->filled('category')) {
            $category = Category::active()->where('slug', $request->category)->first();
            if ($category) {
                $selectedCategory = $category;
                $childIds = $category->children()->pluck('id')->push($category->id);
                $query->whereIn('category_id', $childIds);
            }
        }

        // ── Dynamic attribute filters (class pages only) ───────────────────
        $filterGroups = ['top' => collect(), 'secondary' => collect(), 'advanced' => collect()];
        $activeAttrOptions = []; // attr slug => [option slugs] (or ['1'] for boolean)
        $activeAttrRanges = [];  // attr slug => ['min' => ?, 'max' => ?]

        if ($selectedClass) {
            $classAttributes = $selectedClass->attributes()->with('options')->get()
                ->filter(fn ($a) => $a->is_active && $a->pivot->is_filterable);

            foreach ($classAttributes as $attribute) {
                $priority = $attribute->pivot->filter_priority?->value ?? 'advanced';
                $filterGroups[$priority] ??= collect();
                $filterGroups[$priority]->push($attribute);
            }

            $this->applyAttributeFilters($query, $request, $classAttributes, $activeAttrOptions, $activeAttrRanges);
        }

        if ($request->filled('q')) {
            $query->search($request->q);

            // Relevance first, but never override an explicit user sort.
            if (! $request->filled('sort')) {
                SearchService::orderProductsByRelevance($query, (string) $request->q);
            }
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
                default => $query->latest(),
            };
        } else {
            // Default listing: products an admin positioned by drag & drop in the
            // panel come first, in that order; everything still at 0 keeps the old
            // popularity ordering, so the catalog only changes once someone drags.
            $query->orderByRaw('sort_order = 0')->orderBy('sort_order')->popular();
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

        // Home-page "Ətraflı bax" destinations: only SALE-status products…
        if ($request->boolean('sale')) {
            $query->sale();
        }

        // …or only featured ("seçilmiş") products.
        if ($request->boolean('featured')) {
            $query->featured();
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

        // ── Left rail: 5 clusters -> 12 sections -> groups -> classes ─────
        $sections = CatalogNavigation::sections();

        [$sectionCounts, $groupCounts, $classCounts] = CatalogNavigation::productCounts($sections);

        $clusters = CatalogNavigation::clusters($sections);

        // Classes are listed only for the group the visitor drilled into.
        $groupClasses = $selectedGroup
            ? SubCategory::active()->ordered()->where('category_id', $selectedGroup->id)->get()
            : collect();

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
            ->with(['user', 'specialty'])
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
            'products',
            'selectedCategory',
            'selectedSection',
            'selectedGroup',
            'selectedClass',
            'clusters',
            'groupClasses',
            'sectionCounts',
            'groupCounts',
            'classCounts',
            'filterGroups',
            'activeAttrOptions',
            'activeAttrRanges',
            'priceRange',
            'featuredSpecialists',
            'latestReviews',
            'filterBrands',
        ));
    }

    /**
     * Apply the class's filterable attributes from the request to the product
     * query. Contract: AND between attributes, OR within one attribute's
     * options (multiselect values are one row per option, so whereIn covers
     * both dropdown and multiselect). Numeric attributes filter value_numeric;
     * range attributes match when the stored min–max window overlaps the
     * requested one. Only attributes that belong to the selected class and are
     * marked filterable are honoured — anything else in the URL is ignored.
     *
     * URL contract:
     *   attr[<attr-slug>]=<option-slug>,<option-slug>   dropdown / multiselect
     *   attr[<attr-slug>]=1                             boolean
     *   attr_min[<attr-slug>]= / attr_max[<attr-slug>]= numeric / decimal / range
     */
    private function applyAttributeFilters(
        $query,
        Request $request,
        Collection $classAttributes,
        array &$activeAttrOptions,
        array &$activeAttrRanges,
    ): void {
        $attrParam = (array) $request->input('attr', []);
        $minParam = (array) $request->input('attr_min', []);
        $maxParam = (array) $request->input('attr_max', []);

        foreach ($classAttributes as $attribute) {
            $slug = $attribute->slug;

            if ($attribute->field_type->hasOptions()) {
                $optionSlugs = array_values(array_filter(explode(',', (string) ($attrParam[$slug] ?? ''))));
                if ($optionSlugs === []) {
                    continue;
                }
                $optionIds = $attribute->options->whereIn('slug', $optionSlugs)->pluck('id');
                if ($optionIds->isEmpty()) {
                    continue;
                }
                $activeAttrOptions[$slug] = $optionSlugs;
                $query->whereHas('attributeValues', fn ($q) => $q
                    ->where('attribute_id', $attribute->id)
                    ->whereIn('attribute_option_id', $optionIds));

                continue;
            }

            if ($attribute->field_type === AttributeFieldType::Boolean) {
                if (($attrParam[$slug] ?? null) !== '1') {
                    continue;
                }
                $activeAttrOptions[$slug] = ['1'];
                $query->whereHas('attributeValues', fn ($q) => $q
                    ->where('attribute_id', $attribute->id)
                    ->where('value_bool', true));

                continue;
            }

            if (in_array($attribute->field_type, [AttributeFieldType::Numeric, AttributeFieldType::Decimal, AttributeFieldType::Range], true)) {
                $min = $minParam[$slug] ?? null;
                $max = $maxParam[$slug] ?? null;
                $min = is_numeric($min) ? (float) $min : null;
                $max = is_numeric($max) ? (float) $max : null;
                if ($min === null && $max === null) {
                    continue;
                }
                $activeAttrRanges[$slug] = ['min' => $min, 'max' => $max];
                $query->whereHas('attributeValues', function ($q) use ($attribute, $min, $max) {
                    $q->where('attribute_id', $attribute->id)->where(function ($v) use ($min, $max) {
                        $v->where(function ($n) use ($min, $max) {
                            $n->whereNotNull('value_numeric');
                            if ($min !== null) {
                                $n->where('value_numeric', '>=', $min);
                            }
                            if ($max !== null) {
                                $n->where('value_numeric', '<=', $max);
                            }
                        })->orWhere(function ($r) use ($min, $max) {
                            $r->whereNotNull('value_min');
                            if ($max !== null) {
                                $r->where('value_min', '<=', $max);
                            }
                            if ($min !== null) {
                                $r->where('value_max', '>=', $min);
                            }
                        });
                    });
                });
            }
        }
    }
}
