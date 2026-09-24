<?php

declare(strict_types=1);

use App\Enums\SpecialistCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Files the interior-designer trade under the "designer" category.
 *
 * 2026_08_18_120000 introduced the category column and meant to do this — its
 * DESIGNER_SLUGS list already named `interyer-dizayner`. But DemoSeeder creates the
 * specialty rows without a category, so on any install where the seeder ran AFTER that
 * migration the row came back with the column default, `master`. The visible effect:
 * the header's "İnteryer dizaynerlər" card links to /specialists?type=designer, which
 * matched no specialty at all and returned an empty directory, while the designers
 * themselves were filed under "Ustalar".
 *
 * Only rows still sitting on the default are moved, so an admin who has deliberately
 * re-filed a trade in the panel keeps their choice.
 */
return new class extends Migration
{
    /** slug => the category the trade actually belongs to. */
    private const CORRECTIONS = [
        'interyer-dizayner' => SpecialistCategory::Designer,
        'memar' => SpecialistCategory::Architect,
        'tikinti-sirketi' => SpecialistCategory::Company,
    ];

    public function up(): void
    {
        foreach (self::CORRECTIONS as $slug => $category) {
            DB::table('specialist_specialties')
                ->where('slug', $slug)
                ->where('category', SpecialistCategory::Master->value)
                ->update(['category' => $category->value]);
        }
    }

    /**
     * Data-only correction. Rolling it back would re-file architects and construction
     * companies as trades, which is the bug this migration exists to fix — and it
     * cannot tell which rows it moved from those the 2026_08_18 migration already had
     * right. Nothing to revert.
     */
    public function down(): void {}
};
