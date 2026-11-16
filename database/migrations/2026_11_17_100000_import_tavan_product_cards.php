<?php

use App\Models\Category;
use App\Models\Translation;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Tavanlar: the client's 9 ceiling cards (T01–T09) and 26 ceiling-accessory cards
 * (K1_1–K7_4) — database/seeders/data/product_cards/tavan.php — plus the card copy
 * added for them (sales unit, documents, whole numbers).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (require database_path('seeders/data/product_card_translations.php') as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        }
        Translation::clearCache();

        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', 'tavanlar')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('tavan');
    }

    public function down(): void
    {
        // The tree move is not undone; the ceiling cards are switched off.
        $tavan = Category::query()->where('slug', 'tavanlar')->value('id');
        $groups = Category::query()->where('parent_id', $tavan)->pluck('id');
        \App\Models\SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
