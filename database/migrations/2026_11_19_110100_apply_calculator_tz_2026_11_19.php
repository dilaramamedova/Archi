<?php

use Database\Seeders\CalculatorTz2Seeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Calculator TZ №2 of 19.11.2026, data part: the track system works and materials,
 * LED outputs, the track norms (TRACK_LIGHT_AREA_M2, TRACK_RAIL_M_PER_M2,
 * TRACK_RAIL_FEED_M) and the BALCONY / WARDROBE room types
 * (database/seeders/data/calculator_tz_2026_11_19.php).
 *
 * «Keep Archi's edits» mode — only missing rows are inserted. A fresh database (no
 * calculator rows yet) is filled by CalculatorSeeder instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (\App\Models\CalculatorWorkRate::query()->doesntExist()) {
            return;
        }

        (new CalculatorTz2Seeder)->apply(fresh: false);
    }

    public function down(): void
    {
        // Data only.
    }
};
