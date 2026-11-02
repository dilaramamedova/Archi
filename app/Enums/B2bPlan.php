<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The marketing plan a company is placed on (Figma pricing frames). It drives
 * the card badge and ordering only — there is no self-service billing; the
 * admin sets it when adding the company.
 */
enum B2bPlan: string
{
    case Free = 'free';
    case Basic = 'basic';
    case Pro = 'pro';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Free => 'Free',
            self::Basic => 'Basic',
            self::Pro => 'Pro',
            self::Premium => 'Premium',
        };
    }

    /** Free gets no badge; the paid tiers surface on the company card. */
    public function badge(): bool
    {
        return $this !== self::Free;
    }

    /** Higher tier = higher in the directory. */
    public function weight(): int
    {
        return match ($this) {
            self::Premium => 3,
            self::Pro => 2,
            self::Basic => 1,
            self::Free => 0,
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $plan): array => [$plan->value => $plan->label()])
            ->all();
    }
}
