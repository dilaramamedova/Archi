<?php

use App\Models\Product;
use App\Models\SpecialistProfile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Review aggregates as columns instead of per-row subqueries.
 *
 * Product::getAverageRatingAttribute() and getReviewsCountAttribute() each ran
 * their own query, so a 24-product catalog page cost 48 extra round trips and a
 * product's rating could not be sorted or filtered on at all. App\Observers\
 * ReviewObserver keeps both columns in step with the reviews table; they are
 * only ever written from there, never from a form.
 *
 * rating_avg is decimal(3,2) — enough for 0.00-5.00 — and both columns are
 * indexed together so "highest rated first" becomes an index scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['products', 'specialist_profiles'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                if (! Schema::hasColumn($table, 'reviews_count')) {
                    $blueprint->unsignedInteger('reviews_count')->default(0);
                }
                if (! Schema::hasColumn($table, 'rating_avg')) {
                    $blueprint->decimal('rating_avg', 3, 2)->default(0);
                }
            });
        }

        Schema::table('products', function (Blueprint $blueprint) {
            $blueprint->index(['is_visible', 'is_approved', 'rating_avg'], 'p_live_rating_index');
        });

        $this->backfill('products', Product::class);
        $this->backfill('specialist_profiles', SpecialistProfile::class);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $blueprint) {
            $blueprint->dropIndex('p_live_rating_index');
            $blueprint->dropColumn(['reviews_count', 'rating_avg']);
        });

        Schema::table('specialist_profiles', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['reviews_count', 'rating_avg']);
        });
    }

    /**
     * One UPDATE ... JOIN over the whole table rather than a row-by-row loop —
     * the counters have to be right for every existing row before the observer
     * takes over, and at catalog scale a loop would take minutes.
     */
    private function backfill(string $table, string $morphClass): void
    {
        $aggregates = DB::table('reviews')
            ->select('reviewable_id', DB::raw('COUNT(*) as c'), DB::raw('AVG(rating) as a'))
            ->where('reviewable_type', $morphClass)
            ->where('status', 'approved')
            ->groupBy('reviewable_id');

        DB::table($table)
            ->joinSub($aggregates, 'agg', 'agg.reviewable_id', '=', "{$table}.id")
            ->update([
                "{$table}.reviews_count" => DB::raw('agg.c'),
                "{$table}.rating_avg" => DB::raw('ROUND(agg.a, 2)'),
            ]);
    }
};
