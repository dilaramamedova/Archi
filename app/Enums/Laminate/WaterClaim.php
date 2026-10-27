<?php

declare(strict_types=1);

namespace App\Enums\Laminate;

use App\Enums\Concerns\TranslatesLabels;

/**
 * What kind of water protection the manufacturer claims (TZ §4).
 *
 * The two are different promises and the TZ is explicit that one is never derived
 * from the other: a moisture-resistant board that survives 72 hours of standing
 * water is still not "water resistant" unless the manufacturer says so.
 */
enum WaterClaim: string
{
    use TranslatesLabels;

    case MoistureResistant = 'moisture_resistant';
    case WaterResistant = 'water_resistant';
    case Unknown = 'unknown';

    public function label(): string
    {
        return self::translatedLabel('laminate.water_claim.'.$this->value, match ($this) {
            self::MoistureResistant => 'Nəmə davamlı',
            self::WaterResistant => 'Suya davamlı',
            self::Unknown => 'Göstərilməyib',
        });
    }
}
