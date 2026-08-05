<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badge_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_badge_id')->constrained('product_badges')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['product_badge_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_product');
    }
};
