<?php

use App\Models\Category;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Santexnika: cards S13_1–S17_2 for the 12 engineering classes that still had the
 * plain form — pipes, fittings, valves, seals, filters, water meters, pumps, water
 * heaters, PVC sewer, septic tanks (TZ Santexnika 24.11.2026). Rods sell by the piece,
 * coils by the roll, both by the metre. Names are the ones edited in the admin.
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', 'santexnika-su-techizati-ve-kanalizasiya')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('santexnika');
    }

    public function down(): void
    {
        // Cards are not unimported; before, these classes had the plain form.
    }
};
