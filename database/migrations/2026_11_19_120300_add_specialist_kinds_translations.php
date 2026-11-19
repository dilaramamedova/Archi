<?php

declare(strict_types=1);

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * Copy for the Briqadir / Makler specialist kinds (docs/specialist-kinds.md): category
 * and section labels, the two registration cards, the cabinet and public-profile blocks
 * and the deal / property type options.
 *
 * The rows live in database/seeders/data/specialist_kinds_translations.php
 * (TranslationsSeeder reads the same file). Only these keys are touched, and an
 * existing row is left as it is — an admin may already have edited it.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->rows() as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::firstOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
            }
        }

        Translation::clearCache();
    }

    public function down(): void
    {
        foreach ($this->rows() as $group => $items) {
            Translation::query()
                ->where('group', $group)
                ->whereIn('key', array_keys($items))
                ->delete();
        }

        Translation::clearCache();
    }

    /**
     * @return array<string, array<string, array{az: string, ru: string, en: string}>>
     */
    private function rows(): array
    {
        return require database_path('seeders/data/specialist_kinds_translations.php');
    }
};
