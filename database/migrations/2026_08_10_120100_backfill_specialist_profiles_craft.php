<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * craft is a denormalized snapshot of the specialty name. Re-sync it from
     * the (now translatable) Azerbaijani name so renames made before this
     * release are no longer stale. Profiles without a specialty keep their
     * legacy free-text craft.
     */
    public function up(): void
    {
        DB::table('specialist_specialties')->orderBy('id')->select('id', 'name')
            ->each(function (object $row): void {
                $decoded = json_decode((string) $row->name, true);
                $az = is_array($decoded) ? ($decoded['az'] ?? (reset($decoded) ?: null)) : (string) $row->name;

                if ($az === null || $az === '') {
                    return;
                }

                DB::table('specialist_profiles')
                    ->where('specialist_specialty_id', $row->id)
                    ->update(['craft' => $az]);
            });
    }

    public function down(): void
    {
        // Data-only backfill — nothing to revert.
    }
};
