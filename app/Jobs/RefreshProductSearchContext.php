<?php

namespace App\Jobs;

use App\Models\Product;
use App\Support\ProductSearchContext;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Rebuilds products.search_context for every product under a renamed brand,
 * category or product class.
 *
 * Renaming one brand can touch thousands of products, which is far too much to
 * do inside the admin request that saved it — hence a job. Until a queue worker
 * is running (QUEUE_CONNECTION=sync) this still executes inline; the fix is
 * infrastructure, not code, and the job is the seam that makes moving it off
 * the request a config change.
 */
class RefreshProductSearchContext implements ShouldQueue
{
    use Queueable;

    /**
     * @param  'brand_id'|'category_id'|'sub_category_id'  $column
     */
    public function __construct(
        public string $column,
        public int $id,
    ) {}

    public function handle(): void
    {
        $query = match ($this->column) {
            'brand_id' => Product::where('brand_id', $this->id),
            'sub_category_id' => Product::where('sub_category_id', $this->id),
            // A category rename affects products attached to it directly and,
            // when it is a section, everything under its groups' classes.
            'category_id' => Product::where('category_id', $this->id)
                ->orWhereHas('subCategory', fn ($q) => $q->where('category_id', $this->id)),
            default => null,
        };

        if ($query === null) {
            return;
        }

        ProductSearchContext::refresh($query);
    }
}
