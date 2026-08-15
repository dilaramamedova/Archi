<?php

namespace App\Support;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Single source of truth for the customer's catalog navigation.
 *
 * The classifier (Excel sheet 7 "Навигация_меню" + recommendation R8) groups the
 * twelve sections (Bölmə) into FIVE clusters. Every consumer — the catalog left
 * rail, the header "Kataloq" mega panel, the mobile drawer — reads the cluster
 * definition from here; it must never be copied into a controller or a view.
 *
 * The cluster key is a translation key inside the `catalog-classifier` group, so
 * all three locales come from the database translations (TranslationsSeeder).
 * The values are section `sort_order`s (1-12), which is what the classifier
 * import assigns to the root categories — slugs may be edited in admin, the
 * order is structural.
 */
class CatalogNavigation
{
    /** translation key (group 'catalog-classifier') => section sort_orders */
    public const CLUSTERS = [
        'clusters.construction' => [1, 2, 3, 4],
        'clusters.engineering' => [5, 6, 7],
        'clusters.openings' => [8, 9],
        'clusters.furniture' => [10, 11],
        'clusters.outdoor' => [12],
    ];

    /**
     * Active root sections with their active groups — the shape every cluster
     * consumer needs.
     */
    public static function sections(): EloquentCollection
    {
        return VersionedCache::remember(
            VersionedCache::CATALOG,
            'sections:'.app()->getLocale(),
            VersionedCache::TTL_STRUCTURAL,
            fn () => Category::roots()->active()->ordered()
                ->with(['children' => fn ($q) => $q->active()->ordered()])
                ->get(),
        );
    }

    /**
     * Group already-loaded sections into the five clusters.
     *
     * @return Collection<int, array{key: string, label: string, sections: Collection}>
     */
    public static function clusters(Collection $sections): Collection
    {
        return collect(self::CLUSTERS)
            ->map(fn (array $orders, string $key) => [
                'key' => $key,
                'label' => self::label($key),
                'sections' => $sections->filter(fn ($s) => in_array((int) $s->sort_order, $orders, true))->values(),
            ])
            ->values();
    }

    /**
     * Group the admin-managed "Header — Mega Kataloq" cards into the same five
     * clusters, by resolving each card's `?section=<slug>` back to a section.
     *
     * Cards that point at anything else (a custom admin card, or a legacy
     * `?category=` link on an installation where the classifier was never
     * imported) are returned in a trailing block with a null label, so nothing
     * an admin adds can silently disappear from the menu. When NO card maps to a
     * cluster the result is empty and callers fall back to the flat card grid.
     *
     * @param  Collection<int, MenuItem>  $items
     * @return Collection<int, array{key: ?string, label: ?string, items: Collection}>
     */
    public static function clusterMenuItems(Collection $items): Collection
    {
        if ($items->isEmpty()) {
            return collect();
        }

        // slug => sort_order for the live sections (one query for the whole menu)
        $orderBySlug = Category::roots()->active()->ordered()->pluck('sort_order', 'slug');

        $buckets = array_fill_keys(array_keys(self::CLUSTERS), []);
        $ungrouped = [];

        foreach ($items as $item) {
            $slug = self::sectionSlug($item->url);
            $key = $slug !== null && isset($orderBySlug[$slug])
                ? self::clusterKeyFor((int) $orderBySlug[$slug])
                : null;

            if ($key === null) {
                $ungrouped[] = $item;

                continue;
            }

            $buckets[$key][] = $item;
        }

        $blocks = collect();
        foreach ($buckets as $key => $bucket) {
            if ($bucket === []) {
                continue;
            }
            $blocks->push(['key' => $key, 'label' => self::label($key), 'items' => collect($bucket)]);
        }

        // Nothing recognised — let the caller keep its flat rendering.
        if ($blocks->isEmpty()) {
            return collect();
        }

        if ($ungrouped !== []) {
            $blocks->push(['key' => null, 'label' => null, 'items' => collect($ungrouped)]);
        }

        return $blocks;
    }

    /**
     * Product counts rolled up over the classifier tree, in two aggregate
     * queries instead of one COUNT per row. A product is attributed to its class
     * when it has one (sub_category_id) and to its raw category otherwise — so
     * nothing is counted twice when both columns are set. A section's number is
     * the sum of its own, its groups' and its classes' products.
     *
     * Shared by the catalog left rail and the homepage category tiles: a plain
     * withCount('products') on a section returns 0, because the classifier
     * attaches products to GROUPS, never to the section itself.
     *
     * @param  Collection<int, Category>  $sections  root categories
     * @return array{0: array<int,int>, 1: array<int,int>, 2: array<int,int>} [sectionId => n, groupId => n, classId => n]
     */
    public static function productCounts(Collection $sections): array
    {
        if ($sections instanceof EloquentCollection) {
            $sections->loadMissing(['children' => fn ($q) => $q->active()->ordered()]);
        }

        // Two aggregates over the whole products table plus the full class
        // index — cheap on a laptop, and the single most expensive thing on the
        // catalog and homepage once the table is large. Nobody edits these
        // numbers directly, so a short TTL is the right invalidation: a product
        // approved now shows up in the sidebar counts within minutes. Keyed by
        // the section ids so a different subtree gets its own entry.
        $cacheKey = 'counts:'.md5($sections->pluck('id')->sort()->implode(','));

        [$byCategory, $byClass, $classIndex] = VersionedCache::remember(
            VersionedCache::CATALOG,
            $cacheKey,
            VersionedCache::TTL_AGGREGATE,
            fn () => [
                Product::visible()->approved()->whereNull('sub_category_id')
                    ->groupBy('category_id')->selectRaw('category_id, count(*) as c')->pluck('c', 'category_id'),
                Product::visible()->approved()->whereNotNull('sub_category_id')
                    ->groupBy('sub_category_id')->selectRaw('sub_category_id, count(*) as c')->pluck('c', 'sub_category_id'),
                SubCategory::get(['id', 'category_id']), // class id -> group id
            ],
        );

        $classCounts = [];
        $groupCounts = [];
        $sectionCounts = [];

        foreach ($classIndex as $class) {
            $classCounts[$class->id] = (int) ($byClass[$class->id] ?? 0);
        }

        // Indexed once instead of re-scanning the whole class list per group —
        // the inner where() made this quadratic in the number of classes.
        $classesByGroup = $classIndex->groupBy('category_id');

        foreach ($sections as $section) {
            $total = (int) ($byCategory[$section->id] ?? 0);
            foreach ($section->children as $group) {
                $groupTotal = (int) ($byCategory[$group->id] ?? 0);
                foreach ($classesByGroup->get($group->id, collect()) as $class) {
                    $groupTotal += $classCounts[$class->id];
                }
                $groupCounts[$group->id] = $groupTotal;
                $total += $groupTotal;
            }
            $sectionCounts[$section->id] = $total;
        }

        return [$sectionCounts, $groupCounts, $classCounts];
    }

    /**
     * Set a `products_count` attribute on each section, rolled up over the whole
     * classifier subtree — a drop-in replacement for withCount('products') on
     * root categories (homepage tiles).
     *
     * @param  Collection<int, Category>  $sections
     */
    public static function withRolledUpCounts(Collection $sections): Collection
    {
        [$sectionCounts] = self::productCounts($sections);

        return $sections->each(function ($section) use ($sectionCounts) {
            $section->products_count = $sectionCounts[$section->id] ?? 0;
        });
    }

    private static function clusterKeyFor(int $sortOrder): ?string
    {
        foreach (self::CLUSTERS as $key => $orders) {
            if (in_array($sortOrder, $orders, true)) {
                return $key;
            }
        }

        return null;
    }

    private static function label(string $key): string
    {
        $label = t('catalog-classifier.'.$key);

        return is_string($label) ? $label : $key;
    }

    /** `/catalog?section=dosemeler` => `dosemeler`; anything else => null. */
    private static function sectionSlug(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (! is_string($query) || $query === '') {
            return null;
        }

        parse_str($query, $params);
        $slug = $params['section'] ?? null;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }
}
