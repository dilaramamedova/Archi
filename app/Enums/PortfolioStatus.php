<?php

declare(strict_types=1);

namespace App\Enums;

enum PortfolioStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Yoxlanılır',
            self::Approved => 'Təsdiqlənib',
            self::Rejected => 'Rədd edilib',
        };
    }
}
