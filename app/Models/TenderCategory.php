<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/** "Memarlıq və dizayn", "Tikinti və təmir", … — the Kateqoriya filter on /tenders. */
final class TenderCategory extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (blank($category->slug)) {
                $category->slug = Str::slug((string) $category->getTranslation('name', 'az', false)) ?: Str::random(8);
            }
        });
    }

    public function tenders(): HasMany
    {
        return $this->hasMany(Tender::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
