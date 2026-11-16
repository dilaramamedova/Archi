<?php

use App\Models\Category;
use App\Models\Translation;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Döşəmə örtükləri: the client's 12 category cards (D01–D12) and the tree they live
 * in. Same work as `php artisan product-cards:import doseme`; see that file for the
 * mapping of the old classes onto the new ones. Plus the copy the card UI reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->translations() as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        }
        Translation::clearCache();

        // A fresh database (tests, a new install) has no catalog tree yet — the
        // seeders build it and DatabaseSeeder imports the cards afterwards.
        if (Category::query()->where('slug', 'dosemeler')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('doseme');
    }

    public function down(): void
    {
        // The tree move is not undone automatically; cards are switched off so every
        // class falls back to the plain form.
        \App\Models\SubCategory::query()->update(['card_enabled' => false]);
    }

    private function translations(): array
    {
        return require database_path('seeders/data/product_card_translations.php');
    }
};
