<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Landşaft: the client's landscape cards — from
 * database/seeders/data/product_cards/landsaft.php. No new UI copy: every card uses the engine's texts.
 */
return new class extends Migration
{
    private const SECTION = 'landsaft-ve-abadlasdirma';

    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('landsaft');
    }

    public function down(): void
    {
        // The tree move is not undone; the landscape cards are switched off.
        $section = Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->value('id');
        $groups = Category::query()->where('parent_id', $section)->pluck('id');
        SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
