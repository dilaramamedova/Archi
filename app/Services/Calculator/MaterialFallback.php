<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\PriceSegment;

/**
 * A material category and its three segment prices. A null price is a real answer:
 * it means Archi has not set one yet, and the engine reports the line as
 * "qiymət dəqiqləşdirilir" rather than pricing the material at zero.
 */
class MaterialFallback
{
    public function __construct(
        public readonly string $categoryId,
        public readonly string $group,
        /** @var array<string, string> locale => name */
        public readonly array $names,
        public readonly string $unit,
        /** @var array<string, float|null> segment => price */
        public readonly array $prices,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            categoryId: $row['material_category_id'],
            group: $row['surface_group'],
            names: $row['names'],
            unit: $row['unit'],
            prices: $row['prices'],
        );
    }

    public function name(?string $locale = null): string
    {
        return Translated::pick($this->names, $locale) ?? $this->categoryId;
    }

    public function priceFor(PriceSegment $segment): ?float
    {
        return $this->prices[$segment->value] ?? null;
    }
}
