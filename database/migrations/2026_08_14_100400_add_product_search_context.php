<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Folds a product's brand, category, class and class synonyms into the product
 * row so search is a single MATCH with no OR branches.
 *
 * Product search has to match on more than the product's own text: typing a
 * brand or a category name should find its products, and a colloquial phrase
 * ("kvars vinil") should resolve through search_synonyms to a product class.
 * Those live in other tables, so the query used to OR the full-text predicate
 * with `sub_category_id IN (…) OR brand_id IN (…) OR category_id IN (…)`.
 *
 * One OR against a MATCH and MySQL abandons every index. Measured on 60k rows:
 * MATCH alone 0.7 ms, the same MATCH with one OR-ed IN 66 ms, and rewriting the
 * branches as a UNION subquery made it worse still (695 ms — the optimizer
 * turned it into a per-row dependent subquery).
 *
 * `search_context` is a plain column, not generated, because a generated column
 * cannot read another table. App\Support\ProductSearchContext builds it and
 * App\Observers\ProductObserver keeps it current; renaming a brand or class
 * refreshes its products through RefreshProductSearchContext. The FULLTEXT
 * index now spans both columns, and MATCH must name both to use it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! Schema::hasColumn('products', 'search_context')) {
            DB::statement('ALTER TABLE products ADD COLUMN search_context TEXT NULL AFTER search_text');
        }

        if ($this->hasIndex('products', 'products_search_fulltext')) {
            DB::statement('ALTER TABLE products DROP INDEX products_search_fulltext');
        }

        if (! $this->hasIndex('products', 'products_search_fulltext')) {
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX products_search_fulltext (search_text, search_context)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if ($this->hasIndex('products', 'products_search_fulltext')) {
            DB::statement('ALTER TABLE products DROP INDEX products_search_fulltext');
        }

        DB::statement('ALTER TABLE products ADD FULLTEXT INDEX products_search_fulltext (search_text)');

        if (Schema::hasColumn('products', 'search_context')) {
            DB::statement('ALTER TABLE products DROP COLUMN search_context');
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $name);
    }
};
