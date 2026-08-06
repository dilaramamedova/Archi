<?php

namespace App\Models;

use App\Enums\PortfolioStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialistPortfolioItem extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
            'status' => PortfolioStatus::class,
            'approved_at' => 'immutable_datetime',
        ];
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', PortfolioStatus::Approved);
    }

    public function specialistProfile(): BelongsTo
    {
        return $this->belongsTo(SpecialistProfile::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
