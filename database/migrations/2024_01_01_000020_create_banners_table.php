<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('position');
            $table->json('title')->nullable();
            $table->json('subtitle')->nullable();
            $table->string('image');
            $table->json('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('position');
        });

        Schema::create('promo_banners', function (Blueprint $table) {
            $table->id();
            $table->json('badge_text')->nullable();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('code')->nullable();
            $table->json('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->string('background_color')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_banners');
        Schema::dropIfExists('banners');
    }
};
