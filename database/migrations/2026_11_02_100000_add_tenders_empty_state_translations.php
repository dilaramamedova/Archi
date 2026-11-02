<?php

declare(strict_types=1);

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * The "no active tenders yet" copy shown on an empty board (pages/tenders).
 * Upserts only these two keys so an existing database gets them without the
 * full TranslationsSeeder overwriting admin-edited copy.
 */
return new class extends Migration
{
    private const KEYS = ['empty.none_title', 'empty.none_text'];

    public function up(): void
    {
        $rows = require database_path('seeders/data/tenders_translations.php');

        foreach (self::KEYS as $key) {
            if (isset($rows['tenders'][$key])) {
                Translation::updateOrCreate(
                    ['group' => 'tenders', 'key' => $key],
                    ['value' => $rows['tenders'][$key]],
                );
            }
        }

        Translation::clearCache();
    }

    public function down(): void
    {
        Translation::query()->where('group', 'tenders')->whereIn('key', self::KEYS)->delete();

        Translation::clearCache();
    }
};
