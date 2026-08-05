<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Banner extends Model
{
    use HasTranslations;

    protected $fillable = [
        'position', 'title', 'subtitle', 'image',
        'button_text', 'button_url', 'sort_order', 'is_active',
    ];

    public array $translatable = ['title', 'subtitle', 'button_text'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopePosition($query, string $position)
    {
        return $query->where('position', $position);
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
