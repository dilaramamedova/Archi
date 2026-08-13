<?php

namespace App\Models;

use App\Enums\AttributeComplexity;
use App\Enums\AttributeFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class Attribute extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'tooltip'];

    protected $casts = [
        'field_type' => AttributeFieldType::class,
        'complexity' => AttributeComplexity::class,
        'is_active' => 'boolean',
    ];

    public function options(): HasMany
    {
        return $this->hasMany(AttributeOption::class)->orderBy('sort_order');
    }

    public function subCategories(): BelongsToMany
    {
        return $this->belongsToMany(SubCategory::class, 'attribute_sub_category')
            ->using(SubCategoryAttribute::class)
            ->withPivot(['is_required', 'is_filterable', 'filter_priority', 'unit', 'sort_order'])
            ->withTimestamps();
    }

    public function productValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
