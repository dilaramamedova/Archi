<?php

use App\Enums\City;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Registration used to store the raw select slug ("baku") while the cabinets stored
 * the Azerbaijani label ("Bakı"), so the same city rendered two different ways.
 * The label is now canonical — rewrite every slug row to its label.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['specialist_profiles', 'seller_profiles', 'showrooms'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'city')) {
                continue;
            }

            foreach (City::cases() as $city) {
                DB::table($table)
                    ->whereRaw('LOWER(city) = ?', [$city->value])
                    ->update(['city' => $city->label()]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible: the pre-migration value cannot be told apart per row.
    }
};
