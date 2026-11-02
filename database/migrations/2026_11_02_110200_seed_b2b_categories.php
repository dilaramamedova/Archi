<?php

declare(strict_types=1);

use App\Models\B2bCategory;
use Illuminate\Database\Migrations\Migration;

/**
 * Starting categories for the B2B directory filter. Inserts only what is
 * missing (by slug), so admin edits survive.
 */
return new class extends Migration
{
    private const CATEGORIES = [
        'tikinti-materiallari' => ['az' => 'Tikinti materialları', 'ru' => 'Стройматериалы', 'en' => 'Building materials'],
        'keramika-ve-kafel' => ['az' => 'Keramika və kafel', 'ru' => 'Керамика и плитка', 'en' => 'Ceramics & tiles'],
        'santexnika' => ['az' => 'Santexnika', 'ru' => 'Сантехника', 'en' => 'Plumbing'],
        'elektrik-ve-isiqlandirma' => ['az' => 'Elektrik və işıqlandırma', 'ru' => 'Электрика и освещение', 'en' => 'Electrical & lighting'],
        'boya-ve-ortuk' => ['az' => 'Boya və örtük', 'ru' => 'Краски и покрытия', 'en' => 'Paints & coatings'],
        'mebel-ve-dekor' => ['az' => 'Mebel və dekor', 'ru' => 'Мебель и декор', 'en' => 'Furniture & decor'],
        'pencere-ve-qapi' => ['az' => 'Pəncərə və qapı', 'ru' => 'Окна и двери', 'en' => 'Windows & doors'],
    ];

    public function up(): void
    {
        $sort = 0;
        foreach (self::CATEGORIES as $slug => $name) {
            $sort++;
            if (! B2bCategory::query()->where('slug', $slug)->exists()) {
                B2bCategory::query()->create(['slug' => $slug, 'name' => $name, 'sort_order' => $sort, 'is_active' => true]);
            }
        }
    }

    public function down(): void
    {
        B2bCategory::query()->whereIn('slug', array_keys(self::CATEGORIES))->doesntHave('companies')->delete();
    }
};
