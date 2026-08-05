<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SpecialistProfile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'is_on_vacation' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->where('status', 'approved')->avg('rating') ?? 0, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->where('status', 'approved')->count();
    }
}
