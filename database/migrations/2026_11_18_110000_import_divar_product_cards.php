<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Translation;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Divar üzlükləri: the client's 21 wall-finish cards (W01_1–W12_2) and 16 accessory
 * cards (WK01_1–WK08) — database/seeders/data/product_cards/divar.php — plus the card
 * copy added for them («Başqa ölçü», the unit/format mismatch message).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Only keys the site does not have yet: wording Archi edited in the admin stays.
        foreach (require database_path('seeders/data/product_card_translations.php') as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::firstOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        }
        Translation::clearCache();

        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', 'divarlar-ve-arakesmeler')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('divar');
    }

    public function down(): void
    {
        // The tree move is not undone; the wall cards are switched off.
        $section = Category::query()->where('slug', 'divarlar-ve-arakesmeler')->whereNull('parent_id')->value('id');
        $groups = Category::query()->where('parent_id', $section)->pluck('id');
        SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
