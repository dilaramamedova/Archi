<?php

use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Mebel və mebel furniturası: the client's 64 cards — furniture M01_1–M15_2
 * (mebel.php, 15 groups) and hardware F01_1–F12_4 (mebel_furnitura.php, 12 groups) —
 * in one section. The hardware file goes first: it moves its old classes out of the
 * old groups, which the furniture file then closes. Appliances and raw boards stay
 * with the plain form.
 */
return new class extends Migration
{
    private const SECTION = 'mebel-komplektlesdiriciler-furnitura-ve-mexanizmler';

    public function up(): void
    {
        // A fresh database has no catalog tree yet — ProductCardSeeder imports later.
        if (Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('mebel_furnitura');
        app(CardImporter::class)->importFile('mebel');
    }

    public function down(): void
    {
        // The tree move is not undone; the furniture cards are switched off.
        $section = Category::query()->where('slug', self::SECTION)->whereNull('parent_id')->value('id');
        $groups = Category::query()->where('parent_id', $section)->pluck('id');
        SubCategory::query()->whereIn('category_id', $groups)->update(['card_enabled' => false]);
    }
};
