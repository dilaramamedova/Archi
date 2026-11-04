<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\FinishFamily;
use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\SurfaceType;

/**
 * A calculator_finishes row as the engine sees it (a plain value object, never the
 * Eloquent model — see WorkRate for why).
 *
 * The finish carries both halves of its price: the labour rate, which the engine
 * turns into an ordinary work line through labourWorkRate(), and the material price
 * per segment, used until the visitor picks a real catalog product.
 */
class Finish
{
    public function __construct(
        public readonly string $finishId,
        public readonly SurfaceType $surface,
        public readonly FinishFamily $family,
        /** @var array<string, string> locale => name */
        public readonly array $names,
        public readonly string $unit,
        public readonly ?float $labourRate,
        public readonly float $minimumCharge,
        /** @var array<string, float|null> segment => price per unit */
        public readonly array $materialPrices,
        public readonly float $wastePercent,
        public readonly bool $isDefault,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            finishId: $row['finish_id'],
            surface: SurfaceType::from($row['surface']),
            family: FinishFamily::from($row['family']),
            names: $row['names'],
            unit: $row['unit'],
            labourRate: $row['labour_rate'],
            minimumCharge: $row['minimum_charge'],
            materialPrices: $row['material_prices'],
            wastePercent: $row['waste_percent'],
            isDefault: $row['is_default'],
        );
    }

    public function name(?string $locale = null): string
    {
        return Translated::pick($this->names, $locale) ?? $this->finishId;
    }

    public function materialPrice(PriceSegment $segment): ?float
    {
        return $this->materialPrices[$segment->value] ?? null;
    }

    /** 1 + waste/100 — the factor the required quantity is multiplied by. */
    public function wasteFactor(): float
    {
        return 1 + $this->wastePercent / 100;
    }

    /**
     * The finish's own installation, as a work catalog row. The work id IS the finish
     * id, so the works step, the selection map and the estimate line all refer to the
     * finish by one name.
     */
    public function labourWorkRate(): WorkRate
    {
        return new WorkRate(
            workId: $this->finishId,
            category: $this->family->workCategory($this->surface),
            names: $this->names,
            unit: $this->unit,
            quantitySource: match ($this->surface) {
                SurfaceType::Floor => 'SelectedFloorArea',
                SurfaceType::Wall => 'NetWallAreaSelected',
                SurfaceType::Ceiling => 'SelectedCeilingArea',
            },
            rate: $this->labourRate,
            minimumCharge: $this->minimumCharge,
            precisionClass: PrecisionClass::Calculated,
            contingencyEligible: true,
        );
    }
}
