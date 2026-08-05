<?php

namespace App\Models;

use App\Services\SearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'description'];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'is_visible' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'is_sale' => 'boolean',
        'free_delivery' => 'boolean',
        'return_14_days' => 'boolean',
        'specifications' => 'array',
        'features' => 'array',
        'frequently_bought_together' => 'array',
        'accessories' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function mainImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_main', true);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeSale($query)
    {
        return $query->where('is_sale', true);
    }

    public function scopeOnSale($query)
    {
        return $query->whereNotNull('old_price')->whereColumn('old_price', '>', 'price');
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Search products using synonym expansion and multi-locale matching.
     * Searches the raw JSON columns so all locale values (az, ru, en) are checked.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $terms = SearchService::expandQuery($term);

        return $query->where(function (Builder $q) use ($terms) {
            foreach ($terms as $t) {
                $q->orWhere('name', 'like', "%{$t}%")
                  ->orWhere('brand', 'like', "%{$t}%")
                  ->orWhere('description', 'like', "%{$t}%");
            }

            // Also match products whose category name matches any term
            $q->orWhereHas('category', function (Builder $cq) use ($terms) {
                $cq->where(function (Builder $inner) use ($terms) {
                    foreach ($terms as $t) {
                        $inner->orWhere('name', 'like', "%{$t}%");
                    }
                });
            });
        });
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('views_count');
    }

    public function getMainImageUrlAttribute(): ?string
    {
        $main = $this->images->firstWhere('is_main', true) ?? $this->images->first();
        if (! $main?->path) {
            return null;
        }
        return storage_url($main->path);
    }

    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->where('status', 'approved')->avg('rating') ?? 0, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return $this->reviews()->where('status', 'approved')->count();
    }
}
