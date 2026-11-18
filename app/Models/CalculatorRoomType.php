<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A room type's «Sadə» mode norms (TZ 17.11.2026 §6, §34): points as base + share per
 * m² of floor, the default sanitary fixtures, and whether the room has a water line
 * and is waterproofed. Edited in the admin panel «Otaq növləri».
 */
class CalculatorRoomType extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name'];

    protected $casts = [
        'has_plumbing' => 'boolean',
        'needs_waterproofing' => 'boolean',
        'point_norms' => 'array',
        'fixtures' => 'array',
        'ac_extra_above_m2' => 'decimal:2',
        'tv_min_area_m2' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
