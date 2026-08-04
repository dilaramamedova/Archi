<?php

namespace App\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Rejected = 'rejected';
    case Blocked = 'blocked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Gözləmədə',
            self::Active => 'Aktiv',
            self::Rejected => 'Rədd edilib',
            self::Blocked => 'Bloklanıb',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Rejected => 'danger',
            self::Blocked => 'gray',
        };
    }
}
