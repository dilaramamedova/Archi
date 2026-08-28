<?php

namespace App\Enums\Calculator;

enum RequestStatus: string
{
    case New = 'NEW';
    case InProgress = 'IN_PROGRESS';
    case Done = 'DONE';
    case Rejected = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Yeni',
            self::InProgress => 'İşlənir',
            self::Done => 'Tamamlandı',
            self::Rejected => 'İmtina edildi',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::InProgress => 'info',
            self::Done => 'success',
            self::Rejected => 'danger',
        };
    }
}
