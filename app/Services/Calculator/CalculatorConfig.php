<?php

namespace App\Services\Calculator;

use App\Models\CalculatorCity;
use App\Models\CalculatorCoefficient;
use App\Models\CalculatorMaterialFallback;
use App\Models\CalculatorMaterialNorm;
use App\Models\CalculatorQuickRate;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Support\VersionedCache;

/**
 * Single read path to everything the engine multiplies by.
 *
 * Nothing here is hard-coded: the class only reads the seven configuration tables
 * and caches them per version stamp, so an admin edit is visible on the next request
 * without a deployment (TZ §34, master spec §21/§24). The engine never touches an
 * Eloquent model directly — it asks this class, which is also what makes a stored
 * estimate reproducible from its snapshot's config version.
 */
class CalculatorConfig
{
    public const CACHE_DOMAIN = VersionedCache::CALCULATOR;

    /** Bumped when the shape of a calculation changes, not when a price changes. */
    public const CALCULATOR_VERSION = '1.0';

    private ?array $settings = null;

    private ?array $coefficients = null;

    private ?array $norms = null;

    private ?array $workRates = null;

    private ?array $fallbacks = null;

    private ?array $quickRates = null;

    private ?array $cities = null;

    /**
     * The work catalog.
     *
     * Only plain arrays go into the cache — never Eloquent models. A serialized model
     * carries its class identity into the store, and the first deployment that
     * changes that class brings the entry back as an incomplete object and takes
     * every calculation down with it.
     *
     * @return array<string, WorkRate> keyed by work id
     */
    public function workRates(): array
    {
        if ($this->workRates !== null) {
            return $this->workRates;
        }

        $rows = VersionedCache::remember(
            self::CACHE_DOMAIN,
            'work_rates',
            VersionedCache::TTL_STRUCTURAL,
            fn () => CalculatorWorkRate::active()->ordered()->get()
                ->map(fn (CalculatorWorkRate $rate) => [
                    'work_id' => $rate->work_id,
                    'category' => $rate->category->value,
                    'names' => Translated::normalise($rate->getTranslations('name')),
                    'unit' => $rate->unit,
                    'quantity_source' => $rate->quantity_source,
                    'rate' => $rate->rate === null ? null : (float) $rate->rate,
                    'minimum_charge' => (float) $rate->minimum_charge,
                    'precision_class' => $rate->precision_class->value,
                    'contingency_eligible' => (bool) $rate->contingency_eligible,
                ])
                ->all(),
        );

        $catalog = [];

        foreach ($rows as $row) {
            $catalog[$row['work_id']] = WorkRate::fromArray($row);
        }

        return $this->workRates = $catalog;
    }

    public function workRate(string $workId): ?WorkRate
    {
        return $this->workRates()[$workId] ?? null;
    }

    /** @return array<string, MaterialFallback> keyed by material category id */
    public function materialFallbacks(): array
    {
        if ($this->fallbacks !== null) {
            return $this->fallbacks;
        }

        $rows = VersionedCache::remember(
            self::CACHE_DOMAIN,
            'material_fallbacks',
            VersionedCache::TTL_STRUCTURAL,
            fn () => CalculatorMaterialFallback::active()->orderBy('sort_order')->get()
                ->map(fn (CalculatorMaterialFallback $fallback) => [
                    'material_category_id' => $fallback->material_category_id,
                    'surface_group' => $fallback->surface_group,
                    'names' => Translated::normalise($fallback->getTranslations('name')),
                    'unit' => $fallback->unit,
                    'prices' => [
                        'ECONOMY' => $fallback->economy_price === null ? null : (float) $fallback->economy_price,
                        'STANDARD' => $fallback->standard_price === null ? null : (float) $fallback->standard_price,
                        'PREMIUM' => $fallback->premium_price === null ? null : (float) $fallback->premium_price,
                    ],
                ])
                ->all(),
        );

        $catalog = [];

        foreach ($rows as $row) {
            $catalog[$row['material_category_id']] = MaterialFallback::fromArray($row);
        }

        return $this->fallbacks = $catalog;
    }

    public function materialFallback(?string $categoryId): ?MaterialFallback
    {
        return $categoryId === null ? null : ($this->materialFallbacks()[$categoryId] ?? null);
    }

    /** @return array<string, float> coefficient id => multiplier */
    public function coefficients(): array
    {
        return $this->coefficients ??= VersionedCache::remember(
            self::CACHE_DOMAIN,
            'coefficients',
            VersionedCache::TTL_STRUCTURAL,
            fn () => CalculatorCoefficient::active()
                ->pluck('value', 'coefficient_id')
                ->map(fn ($v) => (float) $v)
                ->all(),
        );
    }

    /**
     * A coefficient that is missing or deactivated is 1.00, never 0 — an unknown
     * multiplier must not be able to zero out a line.
     */
    public function coefficient(?string $id): float
    {
        if ($id === null) {
            return 1.0;
        }

        return $this->coefficients()[$id] ?? 1.0;
    }

    /** @return array<string, float> norm rule id => value */
    public function norms(): array
    {
        return $this->norms ??= VersionedCache::remember(
            self::CACHE_DOMAIN,
            'norms',
            VersionedCache::TTL_STRUCTURAL,
            fn () => CalculatorMaterialNorm::active()
                ->pluck('value', 'rule_id')
                ->map(fn ($v) => (float) $v)
                ->all(),
        );
    }

    public function norm(string $ruleId, float $default = 0.0): float
    {
        return $this->norms()[$ruleId] ?? $default;
    }

    /** Waste rules are stored in percent; the engine wants the 1 + p/100 factor. */
    public function wasteFactor(?string $ruleId): float
    {
        if ($ruleId === null) {
            return 1.0;
        }

        return 1 + ($this->norm($ruleId, 0.0) / 100);
    }

    /** @return array<string, mixed> setting key => cast value */
    public function settings(): array
    {
        return $this->settings ??= VersionedCache::remember(
            self::CACHE_DOMAIN,
            'settings',
            VersionedCache::TTL_STRUCTURAL,
            fn () => CalculatorSetting::all()
                ->mapWithKeys(fn (CalculatorSetting $s) => [$s->key => CalculatorSetting::cast($s->value, $s->type)])
                ->all(),
        );
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        $value = $this->settings()[$key] ?? null;

        return $value === null ? $default : $value;
    }

    public function float(string $key, float $default = 0.0): float
    {
        return (float) $this->setting($key, $default);
    }

    public function int(string $key, int $default = 0): int
    {
        return (int) $this->setting($key, $default);
    }

    public function bool(string $key, bool $default = false): bool
    {
        return (bool) $this->setting($key, $default);
    }

    /** @return array<string, array<string, float|null>> [renovationType][segment] => price */
    public function quickRates(): array
    {
        return $this->quickRates ??= VersionedCache::remember(
            self::CACHE_DOMAIN,
            'quick_rates',
            VersionedCache::TTL_STRUCTURAL,
            function () {
                $matrix = [];

                foreach (CalculatorQuickRate::active()->get() as $rate) {
                    $price = $rate->price_per_m2;
                    $matrix[$rate->renovation_type->value][$rate->segment->value] = $price === null ? null : (float) $price;
                }

                return $matrix;
            },
        );
    }

    /** @return array<string, CityRate> keyed by city key */
    public function cities(): array
    {
        if ($this->cities !== null) {
            return $this->cities;
        }

        $rows = VersionedCache::remember(
            self::CACHE_DOMAIN,
            'cities',
            VersionedCache::TTL_STRUCTURAL,
            fn () => CalculatorCity::active()->ordered()->get()
                ->map(fn (CalculatorCity $city) => [
                    'city_key' => $city->city_key,
                    'names' => Translated::normalise($city->getTranslations('name')),
                    'coefficient' => (float) $city->coefficient,
                    'is_default' => (bool) $city->is_default,
                ])
                ->all(),
        );

        $cities = [];

        foreach ($rows as $row) {
            $cities[$row['city_key']] = CityRate::fromArray($row);
        }

        return $this->cities = $cities;
    }

    /** An unconfigured city is 1.00, not a blocked calculation (master spec audit §2.10). */
    public function cityCoefficient(?string $cityKey): float
    {
        $city = $cityKey === null ? null : ($this->cities()[$cityKey] ?? null);

        return $city ? $city->coefficient : 1.0;
    }

    /**
     * Identifies the exact configuration a snapshot was produced with, so a stored
     * estimate can be told apart from one built after a price edit.
     */
    public function configVersion(): string
    {
        return VersionedCache::remember(
            self::CACHE_DOMAIN,
            'config_version',
            VersionedCache::TTL_STRUCTURAL,
            function () {
                $stamps = [
                    CalculatorWorkRate::max('updated_at'),
                    CalculatorMaterialFallback::max('updated_at'),
                    CalculatorCoefficient::max('updated_at'),
                    CalculatorMaterialNorm::max('updated_at'),
                    CalculatorSetting::max('updated_at'),
                    CalculatorQuickRate::max('updated_at'),
                    CalculatorCity::max('updated_at'),
                ];

                return substr(sha1(implode('|', array_map('strval', $stamps))), 0, 16);
            },
        );
    }

    /** Ceiling-height coefficient band (Coefficients sheet, group HEIGHT). */
    public function heightCoefficientId(float $height): ?string
    {
        return match (true) {
            $height > 4.0 => 'K_HEIGHT_OVER_4',
            $height > 3.5 => 'K_HEIGHT_3_51_4_00',
            $height > 3.0 => 'K_HEIGHT_3_01_3_50',
            default => null,
        };
    }
}
