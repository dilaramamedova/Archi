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
    ];

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
            ->withPivot(['is_required', 'is_filterable', 'filter_priority', 'unit', 'sort_order'])
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
