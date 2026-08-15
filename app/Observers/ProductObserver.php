<?php

namespace App\Observers;

use App\Models\Product;
use App\Support\ProductSearchContext;
use Illuminate\Support\Facades\DB;

/**
 * Keeps products.search_context in step with the product's brand, category and
 * class. `search_text` needs no help — it is a generated column and MySQL
 * recomputes it on write — but the context spans other tables, so it is
 * rebuilt here whenever one of those foreign keys moves.
 */
class ProductObserver
{
    public function created(Product $product): void
    {
        $this->refresh($product);
    }

    public function updated(Product $product): void
    {
        if (! $product->wasChanged(['brand_id', 'category_id', 'sub_category_id'])) {
            return;
        }

        $this->refresh($product);
    }

    private function refresh(Product $product): void
    {
        // withoutRelations() first: the instance being saved may still carry the
        // relation it had BEFORE the foreign key moved, and loadMissing() would
        // happily keep that stale object — so a product moved to a new class
        // would be indexed under its old one. Stripping the relations forces
        // them to be read against the current keys.
        $context = ProductSearchContext::for($product->withoutRelations());

        // Written through the query builder so it does not re-enter this
        // observer or bump updated_at.
        DB::table('products')
            ->where('id', $product->id)
            ->update(['search_context' => $context]);
    }
}
