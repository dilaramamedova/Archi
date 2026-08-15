<?php

namespace App\Observers;

use App\Jobs\RefreshProductSearchContext;
use App\Models\Brand;
use App\Models\Category;
use App\Models\SearchSynonym;
use App\Models\SubCategory;
use App\Support\VersionedCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates the storefront caches when an admin edits the content that feeds
 * them. Registered per model in AppServiceProvider, where the model-to-domain
 * mapping lives, so this class stays a single generic listener rather than one
 * observer per resource.
 *
 * Deliberately NOT registered on Product: a product page view increments
 * views_count, which would otherwise bump the catalog version on every single
 * request and make the cache strictly worse than none. Product-derived caches
 * (counts, price range) carry a short TTL instead.
 */
class ContentCacheObserver
{
    /** @var array<class-string, list<string>> filled by AppServiceProvider */
    public static array $domains = [];

    public function saved(Model $model): void
    {
        $this->invalidate($model);
        $this->refreshSearchContext($model);
    }

    public function deleted(Model $model): void
    {
        $this->invalidate($model);
    }

    private function invalidate(Model $model): void
    {
        foreach (self::$domains[$model::class] ?? [] as $domain) {
            VersionedCache::bump($domain);
        }
    }

    /**
     * A renamed brand, category or product class changes what its products
     * should be findable by, and that text is denormalized onto every one of
     * them (products.search_context). Only a name change matters — reordering
     * or hiding a brand leaves the searchable text alone — so the job is
     * dispatched on that alone, because it can touch thousands of rows.
     */
    private function refreshSearchContext(Model $model): void
    {
        // A synonym is the whole point of the context — "kvars vinil" has to
        // find the Laminat class's products — so any write refreshes them, not
        // just a rename.
        if ($model instanceof SearchSynonym) {
            RefreshProductSearchContext::dispatch('sub_category_id', (int) $model->sub_category_id);

            return;
        }

        $column = match ($model::class) {
            Brand::class => 'brand_id',
            Category::class => 'category_id',
            SubCategory::class => 'sub_category_id',
            default => null,
        };

        if ($column === null || ! $model->wasChanged('name')) {
            return;
        }

        RefreshProductSearchContext::dispatch($column, $model->getKey());
    }
}
