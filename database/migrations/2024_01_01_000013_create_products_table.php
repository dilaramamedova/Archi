<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->unique();
            $table->string('brand')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable();
            $table->integer('discount_percent')->nullable();
            $table->string('unit')->default('piece');
            $table->integer('stock')->default(0);
            $table->integer('min_order')->default(1);
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_sale')->default(false);
            $table->boolean('free_delivery')->default(false);
            $table->boolean('return_14_days')->default(false);
            $table->json('specifications')->nullable();
            $table->json('features')->nullable();
            $table->json('frequently_bought_together')->nullable();
            $table->json('accessories')->nullable();
            $table->string('condition')->default('new');
            $table->integer('views_count')->default(0);
            $table->timestamps();

            $table->index('user_id');
            $table->index('category_id');
            $table->index(['is_visible', 'is_approved']);
            $table->index('is_featured');
            $table->index('is_sale');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
