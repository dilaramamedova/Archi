<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Elektrik: the client's 43 cards — wiring devices (E01_01–E01_06), lighting
 * (E02_01–E02_14), internet / communications / security (E03_01–E03_16) and smart home
 * (E04_01–E04_07) — from database/seeders/data/product_cards/elektrik.php. «Güc elektrik
 * sistemləri» keeps its plain form. No new UI copy: every card uses the engine's texts.
 */
return new class extends Migration
{
    private const SECTION = 'elektrik-isiqlandirma-zeif-cereyan-sistemleri-ve-agilli-ev';

    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('elektrik');
    }

    public function down(): void
    {
        // The tree move is not undone; the electrical cards are switched off.
        $section = Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->value('id');
        $groups = Category::query()->where('parent_id', $section)->pluck('id');
        SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
