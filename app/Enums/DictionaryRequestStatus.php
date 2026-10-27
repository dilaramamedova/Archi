<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\TranslatesLabels;

/**
 * Lifecycle of a seller's proposal to add a value to a shared dictionary — a brand
 * that is not in the list yet, or a "Digər" answer for a laminate class (TZ §1, §3).
 *
 * A pending request is never an approved value: it does not appear in the public
 * dictionary, in filters or on the card until a moderator resolves it.
 */
enum DictionaryRequestStatus: string
{
    use TranslatesLabels;

    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return self::translatedLabel('laminate.request_status.'.$this->value, match ($this) {
            self::Pending => 'Gözləmədə',
            self::Approved => 'Təsdiqlənib',
            self::Rejected => 'Rədd edilib',
        });
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected => 'danger',
        };
    }
}
