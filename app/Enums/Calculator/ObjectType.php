<?php

namespace App\Enums\Calculator;

/**
 * Master spec 7: the object type decides the default room and work sets only - it
 * never applies a price coefficient by itself. City and height coefficients do that.
 */
enum ObjectType: string
{
    case Apartment = 'APARTMENT';
    case House = 'HOUSE';
    case Office = 'OFFICE';
    case Commercial = 'COMMERCIAL';

    public function isResidential(): bool
    {
        return in_array($this, [self::Apartment, self::House], true);
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.object.types.'.strtolower($this->value);
    }

    /** Room types a fresh session starts with, so the visitor is not staring at an empty step. */
    public function defaultRoomTypes(): array
    {
        return $this->isResidential()
            ? [RoomType::Living, RoomType::Bedroom, RoomType::Kitchen, RoomType::Bathroom, RoomType::Corridor]
            : [RoomType::Commercial, RoomType::Office, RoomType::Wc, RoomType::Corridor];
    }
}
