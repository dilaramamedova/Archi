<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Santexnika: the client's 46 cards — 27 fixtures (S01_1–S10_3), 6 accessories
 * (S11_1–S11_6) and 13 plumbing components (S12_1–S12_13) — from
 * database/seeders/data/product_cards/santexnika.php. No new UI copy: every card uses
 * the engine's existing texts.
 */
return new class extends Migration
{
    private const SECTION = 'santexnika-su-techizati-ve-kanalizasiya';

    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('santexnika');
    }

    public function down(): void
    {
        // The tree move is not undone; the plumbing cards are switched off.
        $section = Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->value('id');
        $groups = Category::query()->where('parent_id', $section)->pluck('id');
        SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
