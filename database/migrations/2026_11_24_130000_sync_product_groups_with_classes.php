<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A product's group is its class's group. Classes moved in the admin before
 * SubCategory::booted() kept their products in the old group (TZ Döşəmələr 24.11.2026:
 * class 31 moved 17 → 18, its product stayed in 17) — those products follow now.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->join('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->whereColumn('products.category_id', '!=', 'sub_categories.category_id')
            ->whereNotNull('sub_categories.category_id')
            ->update(['products.category_id' => DB::raw('sub_categories.category_id')]);
    }

    public function down(): void
    {
        // The old groups were wrong; nothing to restore.
    }
};
