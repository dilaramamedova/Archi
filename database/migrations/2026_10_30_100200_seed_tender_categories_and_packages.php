<?php

declare(strict_types=1);

use App\Models\TenderCategory;
use App\Models\TenderPackage;
use Illuminate\Database\Migrations\Migration;

/**
 * Starting data for the tender board: the five categories from the Figma
 * filter and four access packages. Prices are left empty ("Qiymət razılaşma
 * ilə") — the admin sets them in Tenderlər → Ödənişli paketlər.
 *
 * Inserts only what is missing (by slug / duration), so admin edits survive.
 */
return new class extends Migration
{
    private const CATEGORIES = [
        'memarliq-ve-dizayn' => ['az' => 'Memarlıq və dizayn', 'ru' => 'Архитектура и дизайн', 'en' => 'Architecture & design'],
        'tikinti-ve-temir' => ['az' => 'Tikinti və təmir', 'ru' => 'Строительство и ремонт', 'en' => 'Construction & renovation'],
        'mebel' => ['az' => 'Mebel', 'ru' => 'Мебель', 'en' => 'Furniture'],
        'material-techizati' => ['az' => 'Material təchizatı', 'ru' => 'Поставка материалов', 'en' => 'Material supply'],
        'muhendislik' => ['az' => 'Mühəndislik', 'ru' => 'Инженерия', 'en' => 'Engineering'],
    ];

    private const PACKAGES = [
        1 => ['az' => 'Aylıq paket', 'ru' => 'Месячный пакет', 'en' => 'Monthly package'],
        3 => ['az' => '3 aylıq paket', 'ru' => 'Пакет на 3 месяца', 'en' => '3-month package'],
        6 => ['az' => '6 aylıq paket', 'ru' => 'Пакет на 6 месяцев', 'en' => '6-month package'],
        12 => ['az' => 'İllik paket', 'ru' => 'Годовой пакет', 'en' => 'Annual package'],
    ];

    public function up(): void
    {
        $sort = 0;
        foreach (self::CATEGORIES as $slug => $name) {
            $sort++;
            if (! TenderCategory::query()->where('slug', $slug)->exists()) {
                TenderCategory::query()->create(['slug' => $slug, 'name' => $name, 'sort_order' => $sort, 'is_active' => true]);
            }
        }

        $sort = 0;
        foreach (self::PACKAGES as $months => $name) {
            $sort++;
            if (! TenderPackage::query()->where('duration_months', $months)->exists()) {
                TenderPackage::query()->create(['name' => $name, 'duration_months' => $months, 'sort_order' => $sort, 'is_active' => true]);
            }
        }
    }

    public function down(): void
    {
        TenderCategory::query()->whereIn('slug', array_keys(self::CATEGORIES))->doesntHave('tenders')->delete();
        TenderPackage::query()->whereIn('duration_months', array_keys(self::PACKAGES))->delete();
    }
};
