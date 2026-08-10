<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert specialist_specialties.name from a plain string to a Spatie
     * translatable json column, wrapping every current value as {"az": "..."}.
     * The unique index on name cannot survive on a json column, so it is
     * dropped — slug stays unique and is the real identity key.
     */
    public function up(): void
    {
        Schema::table('specialist_specialties', function (Blueprint $table): void {
            $table->dropUnique('specialist_specialties_name_unique');
            $table->json('name_translations')->nullable()->after('name');
        });

        DB::table('specialist_specialties')->orderBy('id')->select('id', 'name')
            ->each(function (object $row): void {
                DB::table('specialist_specialties')->where('id', $row->id)->update([
                    'name_translations' => json_encode(['az' => (string) $row->name], JSON_UNESCAPED_UNICODE),
                ]);
            });

        Schema::table('specialist_specialties', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('specialist_specialties', function (Blueprint $table): void {
            $table->renameColumn('name_translations', 'name');
        });
    }

    public function down(): void
    {
        Schema::table('specialist_specialties', function (Blueprint $table): void {
            $table->string('name_plain')->nullable()->after('name');
        });

        // The json schema only keeps slug unique, so two specialties may share
        // an Azerbaijani name. The old unique index cannot take those as-is —
        // disambiguate with the slug instead of blowing up mid-rollback.
        $seen = [];

        DB::table('specialist_specialties')->orderBy('id')->select('id', 'name', 'slug')
            ->each(function (object $row) use (&$seen): void {
                $decoded = json_decode((string) $row->name, true);
                $plain = (string) (is_array($decoded)
                    ? ($decoded['az'] ?? (reset($decoded) ?: ''))
                    : (string) $row->name);

                if ($plain === '' || isset($seen[$plain])) {
                    $plain = trim($plain.' ('.$row->slug.')');
                }

                $seen[$plain] = true;

                DB::table('specialist_specialties')->where('id', $row->id)->update([
                    'name_plain' => $plain,
                ]);
            });

        Schema::table('specialist_specialties', function (Blueprint $table): void {
            $table->dropColumn('name');
        });

        Schema::table('specialist_specialties', function (Blueprint $table): void {
            $table->renameColumn('name_plain', 'name');
        });

        Schema::table('specialist_specialties', function (Blueprint $table): void {
            $table->string('name')->nullable(false)->change();
            $table->unique('name', 'specialist_specialties_name_unique');
        });
    }
};
