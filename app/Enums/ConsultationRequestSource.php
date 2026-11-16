<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which public form a consultation request came from.
 */
enum ConsultationRequestSource: string
{
    case Home = 'home';
    case B2b = 'b2b';
    // «Hesablama tələb et» on a product with no automatic price (custom-size stone).
    case Product = 'product';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Ana səhifə',
            self::B2b => 'B2B',
            self::Product => 'Məhsul (hesablama sorğusu)',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s): array => [$s->value => $s->label()])->all();
    }
}
