<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SpecialistProfile extends Model
{
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saving(function (SpecialistProfile $profile): void {
            if ($profile->specialist_specialty_id) {
                // craft stays a denormalized snapshot of the Azerbaijani name.
                $profile->craft = SpecialistSpecialty::query()
                    ->find($profile->specialist_specialty_id)
                    ?->getTranslation('name', 'az', false) ?: $profile->craft;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'is_on_vacation' => 'boolean',
            'is_featured' => 'boolean',
            'show_on_homepage' => 'boolean',
            'rating_avg' => 'float',
            'reviews_count' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function specialty(): BelongsTo
    {
        return $this->belongsTo(SpecialistSpecialty::class, 'specialist_specialty_id');
    }

    public function services(): HasMany
    {
        return $this->hasMany(SpecialistService::class)->orderBy('sort_order');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(SpecialistSchedule::class)->orderBy('day_of_week');
    }

    public function portfolioItems(): HasMany
    {
        return $this->hasMany(SpecialistPortfolioItem::class)->orderBy('sort_order');
    }

    public function approvedPortfolioItems(): HasMany
    {
        return $this->portfolioItems()->approved();
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Display label for the trade. The specialty relation is the source of
     * truth; translate_craft() only rescues legacy rows that never got one.
     */
    public function getCraftLabelAttribute(): ?string
    {
        return $this->specialty?->name ?: translate_craft($this->craft);
    }

    /**
     * Denormalized — see App\Observers\ReviewObserver. Previously an AVG() and
     * a COUNT() query per specialist, which the specialists grid paid once per
     * card. The count keeps an accessor so the camelCase `$s->reviewsCount`
     * form the views use still resolves.
     */
    public function getAverageRatingAttribute(): float
    {
        return round((float) $this->rating_avg, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return (int) ($this->attributes['reviews_count'] ?? 0);
    }
}
