<?php

namespace App\Enums;

enum UserRole: string
{
    case Buyer = 'buyer';
    case Seller = 'seller';
    case Master = 'master';

    public function label(): string
    {
        return match ($this) {
            self::Buyer => 'Alıcı',
            self::Seller => 'Satıcı',
            self::Master => 'Usta / Mütəxəssis',
        };
    }
}
