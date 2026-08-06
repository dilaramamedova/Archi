<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Moderation: is_approved=false + rejected_at=null  → pending review
            //             is_approved=false + rejected_at set   → rejected (reason shown to seller)
            //             is_approved=true                      → published
            $table->timestamp('rejected_at')->nullable()->after('is_approved');
            $table->string('rejection_reason', 500)->nullable()->after('rejected_at');
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->json('notification_settings')->nullable()->after('languages');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });

        Schema::table('seller_profiles', function (Blueprint $table) {
            $table->dropColumn('notification_settings');
        });
    }
};
