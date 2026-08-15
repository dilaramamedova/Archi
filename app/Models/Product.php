<?php

namespace App\Models;

use App\Observers\ProductObserver;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Translatable\HasTranslations;

#[ObservedBy(ProductObserver::class)]
class Product extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'description', 'features_text'];

    protected $casts = [
        'price' => 'decimal:2',
        'old_price' => 'decimal:2',
        'rejected_at' => 'datetime',
        'is_visible' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'is_sale' => 'boolean',
        'show_on_homepage' => 'boolean',
        'free_delivery' => 'boolean',
        'return_14_days' => 'boolean',
        'rating_avg' => 'float',
        'reviews_count' => 'integer',
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

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
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

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class);
    }

    public function badges(): BelongsToMany
    {
        return $this->belongsToMany(ProductBadge::class, 'badge_product');
    }

    public function accessoryProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_accessory', 'product_id', 'accessory_id');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Structured EAV values (catalog classifier). The legacy flat `specifications`
     * json column stays untouched — new products fill these instead.
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'product_applications')->withTimestamps();
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
     * A product may only be bought when it is published in the catalog.
     */
    public function isOrderable(): bool
    {
        return (bool) $this->is_approved && (bool) $this->is_visible;
    }

    /**
     * Books a sold quantity against the stock. The caller is expected to hold a
     * lockForUpdate() row inside a transaction, otherwise concurrent orders may oversell.
     */
    public function applySale(int $quantity): void
    {
        $this->stock = max(0, (int) $this->stock - $quantity);
        $this->sold_count = (int) $this->sold_count + $quantity;
        $this->save();
    }

    /**
     * Search products using synonym expansion and multi-locale matching.
     * Searches the raw JSON columns so all locale values (az, ru, en) are checked.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        return SearchService::buildProductQuery($query, $term);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('views_count');
    }

    public function scopePendingReview($query)
    {
        return $query->where('is_approved', false)->whereNull('rejected_at');
    }

    public function scopeRejected($query)
    {
        return $query->where('is_approved', false)->whereNotNull('rejected_at');
    }

    /**
     * Moderation state derived from is_approved + rejected_at:
     * 'approved' — published (visible in catalog when is_visible),
     * 'rejected' — declined by admin (rejection_reason shown to the seller),
     * 'pending'  — awaiting admin review.
     */
    public function getModerationStatusAttribute(): string
    {
        if ($this->is_approved) {
            return 'approved';
        }

        return $this->rejected_at ? 'rejected' : 'pending';
    }

    public function getMainImageUrlAttribute(): ?string
    {
        $main = $this->images->firstWhere('is_main', true) ?? $this->images->first();
        if (! $main?->path) {
            return null;
        }

        return storage_url($main->path);
    }

    /**
     * Both read denormalized columns kept current by App\Observers\ReviewObserver.
     * They used to run an AVG() and a COUNT() query each, per product — one
     * round trip per card in every catalog grid.
     *
     * getReviewsCountAttribute looks redundant next to the column of the same
     * name, but it is not: the views address these as `$product->reviewsCount`,
     * and Eloquent resolves that camelCase form through the accessor only.
     * Without it the templates silently read null and every review block
     * disappears.
     */
    public function getAverageRatingAttribute(): float
    {
        return round((float) $this->rating_avg, 1);
    }

    public function getReviewsCountAttribute(): int
    {
        return (int) ($this->attributes['reviews_count'] ?? 0);
    }
}
