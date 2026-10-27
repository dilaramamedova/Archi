<?php

declare(strict_types=1);

namespace App\Services\Laminate;

use App\Enums\Laminate\HeatingType;
use App\Enums\Laminate\Tri;
use App\Enums\Laminate\WarrantyScope;
use App\Enums\Laminate\WaterClaim;
use App\Models\Attribute;
use App\Models\LaminateSpec;
use App\Support\IsoCountries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Catalog filters of the laminate class (TZ «Ламинат» §9 «Фильтры»).
 *
 * AND between filters, OR between the values of one filter. "Not stated" never
 * matches a positive filter: a card whose water protection is unknown is not in
 * "has water protection", a moisture-resistant board is not "water resistant"
 * because of its hours, and floor heating with unstated systems matches the general
 * heating filter but no specific system (acceptance 7, 8, 9).
 *
 * Every condition is an EXISTS on the product's own card, so a product with two
 * warranties or two heating systems is still counted once (acceptance 20).
 *
 * Query parameters (comma-separated lists):
 *   lam_country=DE,BE        lam_usage=32,33       lam_ac=ac4,ac5
 *   lam_connection=click     lam_thickness=8,10    lam_size=1380x193,1285x192
 *   lam_water=any|water_resistant|moisture_resistant
 *   lam_heating=any|water|electric_cable|electric_film
 *   lam_underlay=yes|no      lam_warranty=residential|commercial
 *   lam_pack_min=1.5         lam_pack_max=2.5
 */
final class LaminateFilters
{
    public const PARAMS = [
        'lam_country', 'lam_usage', 'lam_ac', 'lam_connection', 'lam_thickness', 'lam_size',
        'lam_water', 'lam_heating', 'lam_underlay', 'lam_warranty', 'lam_pack_min', 'lam_pack_max',
    ];

    /**
     * @return array<string, mixed> the filters that took effect, as the sidebar needs them
     */
    public function apply(Builder $query, Request $request): array
    {
        $active = [];

        $countries = array_values(array_filter(
            array_map('strtoupper', $this->list($request, 'lam_country')),
            fn ($c) => IsoCountries::isValid($c),
        ));
        if ($countries) {
            $active['lam_country'] = $countries;
        }

        foreach (['lam_usage' => LaminateCatalog::ATTR_USAGE, 'lam_ac' => LaminateCatalog::ATTR_ABRASION, 'lam_connection' => LaminateCatalog::ATTR_CONNECTION] as $param => $slug) {
            $ids = LaminateCatalog::optionIds($slug);
            $codes = array_values(array_filter($this->list($request, $param), fn ($c) => isset($ids[$c])));
            if ($codes) {
                $active[$param] = $codes;
            }
        }

        $thickness = [];
        foreach ($this->list($request, 'lam_thickness') as $raw) {
            [$value] = LaminateDecimal::parse($raw, 4, 8);
            if ($value !== null) {
                $thickness[] = $value;
            }
        }
        if ($thickness) {
            $active['lam_thickness'] = array_values(array_unique($thickness));
        }

        $sizes = [];
        foreach ($this->list($request, 'lam_size') as $raw) {
            $parts = preg_split('/[x×X*]/u', $raw);
            if (count($parts) === 2) {
                [$l] = LaminateDecimal::parse($parts[0], 4, 8);
                [$w] = LaminateDecimal::parse($parts[1], 4, 8);
                if ($l !== null && $w !== null) {
                    $sizes[$l.'x'.$w] = [$l, $w];
                }
            }
        }
        if ($sizes) {
            $active['lam_size'] = array_keys($sizes);
        }

        $water = array_values(array_intersect($this->list($request, 'lam_water'), ['any', WaterClaim::WaterResistant->value, WaterClaim::MoistureResistant->value]));
        if ($water) {
            $active['lam_water'] = $water;
        }

        $heating = array_values(array_intersect($this->list($request, 'lam_heating'), ['any', ...array_column(HeatingType::cases(), 'value')]));
        if ($heating) {
            $active['lam_heating'] = $heating;
        }

        $underlay = array_values(array_intersect($this->list($request, 'lam_underlay'), [Tri::Yes->value, Tri::No->value]));
        if ($underlay) {
            $active['lam_underlay'] = $underlay;
        }

        $warranty = array_values(array_intersect($this->list($request, 'lam_warranty'), array_column(WarrantyScope::cases(), 'value')));
        if ($warranty) {
            $active['lam_warranty'] = $warranty;
        }

        [$packMin] = LaminateDecimal::parse($this->scalar($request, 'lam_pack_min'), 4, 8);
        [$packMax] = LaminateDecimal::parse($this->scalar($request, 'lam_pack_max'), 4, 8);
        if ($packMin !== null) {
            $active['lam_pack_min'] = $packMin;
        }
        if ($packMax !== null) {
            $active['lam_pack_max'] = $packMax;
        }

        if ($active === []) {
            return [];
        }

        $query->whereHas('laminateSpec', function (Builder $spec) use ($active, $sizes): void {
            if (isset($active['lam_country'])) {
                $spec->whereIn('country_code', $active['lam_country']);
            }

            foreach (['lam_usage' => ['usage_class_option_id', LaminateCatalog::ATTR_USAGE], 'lam_ac' => ['abrasion_option_id', LaminateCatalog::ATTR_ABRASION], 'lam_connection' => ['connection_option_id', LaminateCatalog::ATTR_CONNECTION]] as $param => [$column, $slug]) {
                if (isset($active[$param])) {
                    $ids = LaminateCatalog::optionIds($slug);
                    $spec->whereIn($column, array_map(fn ($c) => $ids[$c], $active[$param]));
                }
            }

            if (isset($active['lam_thickness'])) {
                $spec->whereIn('thickness_mm', $active['lam_thickness']);
            }

            // Sizes are matched as pairs, never as two independent lists — 1380×193
            // must not match a 1380×190 board just because 193 exists elsewhere.
            if ($sizes) {
                $spec->where(function (Builder $q) use ($sizes): void {
                    foreach ($sizes as [$l, $w]) {
                        $q->orWhere(fn (Builder $p) => $p->where('length_mm', $l)->where('width_mm', $w));
                    }
                });
            }

            if (isset($active['lam_water'])) {
                $spec->where(function (Builder $q) use ($active): void {
                    foreach ($active['lam_water'] as $value) {
                        $q->orWhere(function (Builder $c) use ($value): void {
                            $c->where('water_protection', Tri::Yes->value);
                            if ($value !== 'any') {
                                $c->where('water_claim', $value);
                            }
                        });
                    }
                });
            }

            if (isset($active['lam_heating'])) {
                $spec->where(function (Builder $q) use ($active): void {
                    foreach ($active['lam_heating'] as $value) {
                        $q->orWhere(function (Builder $c) use ($value): void {
                            $c->where('floor_heating', Tri::Yes->value);
                            if ($value !== 'any') {
                                // A specific system needs the systems to be KNOWN and
                                // this one among them (acceptance 9).
                                $c->where('heating_types_known', true)
                                    ->whereExists(fn ($e) => $e->select(DB::raw(1))
                                        ->from('laminate_spec_heating_types')
                                        ->whereColumn('laminate_spec_heating_types.laminate_spec_id', 'laminate_specs.id')
                                        ->where('heating_type_code', $value));
                            }
                        });
                    }
                });
            }

            if (isset($active['lam_underlay'])) {
                // unknown is not "no" (TZ §9).
                $spec->whereIn('integrated_underlay', $active['lam_underlay']);
            }

            if (isset($active['lam_warranty'])) {
                $spec->where('warranty', Tri::Yes->value)
                    ->whereExists(fn ($e) => $e->select(DB::raw(1))
                        ->from('laminate_spec_warranties')
                        ->whereColumn('laminate_spec_warranties.laminate_spec_id', 'laminate_specs.id')
                        ->whereIn('scope', $active['lam_warranty']));
            }

            if (isset($active['lam_pack_min'])) {
                $spec->where('pack_area_m2', '>=', $active['lam_pack_min']);
            }

            if (isset($active['lam_pack_max'])) {
                $spec->where('pack_area_m2', '<=', $active['lam_pack_max']);
            }
        });

        return $active;
    }

    /**
     * Values present among published laminate cards, with how many products carry
     * each. Only values that exist are offered — a filter chip that can only ever
     * return nothing is a broken filter.
     *
     * @return array<string, mixed>
     */
    public function facets(int $classId): array
    {
        $base = fn () => LaminateSpec::query()
            ->join('products', 'products.id', '=', 'laminate_specs.product_id')
            ->where('products.sub_category_id', $classId)
            ->where('products.is_visible', true)
            ->where('products.is_approved', true);

        $countries = $base()->whereNotNull('country_code')
            ->groupBy('country_code')->selectRaw('country_code, count(*) as n')->pluck('n', 'country_code');

        $options = [];
        foreach (['usage' => ['usage_class_option_id', LaminateCatalog::ATTR_USAGE], 'ac' => ['abrasion_option_id', LaminateCatalog::ATTR_ABRASION], 'connection' => ['connection_option_id', LaminateCatalog::ATTR_CONNECTION]] as $key => [$column, $slug]) {
            $counts = $base()->whereNotNull($column)->groupBy($column)->selectRaw("$column as id, count(*) as n")->pluck('n', 'id');
            $attribute = Attribute::query()->where('slug', $slug)->with('options')->first();
            $options[$key] = $attribute
                ? $attribute->options
                    ->filter(fn ($o) => isset($counts[$o->id]))
                    ->map(fn ($o) => ['code' => $o->slug, 'label' => (string) $o->value, 'count' => (int) $counts[$o->id]])
                    ->values()->all()
                : [];
        }

        $thickness = $base()->whereNotNull('thickness_mm')
            ->groupBy('thickness_mm')->orderBy('thickness_mm')
            ->selectRaw('thickness_mm as v, count(*) as n')->get()
            ->map(fn ($r) => ['value' => LaminateDecimal::trim((string) $r->v), 'count' => (int) $r->n])->all();

        $sizes = $base()->whereNotNull('length_mm')->whereNotNull('width_mm')
            ->groupBy('length_mm', 'width_mm')->orderBy('length_mm')->orderBy('width_mm')
            ->selectRaw('length_mm as l, width_mm as w, count(*) as n')->get()
            ->map(function ($r) {
                $l = LaminateDecimal::trim((string) $r->l);
                $w = LaminateDecimal::trim((string) $r->w);

                return ['value' => $l.'x'.$w, 'label' => $l.' × '.$w, 'count' => (int) $r->n];
            })->all();

        $water = [
            'any' => $base()->where('water_protection', Tri::Yes->value)->count(),
            WaterClaim::WaterResistant->value => $base()->where('water_protection', Tri::Yes->value)->where('water_claim', WaterClaim::WaterResistant->value)->count(),
            WaterClaim::MoistureResistant->value => $base()->where('water_protection', Tri::Yes->value)->where('water_claim', WaterClaim::MoistureResistant->value)->count(),
        ];

        $heating = ['any' => $base()->where('floor_heating', Tri::Yes->value)->count()];
        foreach (HeatingType::cases() as $type) {
            $heating[$type->value] = $base()->where('floor_heating', Tri::Yes->value)->where('heating_types_known', true)
                ->whereExists(fn ($e) => $e->select(DB::raw(1))->from('laminate_spec_heating_types')
                    ->whereColumn('laminate_spec_heating_types.laminate_spec_id', 'laminate_specs.id')
                    ->where('heating_type_code', $type->value))
                ->count();
        }

        $underlay = [
            Tri::Yes->value => $base()->where('integrated_underlay', Tri::Yes->value)->count(),
            Tri::No->value => $base()->where('integrated_underlay', Tri::No->value)->count(),
        ];

        $warranty = [];
        foreach (WarrantyScope::cases() as $scope) {
            $warranty[$scope->value] = $base()->where('warranty', Tri::Yes->value)
                ->whereExists(fn ($e) => $e->select(DB::raw(1))->from('laminate_spec_warranties')
                    ->whereColumn('laminate_spec_warranties.laminate_spec_id', 'laminate_specs.id')
                    ->where('scope', $scope->value))
                ->count();
        }

        $pack = $base()->whereNotNull('pack_area_m2')->selectRaw('min(pack_area_m2) as lo, max(pack_area_m2) as hi')->first();

        return [
            'countries' => collect($countries)->map(fn ($n, $code) => [
                'code' => $code,
                'name' => IsoCountries::name($code),
                'count' => (int) $n,
            ])->sortBy('name')->values()->all(),
            'usage' => $options['usage'],
            'ac' => $options['ac'],
            'connection' => $options['connection'],
            'thickness' => $thickness,
            'sizes' => $sizes,
            'water' => $water,
            'heating' => $heating,
            'underlay' => $underlay,
            'warranty' => $warranty,
            'pack' => [
                'min' => $pack?->lo !== null ? LaminateDecimal::trim((string) $pack->lo) : null,
                'max' => $pack?->hi !== null ? LaminateDecimal::trim((string) $pack->hi) : null,
            ],
        ];
    }

    /** @return list<string> */
    private function list(Request $request, string $param): array
    {
        $raw = $this->scalar($request, $param);

        return $raw === null ? [] : array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
    }

    private function scalar(Request $request, string $param): ?string
    {
        $value = $request->query->all()[$param] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }
}
