<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class SubCategory extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'description', 'short_description'];

    protected $casts = [
        'is_active' => 'boolean',
        // Category product card (docs/product-cards.md).
        'card_enabled' => 'boolean',
        'card_settings' => 'array',
    ];

    protected static function booted(): void
    {
        // A product's group (category_id) is its class's group: moving the class to
        // another group in the admin moves its products with it.
        static::updated(function (SubCategory $class): void {
            if ($class->wasChanged('category_id') && $class->category_id) {
                Product::query()->where('sub_category_id', $class->id)->update(['category_id' => $class->category_id]);
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * The class form definition: attributes this product class carries,
     * with per-class settings (required/filterable/priority/unit) on the pivot.
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'attribute_sub_category')
            ->using(SubCategoryAttribute::class)
            ->withPivot(['is_required', 'is_filterable', 'filter_priority', 'unit', 'sort_order', 'hint', 'presets', 'visible_when', 'rules', 'maps_to'])
            ->withTimestamps()
            ->orderBy('attribute_sub_category.sort_order');
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'sub_category_applications')->withTimestamps();
    }

    public function synonyms(): HasMany
    {
        return $this->hasMany(SearchSynonym::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
