<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\WorkCategory;

/**
 * A work catalog row as the engine sees it.
 *
 * Deliberately not the Eloquent model. The configuration is cached across requests,
 * and a serialized Eloquent model carries its connection, its casts and its class
 * identity into the cache store — the moment any of those change, the cached entry
 * comes back as an incomplete object and every calculation fails. A plain readonly
 * value object survives a deployment, and it also states exactly which fields the
 * engine is allowed to depend on.
 */
class WorkRate
{
    public function __construct(
        public readonly string $workId,
        public readonly WorkCategory $category,
        /** @var array<string, string> locale => name */
        public readonly array $names,
        public readonly string $unit,
        public readonly string $quantitySource,
        public readonly ?float $rate,
        public readonly float $minimumCharge,
        public readonly PrecisionClass $precisionClass,
        public readonly bool $contingencyEligible,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            workId: $row['work_id'],
            category: WorkCategory::from($row['category']),
            names: $row['names'],
            unit: $row['unit'],
            quantitySource: $row['quantity_source'],
            rate: $row['rate'],
            minimumCharge: $row['minimum_charge'],
            precisionClass: PrecisionClass::from($row['precision_class']),
            contingencyEligible: $row['contingency_eligible'],
        );
    }

    public function name(?string $locale = null): string
    {
        return Translated::pick($this->names, $locale) ?? $this->workId;
    }
}
