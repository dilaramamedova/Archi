<?php

namespace App\Enums\Calculator;

/**
 * Quick calculator renovation types. The technical keys come from the client's TZ
 * (§11 "Qiymətlər üçün sistem identifikatorları") and are also the keys stored in
 * calculator_quick_rates — never rename them, admin data references them.
 */
enum RenovationType: string
{
    case WhiteBox = 'WHITE_BOX';
    case FullRenovation = 'FULL_RENOVATION';

    public function labelKey(): string
    {
        return match ($this) {
            self::WhiteBox => 'calculator.type.white_box',
            self::FullRenovation => 'calculator.type.full_renovation',
        };
    }

    /** The slug used in URLs when parameters are handed to the detailed calculator (TZ §47). */
    public function slug(): string
    {
        return match ($this) {
            self::WhiteBox => 'white-box',
            self::FullRenovation => 'full-renovation',
        };
    }

    public static function fromSlug(?string $slug): ?self
    {
        return match ($slug) {
            'white-box', 'WHITE_BOX', 'whiteBox' => self::WhiteBox,
            'full-renovation', 'FULL_RENOVATION', 'fullRenovation' => self::FullRenovation,
            default => null,
        };
    }
}
