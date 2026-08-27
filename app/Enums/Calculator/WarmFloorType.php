<?php

namespace App\Enums\Calculator;

enum WarmFloorType: string
{
    case None = 'NONE';
    case Electric = 'ELECTRIC';
    case Water = 'WATER';

    public function workId(): ?string
    {
        return match ($this) {
            self::None => null,
            self::Electric => 'FLOOR_HEAT_ELECTRIC',
            self::Water => 'FLOOR_HEAT_WATER',
        };
    }
}
