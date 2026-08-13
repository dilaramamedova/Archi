<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Product classes get a stable, URL-safe identity (used by seeder idempotency
        // and by the future catalog frontend). Nullable: legacy rows may not have one.
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('category_id');
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->string('field_type'); // App\Enums\AttributeFieldType
            $table->json('tooltip')->nullable();
            $table->string('complexity')->default('basic'); // App\Enums\AttributeComplexity
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attribute_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->json('value');
            $table->string('slug');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['attribute_id', 'slug']);
        });

        // The "class form definition": which attributes a product class carries.
        Schema::create('attribute_sub_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->string('filter_priority')->nullable(); // App\Enums\FilterPriority
            $table->string('unit')->nullable(); // display unit, e.g. "мм", "кг/м²"
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['sub_category_id', 'attribute_id']);
        });

        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_option_id')->nullable()->constrained()->cascadeOnDelete();
            $table->text('value_text')->nullable();
            $table->decimal('value_numeric', 12, 3)->nullable();
            $table->decimal('value_min', 12, 3)->nullable();
            $table->decimal('value_max', 12, 3)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->timestamps();

            $table->index(['attribute_id', 'attribute_option_id']);
            $table->index('product_id');
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['product_id', 'application_id']);
        });

        // Default application tags per product class (from the classifier tree) —
        // used to suggest applications on the seller form and to cross-list classes.
        Schema::create('sub_category_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sub_category_id', 'application_id']);
        });

        Schema::create('search_synonyms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sub_category_id')->constrained()->cascadeOnDelete();
            $table->string('phrase')->index();
            $table->string('locale', 5)->default('az');
            $table->timestamps();

            $table->unique(['sub_category_id', 'phrase', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_synonyms');
        Schema::dropIfExists('sub_category_applications');
        Schema::dropIfExists('product_applications');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('product_attribute_values');
        Schema::dropIfExists('attribute_sub_category');
        Schema::dropIfExists('attribute_options');
        Schema::dropIfExists('attributes');

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
