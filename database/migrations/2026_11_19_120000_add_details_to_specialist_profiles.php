<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kind-specific profile data for the two new specialist kinds — a Briqadir's crew
 * (size, trades, members, "takes whole projects") and a Makler's agency and market.
 * One json column rather than nine sparse ones: the keys belong to one kind each and
 * are never filtered on in SQL. Shape: App\Support\SpecialistDetails.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('specialist_profiles', 'details')) {
            return;
        }

        Schema::table('specialist_profiles', function (Blueprint $table) {
            $table->json('details')->nullable()->after('skills');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('specialist_profiles', 'details')) {
            return;
        }

        Schema::table('specialist_profiles', function (Blueprint $table) {
            $table->dropColumn('details');
        });
    }
};
