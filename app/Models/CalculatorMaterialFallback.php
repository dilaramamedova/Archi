<?php

namespace App\Models;

use App\Enums\Calculator\PriceSegment;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Average category price used while no catalog product has been chosen for a
 * material requirement (master spec 12). As soon as a product is picked the
 * catalog price replaces it and the line is promoted from CALCULATED to EXACT.
 */
class CalculatorMaterialFallback extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name'];

    protected $casts = [
        'economy_price' => 'decimal:4',
        'standard_price' => 'decimal:4',
        'premium_price' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function priceFor(PriceSegment $segment): ?float
    {
        $value = $this->{$segment->fallbackColumn()};

        return $value === null ? null : (float) $value;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
