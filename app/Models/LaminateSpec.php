<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Laminate\HeatingType;
use App\Enums\Laminate\Tri;
use App\Enums\Laminate\WaterClaim;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The laminate card of one product (TZ «Ламинат» §3–§5).
 *
 * Decimal columns are cast to strings, never floats: a pack of 1.835 m² must come
 * back as exactly "1.8350", and the TZ forbids float storage for sizes, area and
 * price (§6).
 */
class LaminateSpec extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'thickness_mm' => 'decimal:4',
            'length_mm' => 'decimal:4',
            'width_mm' => 'decimal:4',
            'pack_area_m2' => 'decimal:4',
            'underlay_thickness_mm' => 'decimal:4',
            'max_surface_temp_c' => 'decimal:2',
            'thermal_resistance_m2k_w' => 'decimal:6',
            'water_hours' => 'integer',
            'heating_types_known' => 'boolean',
            'water_protection' => Tri::class,
            'floor_heating' => Tri::class,
            'integrated_underlay' => Tri::class,
            'thickness_includes_underlay' => Tri::class,
            'warranty' => Tri::class,
            'water_claim' => WaterClaim::class,
            'version' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function usageClass(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'usage_class_option_id');
    }

    public function abrasion(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'abrasion_option_id');
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(AttributeOption::class, 'connection_option_id');
    }

    public function heatingTypes(): HasMany
    {
        return $this->hasMany(LaminateSpecHeatingType::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(LaminateSpecWarranty::class)->orderBy('scope');
    }

    /** Documents on the live card — detached ones stay in the table for history. */
    public function documents(): HasMany
    {
        return $this->hasMany(LaminateSpecDocument::class)
            ->whereNull('detached_at')
            ->orderBy('display_order')
            ->orderBy('id');
    }

    /** @return list<HeatingType> */
    public function heatingTypeList(): array
    {
        return $this->heatingTypes
            ->map(fn (LaminateSpecHeatingType $row) => HeatingType::tryFrom($row->heating_type_code))
            ->filter()
            ->values()
            ->all();
    }
}
