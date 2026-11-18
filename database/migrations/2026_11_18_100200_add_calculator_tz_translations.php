<?php

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * UI copy of the calculator TZ of 17.11.2026 — levels, stages, the contractor's
 * margin, the area dialog, the «Sadə» points line, openings, extra finishes, the
 * quick calculator's «what is included» block.
 *
 * Only keys that are not in the table yet are written, so a wording Archi has edited
 * in «Tərcümələr» survives. The one exception is a key whose MEANING changed:
 * «fixtures.kitchen_sink» used to read «qabyuyan», which is now the dishwasher.
 */
return new class extends Migration
{
    private const REWORDED = [
        'calculator-detailed' => ['fixtures.kitchen_sink'],
    ];

    public function up(): void
    {
        $rows = require database_path('seeders/data/calculator_translations.php');

        foreach ($rows as $group => $items) {
            foreach ($items as $key => $value) {
                $existing = Translation::where('group', $group)->where('key', $key)->first();

                if ($existing && ! in_array($key, self::REWORDED[$group] ?? [], true)) {
                    continue;
                }

                $translation = $existing ?? new Translation(['group' => $group, 'key' => $key]);
                $translation->setTranslations('value', $value)->save();
            }
        }

        Translation::clearCache();
    }

    public function down(): void
    {
        // Copy only; nothing to undo.
    }
};
