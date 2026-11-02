<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/** "Əsas kateqoriya" for the B2B directory filter (e.g. Tikinti materialları). */
final class B2bCategory extends Model
{
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected $casts = ['is_active' => 'boolean'];

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (blank($category->slug)) {
                $category->slug = self::uniqueSlug((string) $category->getTranslation('name', 'az', false), $category->getKey());
            }
        });
    }

    /** "keramika", then "keramika-2"… — slugs are unique, a clash used to be a 500 in the panel. */
    public static function uniqueSlug(string $name, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($name) ?: Str::lower(Str::random(8));
        $slug = $base;
        $i = 2;

        while (self::query()->where('slug', $slug)->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function companies(): HasMany
    {
        return $this->hasMany(B2bCompany::class);
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
