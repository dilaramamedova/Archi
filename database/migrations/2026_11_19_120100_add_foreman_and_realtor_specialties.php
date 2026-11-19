<?php

declare(strict_types=1);

use App\Enums\SpecialistCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The client asked for two more specialist kinds: «Briqadir» (a foreman with his own
 * crew who takes whole jobs) and «Makler» (a real-estate agent). Each kind is one
 * specialty row in its own category — registration resolves the row from the kind the
 * visitor picked, so neither appears in the master's specialty select.
 *
 * Same pattern as 2026_08_18_120000_add_category_to_specialist_specialties: created
 * only when absent (safe to re-run, admin edits survive) and appended to the sort
 * order rather than renumbering the trades already on screen.
 */
return new class extends Migration
{
    private const ROWS = [
        [
            'slug' => 'briqadir',
            'category' => 'foreman',
            'name' => ['az' => 'Briqadir (usta dəstəsi)', 'ru' => 'Бригадир (бригада мастеров)', 'en' => 'Foreman (crew of craftsmen)'],
        ],
        [
            'slug' => 'makler',
            'category' => 'realtor',
            'name' => ['az' => 'Makler (əmlak agenti)', 'ru' => 'Риелтор (агент по недвижимости)', 'en' => 'Realtor (real-estate agent)'],
        ],
    ];

    public function up(): void
    {
        $nextSort = (int) DB::table('specialist_specialties')->max('sort_order');
        $now = now();

        foreach (self::ROWS as $row) {
            if (DB::table('specialist_specialties')->where('slug', $row['slug'])->exists()) {
                continue;
            }

            DB::table('specialist_specialties')->insert([
                'name' => json_encode($row['name'], JSON_UNESCAPED_UNICODE),
                'slug' => $row['slug'],
                'category' => SpecialistCategory::from($row['category'])->value,
                'is_active' => true,
                'sort_order' => ++$nextSort,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Profiles keep their row (the FK is nullOnDelete) and fall to «Digər».
        DB::table('specialist_specialties')
            ->whereIn('slug', array_column(self::ROWS, 'slug'))
            ->delete();
    }
};
