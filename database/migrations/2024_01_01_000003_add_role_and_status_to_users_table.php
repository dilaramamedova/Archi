<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->after('name')->nullable();
            $table->string('last_name')->after('first_name')->nullable();
            $table->string('phone')->after('email')->nullable()->unique();
            $table->string('role')->after('phone')->default('buyer');
            $table->string('status')->after('role')->default('pending');
            $table->text('rejection_reason')->after('status')->nullable();
            $table->timestamp('approved_at')->after('rejection_reason')->nullable();
            $table->boolean('terms_accepted')->after('approved_at')->default(false);

            $table->index('role');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'first_name', 'last_name', 'phone', 'role', 'status',
                'rejection_reason', 'approved_at', 'terms_accepted',
            ]);
        });
    }
};
