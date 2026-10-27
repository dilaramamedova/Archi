<?php

declare(strict_types=1);

namespace App\Enums\Laminate;

use App\Enums\Concerns\TranslatesLabels;

/**
 * Where a warranty applies (TZ §5). One record per scope, at most one of each.
 *
 * A missing commercial record means "not stated for commercial use", which the
 * card must say in those words — it is not a refusal of warranty.
 */
enum WarrantyScope: string
{
    use TranslatesLabels;

    case Residential = 'residential';
    case Commercial = 'commercial';

    public function label(): string
    {
        return self::translatedLabel('laminate.warranty_scope.'.$this->value, match ($this) {
            self::Residential => 'Məişət istifadəsi',
            self::Commercial => 'Kommersiya istifadəsi',
        });
    }
}
