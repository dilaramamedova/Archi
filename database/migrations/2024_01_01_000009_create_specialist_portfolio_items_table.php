<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialist_portfolio_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('specialist_profile_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('image_path');
            $table->boolean('is_cover')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('specialist_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('specialist_portfolio_items');
    }
};
