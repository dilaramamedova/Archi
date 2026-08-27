<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Packaging and logistics data the estimate engine needs from a catalog product.
 *
 * Without package_size the calculator cannot turn "44.94 m² of SPC" into the 21
 * packs the client is actually charged for (master spec §13), and without weight and
 * volume it cannot count delivery trips or manual lifting (§14). All four are
 * nullable: a product that lacks them still sells, its estimate line just stays at
 * the required quantity and the logistics block reports itself incomplete instead of
 * quoting 0 AZN.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('package_size', 12, 4)->nullable()->after('unit');
            $table->string('package_unit', 24)->nullable()->after('package_size');
            $table->decimal('weight_kg', 12, 3)->nullable()->after('package_unit');
            $table->decimal('volume_m3', 12, 4)->nullable()->after('weight_kg');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['package_size', 'package_unit', 'weight_kg', 'volume_m3']);
        });
    }
};
