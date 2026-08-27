<?php

namespace App\Services\Calculator;

/** A city and its labour coefficient. Baku is the 1.00 base. */
class CityRate
{
    public function __construct(
        public readonly string $key,
        /** @var array<string, string> locale => name */
        public readonly array $names,
        public readonly float $coefficient,
        public readonly bool $isDefault,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            key: $row['city_key'],
            names: $row['names'],
            coefficient: $row['coefficient'],
            isDefault: $row['is_default'],
        );
    }

    public function name(?string $locale = null): string
    {
        return Translated::pick($this->names, $locale) ?? $this->key;
    }
}
