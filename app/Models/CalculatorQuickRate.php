<?php

namespace App\Models;

use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\RenovationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One of the six approved quick-calculator prices (TZ 10 / 54). The pair
 * (renovation_type, segment) is unique, so the table is the admin-facing 2x3 matrix.
 */
class CalculatorQuickRate extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'renovation_type' => RenovationType::class,
        'segment' => PriceSegment::class,
        'price_per_m2' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
