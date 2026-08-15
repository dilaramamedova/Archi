<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Review;
use App\Models\SpecialistProfile;
use Illuminate\Support\Facades\DB;

/**
 * Keeps products.reviews_count / rating_avg (and the same pair on
 * specialist_profiles) in step with the reviews table.
 *
 * Only approved reviews count, so a moderation decision — not just a new
 * review — has to trigger a recount. `updated` therefore recomputes whenever
 * status, rating or the morph target moved, and the OLD target is recomputed
 * too on a re-parent so it does not keep a stale number.
 */
class ReviewObserver
{
    public function created(Review $review): void
    {
        $this->recount($review->reviewable_type, $review->reviewable_id);
    }

    public function updated(Review $review): void
    {
        if (! $review->wasChanged(['status', 'rating', 'reviewable_id', 'reviewable_type'])) {
            return;
        }

        // A review moved to another product leaves the previous one stale.
        if ($review->wasChanged(['reviewable_id', 'reviewable_type'])) {
            $this->recount(
                $review->getOriginal('reviewable_type'),
                $review->getOriginal('reviewable_id'),
            );
        }

        $this->recount($review->reviewable_type, $review->reviewable_id);
    }

    public function deleted(Review $review): void
    {
        $this->recount($review->reviewable_type, $review->reviewable_id);
    }

    public function restored(Review $review): void
    {
        $this->recount($review->reviewable_type, $review->reviewable_id);
    }

    /**
     * Recompute one subject's aggregates in a single statement. Written with the
     * query builder rather than the model so it never fires model events (an
     * observer loop) and never touches updated_at — a review must not make a
     * product look freshly edited.
     */
    private function recount(?string $type, mixed $id): void
    {
        $table = match ($type) {
            Product::class => 'products',
            SpecialistProfile::class => 'specialist_profiles',
            default => null,
        };

        if ($table === null || $id === null) {
            return;
        }

        $stats = DB::table('reviews')
            ->where('reviewable_type', $type)
            ->where('reviewable_id', $id)
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as c, COALESCE(AVG(rating), 0) as a')
            ->first();

        DB::table($table)->where('id', $id)->update([
            'reviews_count' => (int) ($stats->c ?? 0),
            'rating_avg' => round((float) ($stats->a ?? 0), 2),
        ]);
    }
}
