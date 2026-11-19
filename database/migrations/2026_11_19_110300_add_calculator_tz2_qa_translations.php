<?php

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * Copy added after the QA pass of the calculator TZ №2: the heated floor area field
 * and the RU / EN unit labels (database/seeders/data/calculator_translations.php).
 *
 * Only keys that are not in the table yet are written.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = require database_path('seeders/data/calculator_translations.php');

        foreach ($rows as $group => $items) {
            foreach ($items as $key => $value) {
                if (Translation::where('group', $group)->where('key', $key)->exists()) {
                    continue;
                }

                (new Translation(['group' => $group, 'key' => $key]))->setTranslations('value', $value)->save();
            }
        }

        Translation::clearCache();
    }

    public function down(): void
    {
        // Copy only.
    }
};
