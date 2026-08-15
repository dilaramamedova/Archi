<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Descending index columns for the catalog's mixed-direction sorts.
 *
 * MySQL can walk an ascending index backwards to satisfy a uniformly DESC
 * ORDER BY, so most sorts are already covered by the ascending composites.
 * It cannot do that when the directions are mixed — and the catalog's default
 * listing is exactly that:
 *
 *   ORDER BY sort_rank ASC, sort_order ASC, views_count DESC
 *
 * With an all-ascending index the optimizer gave up on it entirely (EXPLAIN
 * showed key=NULL, rows=58515, "Using filesort") and sorted the whole visible
 * catalog on every page. MySQL 8 supports per-column direction in an index, so
 * the index below matches the clause exactly and the LIMIT 24 can stop after
 * twenty-four index entries.
 *
 * The ascending p_live_rank_index is dropped: this supersedes it, and a
 * duplicate index is pure write cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! $this->hasIndex('products', 'p_live_sort_desc_index')) {
            DB::statement(
                'CREATE INDEX p_live_sort_desc_index ON products
                 (is_visible, is_approved, sort_rank ASC, sort_order ASC, views_count DESC)'
            );
        }

        if ($this->hasIndex('products', 'p_live_rank_index')) {
            DB::statement('DROP INDEX p_live_rank_index ON products');
        }

        // "Ən yüksək reytinq": rating_avg DESC, reviews_count DESC is uniform,
        // but pairing it with the visible/approved prefix keeps the whole
        // clause inside one index instead of filesorting the matches.
        if (! $this->hasIndex('products', 'p_live_rating_desc_index')) {
            DB::statement(
                'CREATE INDEX p_live_rating_desc_index ON products
                 (is_visible, is_approved, rating_avg DESC, reviews_count DESC)'
            );
        }

        if ($this->hasIndex('products', 'p_live_rating_index')) {
            DB::statement('DROP INDEX p_live_rating_index ON products');
        }

        // Newest-first is the fallback ordering on several pages.
        if (! $this->hasIndex('products', 'p_live_created_desc_index')) {
            DB::statement(
                'CREATE INDEX p_live_created_desc_index ON products
                 (is_visible, is_approved, created_at DESC)'
            );
        }

        if ($this->hasIndex('products', 'p_live_created_index')) {
            DB::statement('DROP INDEX p_live_created_index ON products');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['p_live_sort_desc_index', 'p_live_rating_desc_index', 'p_live_created_desc_index'] as $index) {
            if ($this->hasIndex('products', $index)) {
                DB::statement("DROP INDEX {$index} ON products");
            }
        }

        DB::statement('CREATE INDEX p_live_rank_index ON products (is_visible, is_approved, sort_rank, sort_order, views_count)');
        DB::statement('CREATE INDEX p_live_rating_index ON products (is_visible, is_approved, rating_avg)');
        DB::statement('CREATE INDEX p_live_created_index ON products (is_visible, is_approved, created_at)');
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $name);
    }
};
