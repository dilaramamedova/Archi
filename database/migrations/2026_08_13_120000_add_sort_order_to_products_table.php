<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual product ordering for the admin panel's drag & drop.
 *
 * Everything starts at 0, which means "no manual position". The storefront lists
 * positioned products (sort_order > 0) first, in their order, and keeps the old
 * behaviour for the rest — so nothing moves until an admin actually drags a row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->index()->after('stock');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};
