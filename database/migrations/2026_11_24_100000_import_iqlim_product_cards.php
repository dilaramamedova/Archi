<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * İqlim: the client's heating, air-conditioning and ventilation cards (H01–H15_5) — from
 * database/seeders/data/product_cards/iqlim.php. No new UI copy: every card uses the engine's texts.
 */
return new class extends Migration
{
    private const SECTION = 'istilik-ventilyasiya-ve-kondisionerlesdirme';

    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('iqlim');
    }

    public function down(): void
    {
        // The tree move is not undone; the climate cards are switched off.
        $section = Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->value('id');
        $groups = Category::query()->where('parent_id', $section)->pluck('id');
        SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
