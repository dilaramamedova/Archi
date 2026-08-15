<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Cache helper with version-stamped keys.
 *
 * Cache *tags* would be the obvious tool for "drop everything catalog-related",
 * but they only exist on the Redis and Memcached drivers. This works on every
 * driver — including the `file` and `database` stores a first deployment may
 * run on — by folding a version counter into the key: bumping the counter makes
 * every old key unreachable at once, and the orphans fall out on their own TTL.
 *
 * Domains are coarse on purpose. `chrome` is the header/footer furniture,
 * `catalog` the classifier tree, `home` the curated homepage blocks. An editor
 * saving a menu item must invalidate the header immediately; a product's
 * views_count ticking up must not invalidate anything, which is why product
 * writes bump nothing and the count caches instead carry a short TTL.
 */
class VersionedCache
{
    public const CHROME = 'chrome';

    public const CATALOG = 'catalog';

    public const HOME = 'home';

    /** Structural data changes rarely; a day is safe because edits bump the version. */
    public const TTL_STRUCTURAL = 86400;

    /** Aggregates nobody edits directly — allowed to drift for a few minutes. */
    public const TTL_AGGREGATE = 600;

    public static function remember(string $domain, string $key, int $ttl, Closure $callback): mixed
    {
        return Cache::remember(self::key($domain, $key), $ttl, $callback);
    }

    /**
     * Invalidate every key in a domain. Called from ContentCacheObserver when an
     * admin edits something the storefront caches.
     */
    public static function bump(string $domain): void
    {
        $versionKey = "cache_version:{$domain}";

        // increment() only works on an existing integer entry; seed it first.
        if (Cache::get($versionKey) === null) {
            Cache::forever($versionKey, 1);

            return;
        }

        Cache::increment($versionKey);
    }

    public static function flushAll(): void
    {
        foreach ([self::CHROME, self::CATALOG, self::HOME] as $domain) {
            self::bump($domain);
        }
    }

    private static function key(string $domain, string $key): string
    {
        return "{$domain}:v".self::version($domain).":{$key}";
    }

    private static function version(string $domain): int
    {
        return (int) Cache::rememberForever("cache_version:{$domain}", fn () => 1);
    }
}
