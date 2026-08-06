<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('about_steps', function (Blueprint $table) {
            $table->id();
            $table->integer('step_number')->default(1);
            $table->json('title');
            $table->json('description');
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('about_team_members', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('role');
            $table->string('image')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('about_team_members');
        Schema::dropIfExists('about_steps');
    }
};
