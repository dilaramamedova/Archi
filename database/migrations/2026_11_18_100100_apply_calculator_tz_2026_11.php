<?php

use Database\Seeders\CalculatorTzSeeder;
use Illuminate\Database\Migrations\Migration;

/**
 * Calculator TZ of 17.11.2026, data part: stages, new works and materials, settings
 * and room types (database/seeders/data/calculator_tz_2026_11.php).
 *
 * Runs in the "keep Archi's edits" mode — a price, name or setting already in the
 * table is never overwritten; only missing rows are inserted and rows switched on/off.
 * A fresh database (no calculator rows yet) is filled by CalculatorSeeder instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (\App\Models\CalculatorWorkRate::query()->doesntExist()) {
            return;
        }

        (new CalculatorTzSeeder)->apply(fresh: false);
    }

    public function down(): void
    {
        // Data only; the schema migration's down() drops the columns and the table.
    }
};
