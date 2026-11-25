<?php

namespace Database\Seeders;

use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Seeder;

/**
 * Category product cards (docs/product-cards.md). Must run AFTER
 * CatalogClassifierSeeder: that seeder rebuilds the classifier tree and prunes class
 * pivots, which would leave the card classes without their fields.
 */
class ProductCardSeeder extends Seeder
{
    /** Card files under database/seeders/data/product_cards, in import order. */
    public const FILES = ['doseme', 'tavan', 'divar', 'santexnika', 'elektrik', 'tekstil', 'landsaft', 'iqlim', 'qapi', 'mebel_furnitura', 'mebel'];

    public function run(CardImporter $importer): void
    {
        foreach (self::FILES as $file) {
            $importer->importFile($file);
        }
    }
}
