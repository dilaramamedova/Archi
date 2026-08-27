<?php

namespace App\Enums\Calculator;

/** Material price segment — drives both quick rates and material fallback prices. */
enum PriceSegment: string
{
    case Economy = 'ECONOMY';
    case Standard = 'STANDARD';
    case Premium = 'PREMIUM';

    public function labelKey(): string
    {
        return match ($this) {
            self::Economy => 'calculator.level.economy',
            self::Standard => 'calculator.level.standard',
            self::Premium => 'calculator.level.premium',
        };
    }

    /** Column of calculator_material_fallbacks holding this segment's price. */
    public function fallbackColumn(): string
    {
        return match ($this) {
            self::Economy => 'economy_price',
            self::Standard => 'standard_price',
            self::Premium => 'premium_price',
        };
    }

    public function slug(): string
    {
        return strtolower($this->value);
    }

    public static function fromSlug(?string $slug): ?self
    {
        return self::tryFrom(strtoupper((string) $slug));
    }
}
