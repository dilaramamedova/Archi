<?php

declare(strict_types=1);

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;

/**
 * Copy of the tender section (Figma «11 · TENDERLƏR»): the `tenders` group used by
 * pages/tenders, pages/tender and pages/tenders-locked plus TenderController's JSON
 * messages, and the three `notifications` keys of TenderAccessGrantedNotification.
 *
 * The rows live in database/seeders/data/tenders_translations.php (TranslationsSeeder
 * reads the same file). Upserts only these keys — the full TranslationsSeeder would
 * overwrite copy that admins have edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->rows() as $group => $items) {
            foreach ($items as $key => $value) {
                Translation::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
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
        return require database_path('seeders/data/tenders_translations.php');
    }
};
