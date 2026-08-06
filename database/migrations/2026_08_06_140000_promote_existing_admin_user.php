<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'admin@archi.test')
            ->update([
                'role' => 'admin',
                'status' => 'active',
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'admin@archi.test')
            ->where('role', 'admin')
            ->update(['role' => 'buyer']);
    }
};
