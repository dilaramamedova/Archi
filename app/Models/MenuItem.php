<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class MenuItem extends Model
{
    use HasTranslations;

    protected $fillable = [
        'parent_id', 'location', 'label', 'url', 'route_name',
        'icon', 'image', 'description', 'has_dropdown', 'is_clickable',
        'open_in_new_tab', 'css_class', 'badge_text', 'sort_order', 'is_active',
    ];

    public array $translatable = ['label', 'description', 'badge_text'];

    protected $casts = [
        'has_dropdown' => 'boolean',
        'is_clickable' => 'boolean',
        'open_in_new_tab' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->active()->ordered();
    }

    public function scopeLocation($query, string $location)
    {
        return $query->where('location', $location);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getResolvedUrlAttribute(): ?string
    {
        if ($this->route_name) {
            try {
                return route($this->route_name);
            } catch (\Exception) {
                return '#';
            }
        }
        return $this->url;
    }
}
