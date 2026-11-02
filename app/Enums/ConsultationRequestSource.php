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

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Ana səhifə',
            self::B2b => 'B2B',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s): array => [$s->value => $s->label()])->all();
    }
}
