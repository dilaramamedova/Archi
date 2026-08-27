<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Runtime side of the calculator: the live session and its frozen snapshots.
 *
 * Inputs are stored as one JSON document rather than in ten normalised tables. The
 * engine is a pure function of (inputs, config): every derived quantity and every
 * estimate line is recomputed from the inputs, so normalising them would buy nothing
 * and cost a write per keystroke. The snapshot is the opposite case - it must never
 * move again, so it stores the finished lines, the prices actually used and the
 * config version alongside the inputs (master spec 22).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('anonymous_id', 64)->nullable()->index();
            $table->string('status', 24)->default('DRAFT');
            $table->string('current_step', 24)->default('OBJECT');
            $table->json('inputs');
            $table->json('totals')->nullable();
            $table->timestamps();
        });

        Schema::create('calculator_snapshots', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('calculator_session_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->json('inputs');
            $table->json('derived');
            $table->json('lines');
            $table->json('totals');
            $table->string('config_version', 64);
            $table->string('calculator_version', 16);
            $table->string('currency', 3)->default('AZN');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['calculator_session_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_snapshots');
        Schema::dropIfExists('calculator_sessions');
    }
};
