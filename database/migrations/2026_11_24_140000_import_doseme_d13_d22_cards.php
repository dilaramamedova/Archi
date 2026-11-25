<?php

use App\Models\Category;
use App\Models\Translation;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Döşəmələr: cards D13–D22 for the ten classes that still had the plain form
 * (mosaic, cork, self-levelling, sports, raised floor, subfloor boards, skirting,
 * underlay, transition profiles, damper tape) — TZ Döşəmələr 24.11.2026. The file now
 * has three groups; group and class names carry the ones edited in the admin, and
 * «Səki plitələri və tamet», switched off there, stays off (`hide_classes`). Plus the
 * new «Vərəq» sales unit label.
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
        if (Category::query()->where('slug', 'dosemeler')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('doseme');
    }

    public function down(): void
    {
        // Cards are not unimported; the previous migration state had them as plain classes.
    }
};
