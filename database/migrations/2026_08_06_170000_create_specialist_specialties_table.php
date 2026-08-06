<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('specialist_specialties', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('specialist_profiles', function (Blueprint $table): void {
            $table->foreignId('specialist_specialty_id')->nullable()->after('user_id')
                ->constrained('specialist_specialties')->nullOnDelete();
        });

        $crafts = DB::table('specialist_profiles')->whereNotNull('craft')->distinct()->pluck('craft');

        foreach ($crafts as $index => $craft) {
            $baseSlug = Str::slug((string) $craft) ?: 'ixtisas-'.($index + 1);
            $slug = $baseSlug;
            $suffix = 2;

            while (DB::table('specialist_specialties')->where('slug', $slug)->exists()) {
                $slug = $baseSlug.'-'.$suffix++;
            }

            $specialtyId = DB::table('specialist_specialties')->insertGetId([
                'name' => $craft,
                'slug' => $slug,
                'is_active' => true,
                'sort_order' => $index,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('specialist_profiles')->where('craft', $craft)->update([
                'specialist_specialty_id' => $specialtyId,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('specialist_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('specialist_specialty_id');
        });

        Schema::dropIfExists('specialist_specialties');
    }
};
