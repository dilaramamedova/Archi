<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Calculator TZ №2 of 19.11.2026, §41: pressing «PDF yüklə» and «WhatsApp» for the same
 * estimate used to raise two requests. A repeat within 30 minutes from the same
 * visitor for the same estimate is now an event on the existing request.
 *
 *   visitor_key    «u:{user id}» or «s:{session id}» — who pressed the button
 *   estimate_hash  sha1 of the estimate's answers + the configuration version
 *   events         [{type, at}] — every press, the first one included
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculator_requests', function (Blueprint $table) {
            $table->string('visitor_key', 120)->nullable()->after('calculator_snapshot_id');
            $table->string('estimate_hash', 64)->nullable()->after('visitor_key');
            $table->json('events')->nullable()->after('message');
            $table->index(['visitor_key', 'estimate_hash', 'created_at'], 'calculator_requests_dedupe_index');
        });
    }

    public function down(): void
    {
        Schema::table('calculator_requests', function (Blueprint $table) {
            $table->dropIndex('calculator_requests_dedupe_index');
            $table->dropColumn(['visitor_key', 'estimate_hash', 'events']);
        });
    }
};
