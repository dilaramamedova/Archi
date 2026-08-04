<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialist_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Main info (from specialist-cabinet)
            $table->string('craft')->nullable();
            $table->unsignedSmallInteger('experience_years')->nullable();
            $table->string('city')->nullable();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('about')->nullable();
            $table->string('avatar_path')->nullable();

            // Skills (from specialist-cabinet skills card)
            $table->json('skills')->nullable();

            // Schedule meta (from specialist-cabinet-schedule)
            $table->unsignedSmallInteger('available_slots')->nullable();
            $table->boolean('is_on_vacation')->default(false);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialist_profiles');
    }
};
