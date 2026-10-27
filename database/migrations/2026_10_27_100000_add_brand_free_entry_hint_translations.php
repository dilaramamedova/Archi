<?php

declare(strict_types=1);

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * Seller product form: the brand field accepts a name that is not in the list
 * (it is created on save). The placeholder and a hint now say so.
 *
 * Upserts only these two keys — the full TranslationsSeeder would overwrite
 * copy that admins have edited.
 */
return new class extends Migration
{
    private const ROWS = [
        'basic.brand_placeholder' => [
            'az' => 'Seçin və ya yazın…',
            'ru' => 'Выберите или введите…',
            'en' => 'Select or type…',
        ],
        'basic.brand_hint' => [
            'az' => 'Siyahıda yoxdursa, brendin adını yazın — yeni brend kimi əlavə olunacaq.',
            'ru' => 'Если бренда нет в списке, введите его название — он будет добавлен как новый.',
            'en' => 'Not in the list? Type the brand name — it will be added as a new brand.',
        ],
    ];

    public function up(): void
    {
        foreach (self::ROWS as $key => $value) {
            Translation::updateOrCreate(['group' => 'business-product-edit', 'key' => $key], ['value' => $value]);
        }

        Translation::clearCache();
    }

    public function down(): void
    {
        Translation::query()
            ->where('group', 'business-product-edit')
            ->whereIn('key', array_keys(self::ROWS))
            ->delete();

        Translation::clearCache();
    }
};
