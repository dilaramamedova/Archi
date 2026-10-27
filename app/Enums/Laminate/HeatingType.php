<?php

declare(strict_types=1);

namespace App\Enums\Laminate;

use App\Enums\Concerns\TranslatesLabels;

/**
 * The underfloor heating systems a laminate can be approved for (TZ §4).
 *
 * Approval for one system says nothing about the others, so these are stored as a
 * set and each is filtered on its own. "Types not stated" is NOT a member of this
 * set — it lives in `heating_types_known = false`, so a ticked box can never mean
 * "unknown".
 */
enum HeatingType: string
{
    use TranslatesLabels;

    case Water = 'water';
    case ElectricCable = 'electric_cable';
    case ElectricFilm = 'electric_film';

    public function label(): string
    {
        return self::translatedLabel('laminate.heating_type.'.$this->value, match ($this) {
            self::Water => 'Su ilə isidilən',
            self::ElectricCable => 'Elektrik kabel',
            self::ElectricFilm => 'Elektrik film (infraqırmızı)',
        });
    }
}
