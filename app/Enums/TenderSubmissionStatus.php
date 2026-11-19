<?php

declare(strict_types=1);

namespace App\Enums;

/** Where a user's «Tender yarat» submission stands in the admin panel. */
enum TenderSubmissionStatus: string
{
    case New = 'new';
    case InReview = 'in_review';
    case Converted = 'converted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Yeni',
            self::InReview => 'Baxılır',
            self::Converted => 'Tender yaradılıb',
            self::Rejected => 'Rədd edilib',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'warning',
            self::InReview => 'info',
            self::Converted => 'success',
            self::Rejected => 'danger',
        };
    }

    /** Still waiting for the admin. */
    public function isOpen(): bool
    {
        return $this === self::New || $this === self::InReview;
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
