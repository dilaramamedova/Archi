<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuration side of the renovation calculator.
 *
 * Hard requirement of both client documents (TZ 30 / 34 and master spec 21, 24):
 * not one rate, fallback price, coefficient, consumption norm or limit may live in
 * code. Every number the engine multiplies comes from one of these tables and is
 * editable in the admin panel without a deployment.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The six approved quick-calculator prices (TZ 10 / 54).
        Schema::create('calculator_quick_rates', function (Blueprint $table) {
            $table->id();
            $table->string('renovation_type', 40);
            $table->string('segment', 20);
            $table->decimal('price_per_m2', 12, 2)->nullable();
            $table->string('currency', 3)->default('AZN');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['renovation_type', 'segment']);
        });

        // Work catalog + rates (master spec 10, Work_Rates sheet of the price matrix).
        Schema::create('calculator_work_rates', function (Blueprint $table) {
            $table->id();
            $table->string('work_id', 64)->unique();
            $table->string('category', 32)->index();
            $table->json('name');
            $table->string('unit', 24);
            $table->string('quantity_source', 64);
            $table->string('formula')->nullable();
            $table->decimal('rate', 12, 4)->nullable();
            $table->decimal('minimum_charge', 12, 2)->default(0);
            $table->string('precision_class', 16)->default('CALCULATED');
            $table->boolean('contingency_eligible')->default(true);
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Category prices used until the visitor picks a real product (master spec 12).
        Schema::create('calculator_material_fallbacks', function (Blueprint $table) {
            $table->id();
            $table->string('material_category_id', 64)->unique();
            $table->string('surface_group', 24)->index();
            $table->json('name');
            $table->string('unit', 24);
            $table->decimal('economy_price', 12, 4)->nullable();
            $table->decimal('standard_price', 12, 4)->nullable();
            $table->decimal('premium_price', 12, 4)->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Multipliers. 1.00 means "no change" - never delete a row, deactivate it.
        Schema::create('calculator_coefficients', function (Blueprint $table) {
            $table->id();
            $table->string('coefficient_id', 64)->unique();
            $table->string('group', 24)->index();
            $table->json('description');
            $table->decimal('value', 8, 4)->default(1);
            $table->string('applies_to')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Consumption norms and waste percentages (master spec 12.1, 15).
        Schema::create('calculator_material_norms', function (Blueprint $table) {
            $table->id();
            $table->string('rule_id', 64)->unique();
            $table->json('name');
            $table->string('parameter', 64);
            $table->decimal('value', 12, 4);
            $table->string('unit', 32)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Scalar limits and switches: contingency %, area bounds, lift rules, HVAC
        // included route metres, SHOW_FROM_PREFIX, detailed calculator URL, ...
        Schema::create('calculator_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();
            $table->string('group', 24)->index();
            $table->json('description');
            $table->string('value')->nullable();
            $table->string('type', 16)->default('decimal');
            $table->string('unit', 24)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // City coefficient (master spec audit 2.10). Empty table means 1.00, never a block.
        Schema::create('calculator_cities', function (Blueprint $table) {
            $table->id();
            $table->string('city_key', 40)->unique();
            $table->json('name');
            $table->decimal('coefficient', 8, 4)->default(1);
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_cities');
        Schema::dropIfExists('calculator_settings');
        Schema::dropIfExists('calculator_material_norms');
        Schema::dropIfExists('calculator_coefficients');
        Schema::dropIfExists('calculator_material_fallbacks');
        Schema::dropIfExists('calculator_work_rates');
        Schema::dropIfExists('calculator_quick_rates');
    }
};
