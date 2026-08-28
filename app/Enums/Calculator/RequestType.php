<?php

namespace App\Enums\Calculator;

/** Why the visitor reached out from the finished estimate. */
enum RequestType: string
{
    case Turnkey = 'TURNKEY';
    case SavedEstimate = 'SAVED_ESTIMATE';
    case Pdf = 'PDF';
    case Whatsapp = 'WHATSAPP';

    public function label(): string
    {
        return match ($this) {
            self::Turnkey => 'Açar təslim sifariş',
            self::SavedEstimate => 'Smeta yadda saxlandı',
            self::Pdf => 'PDF yükləndi',
            self::Whatsapp => 'WhatsApp-a göndərildi',
        };
    }

    /** Only a turnkey order is a lead a manager must call back. */
    public function needsCallback(): bool
    {
        return $this === self::Turnkey;
    }
}
