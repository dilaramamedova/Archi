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
 * Applies a calculator TZ data file — database/seeders/data/calculator_tz_2026_11.php
 * (TZ of 17.11.2026: stages, new works and materials, settings, room types) by default,
 * or another file of the same shape (TZ №2 of 19.11.2026: CalculatorTz2Seeder).
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

    /** The TZ data file, relative to database/seeders/data. */
    protected string $dataFile = 'calculator_tz_2026_11.php';

    public function run(): void
    {
        $this->apply($this->fresh);
    }

    public function apply(bool $fresh): void
    {
        $tz = require database_path('seeders/data/'.$this->dataFile);

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

        foreach ($tz['works'] ?? [] as $row) {
            $work = CalculatorWorkRate::where('work_id', $row['work_id'])->first();

            if ($work && ! $fresh) {
                continue; // Archi's row, Archi's rate.
            }

            $rate = $row['rate'];

            // «A track light costs more than a spot» (TZ №2 §44): a new draft rate never
            // lands at or below the rate Archi gave the cheaper equivalent.
            if (isset($row['above'])) {
                $floor = (float) CalculatorWorkRate::where('work_id', $row['above'])->value('rate');
                $rate = $floor >= $rate ? round($floor * 1.5, 2) : $rate;
            }

            CalculatorWorkRate::updateOrCreate(['work_id' => $row['work_id']], [
                'category' => $row['category'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'quantity_source' => $row['quantity_source'],
                'formula' => $row['formula'] ?? 'Q × Rate',
                'rate' => $rate,
                'minimum_charge' => $row['min'] ?? 0,
                'precision_class' => 'CALCULATED',
                'contingency_eligible' => true,
                'notes' => $row['notes'] ?? null,
                'search_keywords' => $row['keywords'] ?? null,
                'sort_order' => $work?->sort_order ?? $sort++,
                'is_active' => true,
            ]);
        }

        CalculatorWorkRate::whereIn('work_id', $tz['activate_works'] ?? [])->update(['is_active' => true]);
        CalculatorWorkRate::whereIn('work_id', $tz['deactivate_works'] ?? [])->update(['is_active' => false]);

        foreach ($tz['work_stages'] ?? [] as $stage => $ids) {
            CalculatorWorkRate::whereIn('work_id', $ids)->update(['stage' => $stage]);
        }
    }

    private function fallbacks(array $tz, bool $fresh): void
    {
        $sort = (int) CalculatorMaterialFallback::max('sort_order') + 1;

        foreach ($tz['fallbacks'] ?? [] as $row) {
            $fallback = CalculatorMaterialFallback::where('material_category_id', $row['id'])->first();

            if ($fallback && ! $fresh) {
                continue;
            }

            $prices = ['economy' => $row['economy'], 'standard' => $row['standard'], 'premium' => $row['premium']];

            if (isset($row['above']) && $cheaper = CalculatorMaterialFallback::where('material_category_id', $row['above'])->first()) {
                foreach ($prices as $segment => $price) {
                    $floor = (float) $cheaper->{$segment.'_price'};
                    $prices[$segment] = $floor >= $price ? round($floor * 1.5, 2) : $price;
                }
            }

            CalculatorMaterialFallback::updateOrCreate(['material_category_id' => $row['id']], [
                'surface_group' => $row['group'],
                'name' => $row['name'],
                'unit' => $row['unit'],
                'economy_price' => $prices['economy'],
                'standard_price' => $prices['standard'],
                'premium_price' => $prices['premium'],
                'notes' => $tz['draft_note'] ?? 'DRAFT — TZ 17.11.2026; qiyməti Archi təsdiqləməlidir.',
                'sort_order' => $fallback?->sort_order ?? $sort++,
                'is_active' => true,
            ]);
        }

        CalculatorMaterialFallback::whereIn('material_category_id', $tz['deactivate_fallbacks'] ?? [])->update(['is_active' => false]);

        foreach ($tz['fallback_stages'] ?? [] as $stage => $ids) {
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
        foreach ($tz['finish_stages'] ?? [] as $id => [$labour, $material]) {
            CalculatorFinish::where('finish_id', $id)->update(['labour_stage' => $labour, 'material_stage' => $material]);
        }

        CalculatorFinish::whereIn('finish_id', $tz['finish_underlay'] ?? [])->update(['needs_underlay' => true]);

        foreach ($tz['finish_top_coat'] ?? [] as $id => $topCoat) {
            CalculatorFinish::where('finish_id', $id)->update(['top_coat_finish' => $topCoat]);
        }

        if (empty($tz['finish_paint_norm'])) {
            return;
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
        foreach ($tz['settings'] ?? [] as $row) {
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

    /**
     * A row without `after` takes the next position in the file; one with `after`
     * slots in behind that type and moves the rows below it down by one, so a type
     * added later still sits where it belongs in the admin's list and the room chips.
     */
    private function roomTypes(array $tz, bool $fresh): void
    {
        $sort = 0;

        foreach ($tz['room_types'] ?? [] as $type => $row) {
            $existing = CalculatorRoomType::where('room_type', $type)->first();

            if ($existing && ! $fresh) {
                $sort++;

                continue;
            }

            $position = $existing?->sort_order ?? $sort;

            if (isset($row['after'])) {
                $anchor = CalculatorRoomType::where('room_type', $row['after'])->value('sort_order');

                if ($anchor !== null && ! $existing) {
                    $position = (int) $anchor + 1;
                    CalculatorRoomType::where('sort_order', '>=', $position)->increment('sort_order');
                } elseif ($anchor !== null) {
                    $position = $existing->sort_order;
                }
            }

            CalculatorRoomType::updateOrCreate(['room_type' => $type], [
                'name' => $row['name'],
                'has_plumbing' => $row['plumbing'],
                'needs_waterproofing' => $row['waterproof'],
                'point_norms' => collect($row['points'])->map(fn ($v) => ['base' => $v[0], 'per_m2' => $v[1]])->all(),
                'fixtures' => $row['fixtures'],
                'ac_extra_above_m2' => $row['ac_extra_above'],
                'tv_min_area_m2' => $row['tv_min_area'],
                'sort_order' => $position,
                'is_active' => true,
            ]);

            $sort = $position + 1;
        }
    }
}
