<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Homepage curation: is_featured / is_sale mark the pool ("selected" status);
        // show_on_homepage picks which of them actually appear on the home page grids.
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('show_on_homepage')->default(false)->after('is_sale');
        });

        Schema::table('specialist_profiles', function (Blueprint $table) {
            $table->boolean('show_on_homepage')->default(false)->after('is_featured');
        });

        // Keep the current homepage look: everything featured/sale today stays visible.
        DB::table('products')->where('is_featured', true)->orWhere('is_sale', true)
            ->update(['show_on_homepage' => true]);
        DB::table('specialist_profiles')->where('is_featured', true)
            ->update(['show_on_homepage' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('show_on_homepage');
        });

        Schema::table('specialist_profiles', function (Blueprint $table) {
            $table->dropColumn('show_on_homepage');
        });
    }
};
