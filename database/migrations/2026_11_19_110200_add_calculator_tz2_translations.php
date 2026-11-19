<?php

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * UI copy of the calculator TZ №2 of 19.11.2026 — lighting type, track lights and
 * rail, LED outputs, BALCONY / WARDROBE, the PDF retry message and the line under
 * the quick calculator's «Tam təmir» total (database/seeders/data/calculator_translations.php).
 *
 * Only keys that are not in the table yet are written, so a wording Archi has edited
 * in «Tərcümələr» survives.
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
        // Copy only; nothing to undo.
    }
};
