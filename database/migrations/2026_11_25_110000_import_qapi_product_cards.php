<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Qapılar, pəncərələr və şüşələmə: the client's 31 cards (D01_1–D12) in the workbook's
 * four blocks — doors, windows and outdoor systems, glazing and partitions, hardware —
 * from database/seeders/data/product_cards/qapi.php. The ten old plain classes are
 * reused, merged or closed; the emptied groups close.
 */
return new class extends Migration
{
    private const SECTION = 'qapilar-pencereler-ve-suseleme';

    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('qapi');
    }

    public function down(): void
    {
        // The tree move is not undone; the door and window cards are switched off.
        $section = Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->value('id');
        $groups = Category::query()->where('parent_id', $section)->pluck('id');
        SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
