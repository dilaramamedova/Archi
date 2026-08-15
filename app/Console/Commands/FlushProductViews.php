<?php

namespace App\Console\Commands;

use App\Support\ProductViewCounter;
use Illuminate\Console\Command;

/**
 * Writes the buffered product view counts to the database.
 *
 * Scheduled every minute (routes/console.php). Without a running scheduler the
 * counts accumulate in the cache and views_count simply stops moving — the site
 * keeps working, the popularity ordering just goes stale, so this is safe to
 * forget on a staging box and important to enable in production.
 */
class FlushProductViews extends Command
{
    protected $signature = 'products:flush-views';

    protected $description = 'Write buffered product view counts to the database';

    public function handle(): int
    {
        $updated = ProductViewCounter::flush();

        $this->info($updated === 0 ? 'No buffered views.' : "Flushed views for {$updated} products.");

        return self::SUCCESS;
    }
}
