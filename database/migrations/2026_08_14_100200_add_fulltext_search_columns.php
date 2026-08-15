<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexed full-text search to replace the LIKE scan.
 *
 * Product search matched with
 *
 *   replace(replace(lower(cast(`name` as char) …))) LIKE '%term%'
 *
 * once per expanded synonym, ORed across name, description and two EXISTS
 * subqueries. A leading wildcard cannot use an index and the nested replace()
 * calls run per row, so every search — and every keystroke of the autocomplete,
 * which ran the same predicate four times — read the whole products table.
 *
 * `search_text` is a stored generated column holding all three locales folded
 * the way SearchService folds a query (lowercased, Azerbaijani diacritics
 * stripped), so the comparison needs no per-row string work at all, and the
 * FULLTEXT index over it turns the scan into an inverted-index lookup.
 *
 * Generated + FULLTEXT rather than an external engine on purpose: it needs no
 * extra service, no sync job and no eventual consistency, which matters while
 * the production stack is still undecided. SearchService keeps the LIKE path
 * for short tokens (below InnoDB's minimum token size) and can be pointed at
 * Meilisearch later without the callers changing.
 */
return new class extends Migration
{
    /** Same folding table as App\Services\SearchService::AZERI_FOLD. */
    private const FOLD = [
        'ə' => 'e', 'ö' => 'o', 'ü' => 'u', 'ç' => 'c',
        'ş' => 's', 'ğ' => 'g', 'ı' => 'i',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $this->addSearchColumn('products', 'search_text', $this->fold(
            "concat_ws(' ',
                json_unquote(json_extract(`name`, '$.az')),
                json_unquote(json_extract(`name`, '$.ru')),
                json_unquote(json_extract(`name`, '$.en')),
                json_unquote(json_extract(`description`, '$.az')),
                json_unquote(json_extract(`description`, '$.ru')),
                json_unquote(json_extract(`description`, '$.en')),
                `sku`
            )"
        ));

        $this->addSearchColumn('sub_categories', 'search_text', $this->fold(
            "concat_ws(' ',
                json_unquote(json_extract(`name`, '$.az')),
                json_unquote(json_extract(`name`, '$.ru')),
                json_unquote(json_extract(`name`, '$.en'))
            )"
        ));

        $this->addSearchColumn('blog_posts', 'search_text', $this->fold(
            "concat_ws(' ',
                json_unquote(json_extract(`title`, '$.az')),
                json_unquote(json_extract(`title`, '$.ru')),
                json_unquote(json_extract(`title`, '$.en')),
                json_unquote(json_extract(`excerpt`, '$.az')),
                json_unquote(json_extract(`excerpt`, '$.ru')),
                json_unquote(json_extract(`excerpt`, '$.en'))
            )"
        ));

        // Specialists search over plain columns plus a json skills array.
        $this->addSearchColumn('specialist_profiles', 'search_text', $this->fold(
            "concat_ws(' ', `craft`, cast(`skills` as char), `city`)"
        ));
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (['products', 'sub_categories', 'blog_posts', 'specialist_profiles'] as $table) {
            if (Schema::hasColumn($table, 'search_text')) {
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$table}_search_fulltext`");
                DB::statement("ALTER TABLE `{$table}` DROP COLUMN `search_text`");
            }
        }
    }

    private function addSearchColumn(string $table, string $column, string $expression): void
    {
        if (Schema::hasColumn($table, $column)) {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` ADD COLUMN `{$column}` TEXT AS ({$expression}) STORED");
        DB::statement("ALTER TABLE `{$table}` ADD FULLTEXT INDEX `{$table}_search_fulltext` (`{$column}`)");
    }

    /**
     * Wrap an expression in the lower() + replace() chain that folds Azerbaijani
     * diacritics, so a query typed either way matches. Computed once at write
     * time here instead of per row at read time.
     */
    private function fold(string $expression): string
    {
        $folded = "lower({$expression})";

        foreach (self::FOLD as $from => $to) {
            $folded = "replace({$folded}, '{$from}', '{$to}')";
        }

        return $folded;
    }
};
