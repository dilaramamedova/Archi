<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Category product cards (docs/product-cards.md): a product class can carry its own
 * parameter form, defined as data on top of the existing attributes / options /
 * attribute_sub_category tables, plus the product-level fields every card shares.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Which classes use a card, and how their products are sold.
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->boolean('card_enabled')->default(false)->after('is_active');
            $table->json('card_settings')->nullable()->after('card_enabled');
        });

        // Per-class behaviour of one parameter.
        Schema::table('attribute_sub_category', function (Blueprint $table) {
            $table->json('hint')->nullable()->after('unit');
            $table->json('presets')->nullable()->after('hint');
            $table->json('visible_when')->nullable()->after('presets');
            $table->json('rules')->nullable()->after('visible_when');
            $table->string('maps_to', 32)->nullable()->after('rules');
        });

        // "Digər" → write the exact name; "Bəli" → name the source; "Göstərilməyib"
        // excludes every other chip; some options exist only after another answer.
        Schema::table('attribute_options', function (Blueprint $table) {
            $table->json('text_prompt')->nullable()->after('slug');
            $table->json('visible_when')->nullable()->after('text_prompt');
            $table->boolean('is_exclusive')->default(false)->after('visible_when');
        });

        // Pack areas come with four decimals on the label (2.1315 m²).
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->decimal('value_numeric', 14, 4)->nullable()->change();
            $table->decimal('value_min', 14, 4)->nullable()->change();
            $table->decimal('value_max', 14, 4)->nullable()->change();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->char('origin_country', 2)->nullable()->after('brand_id');
            $table->boolean('is_unbranded')->default(false)->after('origin_country');
            $table->string('collection', 120)->nullable()->after('is_unbranded');
            // in_stock | on_order | unavailable
            $table->string('availability', 16)->default('in_stock')->after('stock');
            $table->unsignedSmallInteger('lead_time_days')->nullable()->after('availability');
            // Price stays what the seller typed (AZN/m², AZN/p.m …); one sold unit
            // (a pack, a running metre of roll, a board) costs price × sale_factor.
            $table->decimal('sale_factor', 14, 6)->nullable()->after('lead_time_days');
            $table->string('sale_unit', 16)->nullable()->after('sale_factor');
            // Custom-size stone: no automatic price, the buyer asks for an estimate.
            $table->boolean('quote_only')->default(false)->after('sale_unit');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'origin_country', 'is_unbranded', 'collection', 'availability',
                'lead_time_days', 'sale_factor', 'sale_unit', 'quote_only',
            ]);
        });

        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->decimal('value_numeric', 12, 3)->nullable()->change();
            $table->decimal('value_min', 12, 3)->nullable()->change();
            $table->decimal('value_max', 12, 3)->nullable()->change();
        });

        Schema::table('attribute_options', function (Blueprint $table) {
            $table->dropColumn(['text_prompt', 'visible_when', 'is_exclusive']);
        });

        Schema::table('attribute_sub_category', function (Blueprint $table) {
            $table->dropColumn(['hint', 'presets', 'visible_when', 'rules', 'maps_to']);
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropColumn(['card_enabled', 'card_settings']);
        });
    }
};
