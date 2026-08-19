<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\TranslatesLabels;

/**
 * The four groups the header's "Mütəxəssislər" mega panel offers.
 *
 * The case VALUES are the `?type=` values those menu links already carried —
 * /specialists?type=architect and friends. The links were seeded long before
 * anything read the parameter, so every one of them silently landed on the full
 * directory; this enum is the contract that finally gives them meaning, which is
 * why the values are fixed by the existing menu rather than chosen freshly.
 *
 * A specialty belongs to exactly one category (specialist_specialties.category),
 * so the grouping is data an admin can edit, not a hardcoded map of ids that
 * would rot the first time someone adds a trade.
 */
enum SpecialistCategory: string
{
    use TranslatesLabels;

    case Architect = 'architect';
    case Designer = 'designer';
    case Master = 'master';
    case Company = 'company';

    public function label(): string
    {
        return self::translatedLabel('specialists.category.'.$this->value, $this->fallbackLabel());
    }

    private function fallbackLabel(): string
    {
        return match ($this) {
            self::Architect => 'Arxitektorlar',
            self::Designer => 'İnteryer dizaynerlər',
            self::Master => 'Ustalar',
            self::Company => 'Tikinti şirkətləri',
        };
    }

    /** @return array<string, string> value => label, for Filament selects */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
