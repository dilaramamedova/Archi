<?php

declare(strict_types=1);

namespace App\Enums\Laminate;

use App\Enums\Concerns\TranslatesLabels;

/**
 * How a warranty term is expressed (TZ §5).
 *
 * `lifetime` is its own kind, never a number: storing it as 0 or 999 years would
 * make it sort, filter and print as something the manufacturer did not promise.
 */
enum WarrantyTerm: string
{
    use TranslatesLabels;

    case Years = 'years';
    case Lifetime = 'lifetime';
    case Unknown = 'unknown';

    public function label(): string
    {
        return self::translatedLabel('laminate.warranty_term.'.$this->value, match ($this) {
            self::Years => 'İl',
            self::Lifetime => 'Ömürlük (istehsalçının şərtləri ilə)',
            self::Unknown => 'Müddət göstərilməyib',
        });
    }
}
