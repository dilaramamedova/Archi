<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extra words that should find a work in the admin search.
 *
 * An admin does not look for "DOOR_INSTALL_INTERIOR" or even "Otaqarası qapının
 * quraşdırılması" — they look for the label they just saw on the site, which is
 * "Yeni qapı quraşdırılsın". Without a place to put those aliases the search only
 * matches the catalog's own wording and the price stays unfound.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculator_work_rates', function (Blueprint $table) {
            $table->text('search_keywords')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('calculator_work_rates', function (Blueprint $table) {
            $table->dropColumn('search_keywords');
        });
    }
};
