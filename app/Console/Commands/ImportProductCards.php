<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ProductCard\CardImporter;
use Illuminate\Console\Command;

/**
 * (Re)loads a section's product cards from database/seeders/data/product_cards/<file>.php.
 * Safe to run again after editing the file — see docs/product-cards.md.
 */
class ImportProductCards extends Command
{
    protected $signature = 'product-cards:import {file : Card file name, e.g. doseme}';

    protected $description = 'Load category product cards (parameters per class) from a data file';

    public function handle(CardImporter $importer): int
    {
        foreach ($importer->importFile((string) $this->argument('file')) as $line) {
            $this->line($line);
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
