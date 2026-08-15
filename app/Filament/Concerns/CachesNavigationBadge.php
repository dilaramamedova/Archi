<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * Caches a resource's sidebar badge count.
 *
 * Filament asks every registered resource for its badge while rendering the
 * navigation, so the counts run on *every* admin page load — one COUNT per
 * badged resource, against the largest tables in the schema. They exist to draw
 * attention to a queue ("3 waiting"), which a minute of staleness does not
 * harm, so they are cached for a minute instead of recounted per page.
 */
trait CachesNavigationBadge
{
    /**
     * @param  Closure(): int  $count
     */
    protected static function cachedBadge(string $key, Closure $count, int $ttl = 60): ?string
    {
        $value = Cache::remember("filament:badge:{$key}", $ttl, $count);

        return $value > 0 ? (string) $value : null;
    }
}
