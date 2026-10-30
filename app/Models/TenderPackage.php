<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A paid access package ("1 ay", "3 ay", …). Chosen in the access-request form;
 * the admin is paid offline and then activates a TenderSubscription.
 */
final class TenderPackage extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'description', 'duration_months', 'price', 'currency', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected $attributes = ['currency' => 'AZN'];

    protected $casts = [
        'duration_months' => 'integer',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('duration_months');
    }

    public function priceLabel(): ?string
    {
        if ($this->price === null) {
            return null;
        }

        $value = (float) $this->price;

        return number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 2, '.', ' ').' '.($this->currency === 'AZN' ? '₼' : $this->currency);
    }
}
