<?php

namespace App\Models;

use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\WorkCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A single row of the work catalog: what the engine multiplies a resolved quantity
 * by. A null rate is deliberate and means "qiymet dequiqlesdirilir" - the engine
 * emits a PRICE_MISSING line rather than silently pricing the work at zero
 * (master spec 19).
 */
class CalculatorWorkRate extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name'];

    protected $casts = [
        'category' => WorkCategory::class,
        'precision_class' => PrecisionClass::class,
        'rate' => 'decimal:4',
        'minimum_charge' => 'decimal:2',
        'contingency_eligible' => 'boolean',
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('category')->orderBy('sort_order')->orderBy('work_id');
    }
}
