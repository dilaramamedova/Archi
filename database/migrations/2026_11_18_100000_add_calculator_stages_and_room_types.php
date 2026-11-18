<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calculator TZ of 17.11.2026: every position belongs to a stage (White Box or the
 * finishing stage), prices carry an «approved» date, and the room-type norms move from
 * code to the admin panel (calculator_room_types).
 */
return new class extends Migration
{
    public function up(): void
    {
        // WHITE_BOX | FINISH | LAST (the last selected stage) | null (outside both).
        Schema::table('calculator_work_rates', function (Blueprint $table) {
            $table->string('stage', 16)->nullable()->after('category');
            $table->timestamp('approved_at')->nullable()->after('is_active');
        });

        Schema::table('calculator_finishes', function (Blueprint $table) {
            $table->string('labour_stage', 16)->nullable()->after('family');
            $table->string('material_stage', 16)->nullable()->after('labour_stage');
            // A finish finished on top by another one: plasterboard → CEILING_PAINT.
            $table->string('top_coat_finish', 64)->nullable()->after('material_stage');
            // Laminate-type floors also need an underlay (MAT_LAMINATE_UNDERLAY).
            $table->boolean('needs_underlay')->default(false)->after('top_coat_finish');
            // Material by the litre: area × coats / coverage (paint), priced per litre.
            $table->boolean('paint_norm')->default(false)->after('needs_underlay');
            $table->timestamp('approved_at')->nullable()->after('is_active');
        });

        Schema::table('calculator_material_fallbacks', function (Blueprint $table) {
            $table->string('stage', 16)->nullable()->after('surface_group');
            $table->timestamp('approved_at')->nullable()->after('is_active');
        });

        Schema::create('calculator_room_types', function (Blueprint $table) {
            $table->id();
            $table->string('room_type', 32)->unique();
            $table->json('name');
            // A water line runs here (sink, toilet…): plumbing points come from fixtures.
            $table->boolean('has_plumbing')->default(false);
            // Floor and every wall are waterproofed (bathroom, WC — Archi's decision).
            $table->boolean('needs_waterproofing')->default(false);
            // {"sockets": {"base": 6, "per_m2": 0.2}, …} — «Sadə» mode norms.
            $table->json('point_norms')->nullable();
            // {"toilet": 1, "mixer": 2, …}
            $table->json('fixtures')->nullable();
            // One more air conditioner above this floor area (m²); null = never.
            $table->decimal('ac_extra_above_m2', 8, 2)->nullable();
            // A TV point only from this floor area (m²); null = always.
            $table->decimal('tv_min_area_m2', 8, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_room_types');

        Schema::table('calculator_material_fallbacks', function (Blueprint $table) {
            $table->dropColumn(['stage', 'approved_at']);
        });
        Schema::table('calculator_finishes', function (Blueprint $table) {
            $table->dropColumn(['labour_stage', 'material_stage', 'top_coat_finish', 'needs_underlay', 'paint_norm', 'approved_at']);
        });
        Schema::table('calculator_work_rates', function (Blueprint $table) {
            $table->dropColumn(['stage', 'approved_at']);
        });
    }
};
