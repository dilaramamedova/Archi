<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\TenderAccessRequest;
use App\Models\TenderSubscription;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Who may see the tender section (Figma T1, T3, T4, T5):
 *
 *   guest    — not signed in                      → lock card, "Daxil ol"
 *   none     — signed in, never subscribed        → lock card, request a package
 *   pending  — signed in, a request is waiting    → lock card, "request sent"
 *   expired  — the last subscription has ended    → red banner + "Paketi yenilə"
 *   active   — a subscription covers now, or admin → the board itself
 *
 * "pending" wins over "expired": once a renewal request is in, the user sees
 * that it is being handled instead of being asked to send another one.
 */
final class TenderAccess
{
    public const GUEST = 'guest';

    public const NONE = 'none';

    public const PENDING = 'pending';

    public const EXPIRED = 'expired';

    public const ACTIVE = 'active';

    private function __construct(
        public readonly string $state,
        public readonly ?CarbonInterface $endsAt = null,
        public readonly ?CarbonInterface $expiredAt = null,
    ) {}

    public static function for(?User $user): self
    {
        if ($user === null) {
            return new self(self::GUEST);
        }

        return self::resolve($user);
    }

    public function granted(): bool
    {
        return $this->state === self::ACTIVE;
    }

    public function canRequest(): bool
    {
        return in_array($this->state, [self::NONE, self::EXPIRED], true);
    }

    private static function resolve(User $user): self
    {
        if ($user->isAdmin()) {
            return new self(self::ACTIVE);
        }

        $active = TenderSubscription::query()
            ->where('user_id', $user->id)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>', now())
            ->orderByDesc('ends_at')
            ->first();

        if ($active) {
            return new self(self::ACTIVE, endsAt: $active->ends_at);
        }

        $hasOpenRequest = TenderAccessRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'contacted'])
            ->exists();

        if ($hasOpenRequest) {
            return new self(self::PENDING);
        }

        $lastEnded = TenderSubscription::query()
            ->where('user_id', $user->id)
            ->where('ends_at', '<=', now())
            ->max('ends_at');

        return $lastEnded
            ? new self(self::EXPIRED, expiredAt: Carbon::parse($lastEnded))
            : new self(self::NONE);
    }
}
