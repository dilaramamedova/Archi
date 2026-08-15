<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the admin tables, which query the same rows as the storefront but
 * without its filters.
 *
 * Every storefront composite starts with (is_visible, is_approved), because
 * every storefront query does. The admin panel deliberately does not — a
 * moderator's whole job is to see the products that are *not* approved yet — so
 * none of those indexes apply, and the product list's `ORDER BY created_at
 * DESC` filesorted all 60k rows on every page: 722 ms to show twenty-five.
 *
 * Same reasoning for the users table, where the admin lists newest-first.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (! $this->hasIndex('products', 'p_admin_created_index')) {
            DB::statement('CREATE INDEX p_admin_created_index ON products (created_at DESC)');
        }

        if (! $this->hasIndex('users', 'users_created_at_index')) {
            DB::statement('CREATE INDEX users_created_at_index ON users (created_at DESC)');
        }

        // Moderation queue: pending products, newest first.
        if (! $this->hasIndex('products', 'p_admin_moderation_index')) {
            DB::statement('CREATE INDEX p_admin_moderation_index ON products (is_approved, rejected_at, created_at DESC)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ([
            ['products', 'p_admin_created_index'],
            ['products', 'p_admin_moderation_index'],
            ['users', 'users_created_at_index'],
        ] as [$table, $index]) {
            if ($this->hasIndex($table, $index)) {
                DB::statement("DROP INDEX {$index} ON {$table}");
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => $index['name'] === $name);
    }
};
