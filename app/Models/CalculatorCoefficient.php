<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/** A multiplier applied to a line total. 1.00 means the line is unchanged. */
class CalculatorCoefficient extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['description'];

    protected $casts = [
        'value' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
