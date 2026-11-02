<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tag each consultation request with the form it came from, so B2B plan
 * leads are separate from home-page leads. Existing rows are home leads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table): void {
            $table->string('source', 20)->default('home')->after('message')->index();
            $table->string('b2b_plan', 40)->nullable()->after('source');
        });
    }

    public function down(): void
    {
        Schema::table('consultation_requests', function (Blueprint $table): void {
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'b2b_plan']);
        });
    }
};
