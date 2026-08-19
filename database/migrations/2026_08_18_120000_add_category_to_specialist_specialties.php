<?php

use App\Enums\SpecialistCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Groups specialties under the four categories the header mega panel offers.
 *
 * The panel has always linked to /specialists?type=architect|designer|master|company,
 * but nothing read that parameter and no column existed to answer it, so all four
 * cards dumped the visitor into the undifferentiated directory. The grouping lives on
 * the specialty row rather than in a hardcoded id map so an admin adding a trade can
 * file it without a deploy.
 *
 * Two categories had no specialty at all behind them — a visitor could click
 * "Arxitektorlar" forever and nobody could even register as one, because the sign-up
 * dropdown is built from this table. They are created here, which makes the menu honest
 * and opens the two roles for registration at the same time.
 */
return new class extends Migration
{
    /** Existing specialty slugs that are trades rather than design/architecture. */
    private const DESIGNER_SLUGS = ['interyer-dizayner'];

    public function up(): void
    {
        Schema::table('specialist_specialties', function (Blueprint $table) {
            $table->string('category', 20)
                ->default(SpecialistCategory::Master->value)
                ->after('slug')
                ->index();
        });

        // Everything seeded so far is a trade except the interior designer.
        DB::table('specialist_specialties')
            ->whereIn('slug', self::DESIGNER_SLUGS)
            ->update(['category' => SpecialistCategory::Designer->value]);

        $this->createMissingSpecialties();
    }

    public function down(): void
    {
        DB::table('specialist_specialties')
            ->whereIn('slug', ['memar', 'tikinti-sirketi'])
            ->delete();

        Schema::table('specialist_specialties', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn('category');
        });
    }

    /**
     * The architect and construction-company rows the menu promised. Created only when
     * absent so the migration stays safe to re-run, and pushed to the end of the sort
     * order rather than renumbering the trades already on screen.
     */
    private function createMissingSpecialties(): void
    {
        $nextSort = (int) DB::table('specialist_specialties')->max('sort_order');
        $now = now();

        $missing = [
            [
                'slug' => 'memar',
                'category' => SpecialistCategory::Architect->value,
                'name' => ['az' => 'Memar', 'ru' => 'Архитектор', 'en' => 'Architect'],
            ],
            [
                'slug' => 'tikinti-sirketi',
                'category' => SpecialistCategory::Company->value,
                'name' => ['az' => 'Tikinti şirkəti', 'ru' => 'Строительная компания', 'en' => 'Construction company'],
            ],
        ];

        foreach ($missing as $row) {
            if (DB::table('specialist_specialties')->where('slug', $row['slug'])->exists()) {
                continue;
            }

            DB::table('specialist_specialties')->insert([
                'name' => json_encode($row['name'], JSON_UNESCAPED_UNICODE),
                'slug' => $row['slug'],
                'category' => $row['category'],
                'is_active' => true,
                'sort_order' => ++$nextSort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
