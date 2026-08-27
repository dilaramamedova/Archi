<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * Consumption norm or waste percentage. Waste lives inside the required quantity of
 * a material and must never be added a second time on top of the estimate - the
 * financial reserve is a separate line (master spec 15).
 */
class CalculatorMaterialNorm extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name'];

    protected $casts = [
        'value' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
