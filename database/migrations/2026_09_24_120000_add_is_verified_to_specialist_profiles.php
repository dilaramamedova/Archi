<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A real "verified" flag for specialists.
 *
 * The directory has always offered a "Yalnız təsdiqlənmiş" filter and the card template
 * has always had a "✓ Təsdiqlənmiş" badge, but no column ever backed either of them —
 * both were wired to `is_featured`, so the filter was an exact duplicate of the
 * featured one and the badge said something the data could not support. The page
 * comments admitted as much and switched the badge off rather than lie.
 *
 * Verification is an editorial decision (documents checked, licence seen), which is a
 * different claim from "we promote this profile", so it gets its own column and starts
 * false for everyone: nobody has been verified yet, and back-filling it from is_featured
 * would mint four verifications that never happened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialist_profiles', function (Blueprint $table): void {
            $table->boolean('is_verified')->default(false)->after('is_featured')->index();
        });
    }

    public function down(): void
    {
        Schema::table('specialist_profiles', function (Blueprint $table): void {
            $table->dropIndex(['is_verified']);
            $table->dropColumn('is_verified');
        });
    }
};
