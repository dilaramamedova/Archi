<?php

declare(strict_types=1);

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * B2B company profile copy added after 2026_11_02_110300_add_b2b_translations:
 * the legal-address row, the contact person's direct-phone label and the
 * communication-language names (B2bCompany::languageLabels). Upserts only these
 * keys so an existing database gets them without the full TranslationsSeeder
 * overwriting admin-edited copy.
 */
return new class extends Migration
{
    private const KEYS = [
        'contact.address', 'contact.direct_phone',
        'lang.az', 'lang.ru', 'lang.en', 'lang.tr', 'lang.other',
    ];

    public function up(): void
    {
        $rows = require database_path('seeders/data/b2b_translations.php');

        foreach (self::KEYS as $key) {
            if (isset($rows['b2b'][$key])) {
                Translation::updateOrCreate(
                    ['group' => 'b2b', 'key' => $key],
                    ['value' => $rows['b2b'][$key]],
                );
            }
        }

        Translation::clearCache();
    }

    public function down(): void
    {
        Translation::query()->where('group', 'b2b')->whereIn('key', self::KEYS)->delete();

        Translation::clearCache();
    }
};
