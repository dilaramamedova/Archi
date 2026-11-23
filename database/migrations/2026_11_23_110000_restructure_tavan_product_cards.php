<?php

use App\Models\Category;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Tavanlar 38 → 20 (Tavanlar_kartlar_38-20_AZ.xlsx, 23.11.2026): the 26 accessory cards
 * fold into 6 classes with a «Növ» field, kesson and cornice get cards, class 42 closes,
 * groups 112 / 115 / 116 close, 110 / 113 / 114 are renamed and «Tavan dekoru» opens.
 * Products of a folded class move to the main class with «Növ» = the old class; the
 * old ?class= / ?group= links answer with a 301 (CatalogController::mergedTarget).
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', 'tavanlar')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('tavan');
    }

    public function down(): void
    {
        // The fold is not undone automatically: restore from the pre-deploy backup.
    }
};
