<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Buffers product view counts and writes them to the database in batches.
 *
 * Every product page used to run `UPDATE products SET views_count =
 * views_count + 1` inline. On a quiet site that is invisible; on a launch day
 * it is a write and a row lock on the single hottest row in the table for every
 * visitor looking at the same product, and views_count is part of the default
 * catalog ordering, so each of those writes also dirties an index the catalog
 * reads constantly.
 *
 * Views are counted in the cache and flushed once a minute by
 * `php artisan products:flush-views` (scheduled in routes/console.php). The
 * counter is intentionally approximate — losing a handful of views to a cache
 * restart costs nothing, and the alternative costs write throughput.
 *
 * The cache store should be Redis in production: on the `file` store this is
 * still a write per view, just a cheaper one that never blocks a database row.
 */
class ProductViewCounter
{
    private const PENDING_KEY = 'product_views:pending';

    private const COUNT_PREFIX = 'product_views:count:';

    public static function record(int $productId): void
    {
        $key = self::COUNT_PREFIX.$productId;

        // increment() only operates on an existing integer entry.
        if (Cache::add($key, 1, now()->addDay())) {
            self::markPending($productId);

            return;
        }

        Cache::increment($key);
        self::markPending($productId);
    }

    /**
     * Apply every buffered delta and clear the buffer.
     *
     * @return int number of products updated
     */
    public static function flush(): int
    {
        $pending = Cache::get(self::PENDING_KEY, []);

        if ($pending === []) {
            return 0;
        }

        // Cleared first: a view arriving during the flush re-registers its id
        // and is picked up next minute, rather than being dropped here.
        Cache::forget(self::PENDING_KEY);

        $updated = 0;

        foreach (array_keys($pending) as $productId) {
            $key = self::COUNT_PREFIX.$productId;
            $delta = (int) Cache::pull($key, 0);

            if ($delta <= 0) {
                continue;
            }

            // Query builder, so no model events fire and updated_at is left
            // alone — a view is not an edit.
            $updated += DB::table('products')->where('id', $productId)->increment('views_count', $delta);
        }

        return $updated;
    }

    private static function markPending(int $productId): void
    {
        $pending = Cache::get(self::PENDING_KEY, []);

        if (isset($pending[$productId])) {
            return;
        }

        $pending[$productId] = true;
        Cache::put(self::PENDING_KEY, $pending, now()->addDay());
    }
}
