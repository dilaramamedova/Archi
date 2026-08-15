<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Covering index for the catalog paginator's COUNT.
 *
 * Fetching a page is already fast — the sort index lets MySQL stop after 24
 * rows — but `paginate()` also asks for the total, and a COUNT cannot stop
 * early. With only (is_visible, is_approved) to work with, counting a filtered
 * catalog meant reading 29k full rows off the clustered index: 177 ms for a
 * page whose 24 products took 4 ms.
 *
 * Adding the two boolean filters that appear on the sidebar turns that into an
 * index-only scan over a much narrower structure. Range filters (price, and
 * `stock > 0`) can only ever be the last column of a usable index, which is why
 * stock sits at the end and price keeps its own composite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || $this->hasIndex('products', 'p_live_filter_count_index')) {
            return;
        }

        DB::statement(
            'CREATE INDEX p_live_filter_count_index ON products
             (is_visible, is_approved, free_delivery, is_sale, stock)'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql' && $this->hasIndex('products', 'p_live_filter_count_index')) {
            DB::statement('DROP INDEX p_live_filter_count_index ON products');
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $name);
    }
};
