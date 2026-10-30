<?php

declare(strict_types=1);

namespace App\Enums;

enum TenderAccessRequestStatus: string
{
    case Pending = 'pending';
    case Contacted = 'contacted';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Yeni',
            self::Contacted => 'Əlaqə saxlanılıb',
            self::Approved => 'Giriş aktivləşdirilib',
            self::Rejected => 'Rədd edilib',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Contacted => 'info',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }

    /** Still waiting for the admin — the user must not send another one. */
    public function isOpen(): bool
    {
        return $this === self::Pending || $this === self::Contacted;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
