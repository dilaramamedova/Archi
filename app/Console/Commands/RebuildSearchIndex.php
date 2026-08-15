<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ProductSearchContext;
use Illuminate\Console\Command;

/**
 * Rebuilds products.search_context for the whole catalog.
 *
 * Needed once after deploying the search_context migration, and any time the
 * classifier is re-imported — the seeder writes classes and synonyms with raw
 * inserts, which bypass the model events that normally keep the column fresh.
 *
 *   php artisan search:rebuild
 */
class RebuildSearchIndex extends Command
{
    protected $signature = 'search:rebuild {--chunk=500 : Products per batch}';

    protected $description = 'Rebuild the denormalized search context on every product';

    public function handle(): int
    {
        $total = Product::count();
        $this->info("Rebuilding search context for {$total} products…");

        $bar = $this->output->createProgressBar($total);

        $updated = ProductSearchContext::refresh(
            Product::query(),
            max(50, (int) $this->option('chunk')),
            fn (int $done) => $bar->advance($done),
        );

        $bar->finish();
        $this->newLine(2);
        $this->info("Updated {$updated} products.");

        return self::SUCCESS;
    }
}
