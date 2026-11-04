<?php

namespace App\Models;

use App\Enums\Calculator\FinishFamily;
use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\SurfaceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * A finish the visitor can put on a floor, a wall or a ceiling: its name, the
 * technology family behind it, the labour rate per m² and the material price per
 * m² for the three segments. One row is the whole definition — adding "Mikrosement"
 * is adding a row, not a release.
 */
class CalculatorFinish extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name'];

    protected $casts = [
        'surface' => SurfaceType::class,
        'family' => FinishFamily::class,
        'labour_rate' => 'decimal:4',
        'minimum_charge' => 'decimal:2',
        'material_economy' => 'decimal:4',
        'material_standard' => 'decimal:4',
        'material_premium' => 'decimal:4',
        'waste_percent' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function materialPriceFor(PriceSegment $segment): ?float
    {
        $value = $this->{'material_'.strtolower($segment->value)};

        return $value === null ? null : (float) $value;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('surface')->orderBy('sort_order')->orderBy('finish_id');
    }
}
