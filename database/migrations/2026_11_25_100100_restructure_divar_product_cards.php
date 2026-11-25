<?php

use App\Models\Category;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Divarlar (TZ 24.11.2026): divar.php now mirrors the 7 groups the client set up in the
 * admin (names, membership, 14 groups off); the five adhesive classes fold into one
 * «Yapışqanlar» card with a «Təyinat» filter (old links 301 via merged_into); panels,
 * brick, slats and mouldings gain m² / xətti metr; new cards W13_1–W13_4 for the four
 * partition classes (framed and glass partitions can be priced on request).
 */
return new class extends Migration
{
    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', 'divarlar-ve-arakesmeler')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('divar');
    }

    public function down(): void
    {
        // The admin's structure is the one to keep; nothing to undo.
    }
};
