<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Builds the `products.search_context` value: everything about a product that
 * lives in another table but should still be findable by typing it — the brand,
 * the category and group names, the product class, and the colloquial phrases
 * seeded into search_synonyms for that class.
 *
 * Written folded and lowercased, exactly like the generated `search_text`
 * column, so one FULLTEXT index spans both and a query needs no per-row string
 * work. See the 2026_08_14_100400 migration for why this is denormalized rather
 * than joined at query time.
 */
class ProductSearchContext
{
    /** Mirrors App\Services\SearchService::AZERI_FOLD. */
    private const FOLD = [
        'ə' => 'e', 'ö' => 'o', 'ü' => 'u', 'ç' => 'c',
        'ş' => 's', 'ğ' => 'g', 'ı' => 'i',
    ];

    /**
     * Context string for one product. Loads the relations it needs if they are
     * not already there, so it is safe to call from an observer.
     */
    public static function for(Product $product): string
    {
        $product->loadMissing(['brand', 'category.parent', 'subCategory']);

        $parts = [
            self::names($product->brand),
            self::names($product->category),
            self::names($product->category?->parent),
            self::names($product->subCategory),
        ];

        if ($product->sub_category_id) {
            $parts[] = DB::table('search_synonyms')
                ->where('sub_category_id', $product->sub_category_id)
                ->pluck('phrase')
                ->implode(' ');
        }

        return self::fold(implode(' ', array_filter($parts)));
    }

    /**
     * Rebuild the column for a set of products, in chunks. Used by the backfill
     * command and by RefreshProductSearchContext when a brand or class is
     * renamed and every product under it goes stale at once.
     *
     * @param  Builder<Product>  $query
     * @param  (callable(int): void)|null  $onChunk  receives the size of each finished chunk
     */
    public static function refresh($query, int $chunk = 500, ?callable $onChunk = null): int
    {
        $updated = 0;

        $query->with(['brand', 'category.parent', 'subCategory'])
            ->chunkById($chunk, function ($products) use (&$updated, $onChunk) {
                foreach ($products as $product) {
                    // Query builder, not save(): this must not fire model
                    // events (the observer that called us) or touch updated_at.
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['search_context' => self::for($product)]);

                    $updated++;
                }

                if ($onChunk !== null) {
                    $onChunk($products->count());
                }
            });

        return $updated;
    }

    /**
     * All locale variants of a translatable `name`, so a Russian brand name is
     * findable from the Russian interface and from the Azerbaijani one.
     */
    private static function names(mixed $model): string
    {
        if ($model === null) {
            return '';
        }

        $raw = $model->getAttributes()['name'] ?? null;

        if ($raw === null) {
            return '';
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded)
            ? implode(' ', array_filter($decoded, 'is_string'))
            : (string) $raw;
    }

    private static function fold(string $text): string
    {
        return strtr(mb_strtolower(str_replace('İ', 'i', $text)), self::FOLD);
    }
}
