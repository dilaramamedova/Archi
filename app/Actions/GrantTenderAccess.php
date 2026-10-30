<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\TenderAccessRequestStatus;
use App\Models\TenderAccessRequest;
use App\Models\TenderPackage;
use App\Models\TenderSubscription;
use App\Models\User;
use App\Notifications\TenderAccessGrantedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Opens the tender section for a user for N months — the admin's answer to an
 * access request once the package is paid.
 *
 * A renewal while access is still running starts when the current period ends,
 * so paying early never loses days.
 */
final class GrantTenderAccess
{
    public function __invoke(
        User $user,
        int $months,
        ?TenderPackage $package = null,
        ?TenderAccessRequest $request = null,
        ?string $note = null,
    ): TenderSubscription {
        $subscription = DB::transaction(function () use ($user, $months, $package, $request, $note): TenderSubscription {
            $currentEnd = TenderSubscription::query()
                ->where('user_id', $user->id)
                ->where('ends_at', '>', now())
                ->lockForUpdate()
                ->max('ends_at');

            $startsAt = $currentEnd ? Carbon::parse($currentEnd) : now();

            $subscription = TenderSubscription::query()->create([
                'user_id' => $user->id,
                'tender_package_id' => $package?->id,
                'tender_access_request_id' => $request?->id,
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addMonthsNoOverflow($months),
                'note' => $note,
            ]);

            $request?->update([
                'status' => TenderAccessRequestStatus::Approved,
                'processed_at' => now(),
            ]);

            return $subscription;
        });

        $user->notify(new TenderAccessGrantedNotification($subscription));

        return $subscription;
    }
}
