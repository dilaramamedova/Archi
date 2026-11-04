<?php

declare(strict_types=1);

use App\Models\CalculatorCoefficient;
use App\Models\CalculatorFinish;
use App\Models\CalculatorMaterialFallback;
use App\Models\CalculatorMaterialNorm;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Models\Translation;
use App\Support\VersionedCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The simplified calculator (2026-11).
 *
 * The engine no longer owns a classification of finishes: a finish is a row of the
 * new calculator_finishes table carrying its labour rate and its material prices.
 * This migration creates that table and fills it from the old data — the labour
 * rate of the finish's old work-rate row and the material prices of its old
 * fallback row, so an admin's edits survive — then retires what the engine stopped
 * reading: the finish work rates, the finish fallbacks, the format / layout
 * coefficients, the per-finish waste norms, the HVAC / ventilation / partition /
 * supervision works and the settings behind them. Retired rows are deactivated,
 * never deleted, except the finish fallbacks whose prices moved.
 *
 * The seeder data files hold the same new rows; on a fresh database this migration
 * runs on empty tables and the seeder fills everything.
 */
return new class extends Migration
{
    /** finish id => [old work id, old material fallback id] */
    private const FINISH_SOURCES = [
        'LAMINATE' => ['FLOOR_LAMINATE_INSTALL', 'MAT_FLOOR_LAMINATE'],
        'SPC' => ['FLOOR_SPC_INSTALL', 'MAT_FLOOR_SPC'],
        'PARQUET' => ['FLOOR_PARQUET_INSTALL', 'MAT_FLOOR_PARQUET'],
        'CARPET' => [null, 'MAT_FLOOR_CARPET'],
        'FLOOR_TILE' => ['FLOOR_TILE_INSTALL', 'MAT_FLOOR_TILE'],
        'FLOOR_STONE' => ['FLOOR_STONE_INSTALL', 'MAT_FLOOR_STONE'],
        'PAINT' => ['WALL_PAINT', 'MAT_WALL_PAINT'],
        'WALLPAPER' => ['WALL_WALLPAPER', 'MAT_WALL_WALLPAPER'],
        'DECOR_PLASTER' => ['WALL_DECOR_PLASTER', 'MAT_WALL_DECOR_PLASTER'],
        'WALL_TILE' => ['TILE_INSTALL_STANDARD', 'MAT_WALL_TILE'],
        'WALL_TILE_LARGE' => ['TILE_INSTALL_LARGE', null],
        'MOSAIC' => ['TILE_INSTALL_MOSAIC', null],
        'WALL_STONE' => ['WALL_STONE_INSTALL', 'MAT_WALL_STONE'],
        'WALL_PANEL' => ['WALL_PANEL_INSTALL', 'MAT_WALL_PANEL'],
        'CEILING_PAINT' => ['CEIL_PAINT', 'MAT_CEIL_PAINT'],
        'GYPSUM_FLAT' => ['CEIL_GYPSUM_FLAT', 'MAT_CEIL_GYPSUM'],
        'GYPSUM_COMPLEX' => ['CEIL_GYPSUM_COMPLEX', null],
        'STRETCH_CEILING' => ['CEIL_STRETCH', 'MAT_CEIL_STRETCH'],
    ];

    /** Fallback rows whose prices moved onto a finish row — removed after the copy. */
    private const FINISH_FALLBACKS = [
        'MAT_FLOOR_LAMINATE', 'MAT_FLOOR_SPC', 'MAT_FLOOR_PARQUET', 'MAT_FLOOR_CARPET', 'MAT_FLOOR_TILE', 'MAT_FLOOR_STONE',
        'MAT_WALL_PAINT', 'MAT_WALL_WALLPAPER', 'MAT_WALL_DECOR_PLASTER', 'MAT_WALL_TILE', 'MAT_WALL_PANEL', 'MAT_WALL_STONE',
        'MAT_CEIL_PAINT', 'MAT_CEIL_GYPSUM', 'MAT_CEIL_STRETCH',
    ];

    private const RETIRED_SETTINGS = [
        'DELIVERY_VEHICLE_WEIGHT_CAPACITY', 'DELIVERY_VEHICLE_VOLUME_CAPACITY', 'SPLIT_INCLUDED_ROUTE_METERS',
        'PLASTER_THICK_THRESHOLD_MM', 'SELF_LEVEL_DEFAULT_THICKNESS_MM',
    ];

    private const NEW_TRANSLATION_KEYS = [
        'states.bare', 'states.prepared', 'states.old', 'conditions.prepared',
        'families.coat', 'families.tile', 'families.panel', 'families.floating', 'families.suspended',
        'line.work', 'line.material', 'object.rewire', 'object.rewire_hint', 'object.demolition_hint',
        'rooms.fixtures_label', 'fixtures.toilet', 'fixtures.basin', 'fixtures.shower', 'fixtures.bathtub',
        'fixtures.kitchen_sink', 'fixtures.mixer', 'points.ac', 'rooms.state_hint', 'rooms.derived_short',
        'works.auto_hint', 'side.delivery_note',
    ];

    public function up(): void
    {
        Schema::create('calculator_finishes', function (Blueprint $table) {
            $table->id();
            $table->string('finish_id', 64)->unique();
            $table->string('surface', 16)->index();
            $table->string('family', 16)->index();
            $table->json('name');
            $table->string('unit', 24)->default('m²');
            $table->decimal('labour_rate', 12, 4)->nullable();
            $table->decimal('minimum_charge', 12, 2)->default(0);
            $table->decimal('material_economy', 12, 4)->nullable();
            $table->decimal('material_standard', 12, 4)->nullable();
            $table->decimal('material_premium', 12, 4)->nullable();
            $table->decimal('waste_percent', 6, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->text('search_keywords')->nullable();
            $table->text('notes')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $this->seedFinishesFromOldRows();
        $this->retireOldRows();
        $this->addNewRows();
        $this->upsertTranslations();

        VersionedCache::bump(VersionedCache::CALCULATOR);
        Translation::clearCache();
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_finishes');

        Translation::query()->where('group', 'calculator-detailed')->whereIn('key', self::NEW_TRANSLATION_KEYS)->delete();
        Translation::clearCache();
        VersionedCache::bump(VersionedCache::CALCULATOR);
    }

    /**
     * Every finish of the data file, priced from the rows it replaces when those
     * exist (an admin may have edited them) and from the data file otherwise.
     */
    private function seedFinishesFromOldRows(): void
    {
        $draft = 'DRAFT — 2026 Bakı bazar araşdırması; Archi tərəfindən təsdiqlənməlidir.';
        $sort = 0;

        foreach (require database_path('seeders/data/calculator_finishes.php') as $row) {
            [$workId, $fallbackId] = self::FINISH_SOURCES[$row['id']] ?? [null, null];

            $work = $workId ? CalculatorWorkRate::query()->where('work_id', $workId)->first() : null;
            $fallback = $fallbackId ? CalculatorMaterialFallback::query()->where('material_category_id', $fallbackId)->first() : null;

            CalculatorFinish::query()->updateOrCreate(
                ['finish_id' => $row['id']],
                [
                    'surface' => $row['surface'],
                    'family' => $row['family'],
                    'name' => $row['name'],
                    'unit' => $row['unit'] ?? 'm²',
                    'labour_rate' => $work?->rate ?? $row['labour'],
                    'minimum_charge' => $work?->minimum_charge ?? ($row['min'] ?? 0),
                    'material_economy' => $fallback?->economy_price ?? $row['economy'],
                    'material_standard' => $fallback?->standard_price ?? $row['standard'],
                    'material_premium' => $fallback?->premium_price ?? $row['premium'],
                    'waste_percent' => $row['waste'] ?? 0,
                    'is_default' => $row['default'] ?? false,
                    'search_keywords' => $row['keywords'] ?? null,
                    'notes' => $row['notes'] ?? $draft,
                    'sort_order' => $sort++,
                    'is_active' => true,
                ],
            );
        }
    }

    private function retireOldRows(): void
    {
        $keptWorks = array_column(require database_path('seeders/data/calculator_work_rates.php'), 'work_id');
        CalculatorWorkRate::query()->whereNotIn('work_id', $keptWorks)->update(['is_active' => false]);

        CalculatorMaterialFallback::query()->whereIn('material_category_id', self::FINISH_FALLBACKS)->delete();
        $keptFallbacks = array_column(require database_path('seeders/data/calculator_material_fallbacks.php'), 'id');
        CalculatorMaterialFallback::query()->whereNotIn('material_category_id', $keptFallbacks)->update(['is_active' => false]);

        CalculatorCoefficient::query()
            ->whereNotIn('coefficient_id', ['K_BASE', 'K_HEIGHT_3_01_3_50', 'K_HEIGHT_3_51_4_00', 'K_HEIGHT_OVER_4'])
            ->update(['is_active' => false]);

        CalculatorMaterialNorm::query()
            ->whereIn('rule_id', ['WASTE_TILE_STANDARD', 'WASTE_TILE_DIAGONAL', 'WASTE_LAMINATE', 'WASTE_SPC', 'WASTE_PARQUET', 'SELF_LEVEL_CONSUMPTION'])
            ->update(['is_active' => false]);

        CalculatorSetting::query()->whereIn('key', self::RETIRED_SETTINGS)->delete();
    }

    /**
     * The rows the simplified engine reads that did not exist before: the generic
     * light-fitting work, the cable and warm-floor materials, the four norms behind
     * the derived modules. Renamed labels of surviving works are refreshed too.
     */
    private function addNewRows(): void
    {
        $draft = 'DRAFT — 2026 Bakı bazar araşdırması; Archi tərəfindən təsdiqlənməlidir.';

        foreach (require database_path('seeders/data/calculator_work_rates.php') as $row) {
            $existing = CalculatorWorkRate::query()->where('work_id', $row['work_id'])->first();

            if ($existing) {
                // Labels and keywords follow the simplified wording; the price is the admin's.
                $existing->update([
                    'name' => $row['name'],
                    'search_keywords' => $row['keywords'] ?? $existing->search_keywords,
                    'is_active' => true,
                ]);

                continue;
            }

            CalculatorWorkRate::query()->create([
                'work_id' => $row['work_id'],
                'category' => $row['category'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'quantity_source' => $row['quantity_source'],
                'formula' => $row['formula'] ?? 'Q × Rate',
                'rate' => $row['rate'],
                'minimum_charge' => $row['min'] ?? 0,
                'precision_class' => $row['precision'] ?? 'CALCULATED',
                'contingency_eligible' => $row['contingency'] ?? true,
                'notes' => $row['notes'] ?? $draft,
                'search_keywords' => $row['keywords'] ?? null,
                'sort_order' => (int) CalculatorWorkRate::query()->max('sort_order') + 1,
                'is_active' => true,
            ]);
        }

        $sort = (int) CalculatorMaterialFallback::query()->max('sort_order');

        foreach (require database_path('seeders/data/calculator_material_fallbacks.php') as $row) {
            if (CalculatorMaterialFallback::query()->where('material_category_id', $row['id'])->exists()) {
                continue;
            }

            CalculatorMaterialFallback::query()->create([
                'material_category_id' => $row['id'],
                'surface_group' => $row['group'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'economy_price' => $row['economy'],
                'standard_price' => $row['standard'],
                'premium_price' => $row['premium'],
                'notes' => $row['notes'] ?? null,
                'sort_order' => ++$sort,
                'is_active' => true,
            ]);
        }

        $settings = [
            ['key' => 'DELIVERY_AREA_PER_TRIP', 'group' => 'LOGISTICS', 'type' => 'decimal', 'value' => 40, 'unit' => 'm²/reys',
                'description' => ['az' => 'Bir çatdırılma reysinə düşən obyekt sahəsi', 'ru' => 'Площадь объекта на один рейс доставки', 'en' => 'Object area per delivery trip'],
                'notes' => '80 m² obyekt = 2 reys. Materialların çəkisi kataloqda çox vaxt olmadığı üçün çatdırılma sahəyə görə təxmin edilir.'],
            ['key' => 'MATERIAL_KG_PER_M2', 'group' => 'LOGISTICS', 'type' => 'decimal', 'value' => 25, 'unit' => 'kq/m²',
                'description' => ['az' => 'Obyektin 1 m²-nə düşən materialın təxmini çəkisi', 'ru' => 'Примерный вес материалов на 1 м² объекта', 'en' => 'Estimated material weight per m² of the object'],
                'notes' => 'Lift olmayanda əl ilə qaldırma bu çəki ilə hesablanır.'],
            ['key' => 'CABLE_M_PER_POINT', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 7, 'unit' => 'm/nöqtə',
                'description' => ['az' => 'Hər elektrik nöqtəsinə düşən kabel uzunluğu', 'ru' => 'Длина кабеля на одну электроточку', 'en' => 'Cable length per electrical point'],
                'notes' => 'Yalnız «Elektrik tam yenilənsin» açıq olanda işləyir.'],
            ['key' => 'PIPE_M_PER_POINT', 'group' => 'ENGINEERING', 'type' => 'decimal', 'value' => 3, 'unit' => 'm/nöqtə',
                'description' => ['az' => 'Hər su / kanalizasiya nöqtəsinə düşən boru uzunluğu', 'ru' => 'Длина трубы на одну точку воды / канализации', 'en' => 'Pipe length per water / sewer point']],
        ];

        foreach ($settings as $setting) {
            CalculatorSetting::query()->firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    private function upsertTranslations(): void
    {
        $rows = require database_path('seeders/data/calculator_translations.php');

        foreach (self::NEW_TRANSLATION_KEYS as $key) {
            if (isset($rows['calculator-detailed'][$key])) {
                Translation::updateOrCreate(
                    ['group' => 'calculator-detailed', 'key' => $key],
                    ['value' => $rows['calculator-detailed'][$key]],
                );
            }
        }
    }
};
