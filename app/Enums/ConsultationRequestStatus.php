<?php

declare(strict_types=1);

namespace App\Enums;

enum ConsultationRequestStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Yeni',
            self::Contacted => 'Əlaqə saxlanılıb',
            self::Completed => 'Tamamlanıb',
            self::Rejected => 'Ləğv edilib',
        };
    }
}
