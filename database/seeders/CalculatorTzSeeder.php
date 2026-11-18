<?php

namespace Database\Seeders;

use App\Models\CalculatorFinish;
use App\Models\CalculatorMaterialFallback;
use App\Models\CalculatorRoomType;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Support\VersionedCache;
use Illuminate\Database\Seeder;

/**
 * Applies database/seeders/data/calculator_tz_2026_11.php — the calculator TZ of
 * 17.11.2026 (stages, new works and materials, settings, room types).
 *
 * Two modes:
 *   - production migration (`$fresh = false`): inserts what is missing, switches rows on
 *     and off, sets stages and flags — but never overwrites a price, a name or a
 *     setting value Archi has already edited in the admin panel;
 *   - CalculatorSeeder (`$fresh = true`): writes the rows in full, for a new install and
 *     the tests.
 */
class CalculatorTzSeeder extends Seeder
{
    public bool $fresh = true;

    public function run(): void
    {
        $this->apply($this->fresh);
    }

    public function apply(bool $fresh): void
    {
        $tz = require database_path('seeders/data/calculator_tz_2026_11.php');

        $this->works($tz, $fresh);
        $this->fallbacks($tz, $fresh);
        $this->finishes($tz);
        $this->settings($tz, $fresh);
        $this->roomTypes($tz, $fresh);

        VersionedCache::bump(VersionedCache::CALCULATOR);
    }

    private function works(array $tz, bool $fresh): void
    {
        $sort = (int) CalculatorWorkRate::max('sort_order') + 1;

        foreach ($tz['works'] as $row) {
            $work = CalculatorWorkRate::where('work_id', $row['work_id'])->first();

            if ($work && ! $fresh) {
                continue; // Archi's row, Archi's rate.
            }

            CalculatorWorkRate::updateOrCreate(['work_id' => $row['work_id']], [
                'category' => $row['category'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'quantity_source' => $row['quantity_source'],
                'formula' => $row['formula'] ?? 'Q × Rate',
                'rate' => $row['rate'],
                'minimum_charge' => $row['min'] ?? 0,
                'precision_class' => 'CALCULATED',
                'contingency_eligible' => true,
                'notes' => $row['notes'] ?? null,
                'search_keywords' => $row['keywords'] ?? null,
                'sort_order' => $work?->sort_order ?? $sort++,
                'is_active' => true,
            ]);
        }

        CalculatorWorkRate::whereIn('work_id', $tz['activate_works'])->update(['is_active' => true]);
        CalculatorWorkRate::whereIn('work_id', $tz['deactivate_works'])->update(['is_active' => false]);

        foreach ($tz['work_stages'] as $stage => $ids) {
            CalculatorWorkRate::whereIn('work_id', $ids)->update(['stage' => $stage]);
        }
    }

    private function fallbacks(array $tz, bool $fresh): void
    {
        $sort = (int) CalculatorMaterialFallback::max('sort_order') + 1;

        foreach ($tz['fallbacks'] as $row) {
            $fallback = CalculatorMaterialFallback::where('material_category_id', $row['id'])->first();

            if ($fallback && ! $fresh) {
                continue;
            }

            CalculatorMaterialFallback::updateOrCreate(['material_category_id' => $row['id']], [
                'surface_group' => $row['group'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'economy_price' => $row['economy'],
                'standard_price' => $row['standard'],
                'premium_price' => $row['premium'],
                'notes' => 'DRAFT — TZ 17.11.2026; qiyməti Archi təsdiqləməlidir.',
                'sort_order' => $fallback?->sort_order ?? $sort++,
                'is_active' => true,
            ]);
        }

        CalculatorMaterialFallback::whereIn('material_category_id', $tz['deactivate_fallbacks'])->update(['is_active' => false]);

        foreach ($tz['fallback_stages'] as $stage => $ids) {
            CalculatorMaterialFallback::whereIn('material_category_id', $ids)->update(['stage' => $stage]);
        }
    }

    /**
     * Stages and flags. Paint moves from «AZN per m²» to «AZN per litre»: the stored
     * price is converted with the same coverage and coat count the engine now uses,
     * so the estimate for a painted wall stays exactly where Archi put it.
     */
    private function finishes(array $tz): void
    {
        foreach ($tz['finish_stages'] as $id => [$labour, $material]) {
            CalculatorFinish::where('finish_id', $id)->update(['labour_stage' => $labour, 'material_stage' => $material]);
        }

        CalculatorFinish::whereIn('finish_id', $tz['finish_underlay'])->update(['needs_underlay' => true]);

        foreach ($tz['finish_top_coat'] as $id => $topCoat) {
            CalculatorFinish::where('finish_id', $id)->update(['top_coat_finish' => $topCoat]);
        }

        $coverage = (float) (\App\Models\CalculatorMaterialNorm::where('rule_id', 'PAINT_COVERAGE')->value('value') ?: 10);
        $coats = (float) (CalculatorSetting::where('key', 'DEFAULT_PAINT_COATS')->value('value') ?: 2);
        $perLitre = $coats > 0 ? $coverage / $coats : 1.0;

        foreach (CalculatorFinish::whereIn('finish_id', $tz['finish_paint_norm'])->where('paint_norm', false)->get() as $finish) {
            $convert = fn ($price) => $price === null ? null : round((float) $price * $perLitre, 4);

            $finish->forceFill([
                'paint_norm' => true,
                'material_economy' => $convert($finish->material_economy),
                'material_standard' => $convert($finish->material_standard),
                'material_premium' => $convert($finish->material_premium),
            ])->save();
        }
    }

    private function settings(array $tz, bool $fresh): void
    {
        foreach ($tz['settings'] as $row) {
            if (! $fresh && CalculatorSetting::where('key', $row['key'])->exists()) {
                continue;
            }

            CalculatorSetting::updateOrCreate(['key' => $row['key']], [
                'group' => $row['group'],
                'description' => $row['description'],
                'value' => (string) $row['value'],
                'type' => $row['type'],
                'unit' => $row['unit'] ?? null,
            ]);
        }
    }

    private function roomTypes(array $tz, bool $fresh): void
    {
        $sort = 0;

        foreach ($tz['room_types'] as $type => $row) {
            if (! $fresh && CalculatorRoomType::where('room_type', $type)->exists()) {
                $sort++;

                continue;
            }

            CalculatorRoomType::updateOrCreate(['room_type' => $type], [
                'name' => $row['name'],
                'has_plumbing' => $row['plumbing'],
                'needs_waterproofing' => $row['waterproof'],
                'point_norms' => collect($row['points'])->map(fn ($v) => ['base' => $v[0], 'per_m2' => $v[1]])->all(),
                'fixtures' => $row['fixtures'],
                'ac_extra_above_m2' => $row['ac_extra_above'],
                'tv_min_area_m2' => $row['tv_min_area'],
                'sort_order' => $sort++,
                'is_active' => true,
            ]);
        }
    }
}
