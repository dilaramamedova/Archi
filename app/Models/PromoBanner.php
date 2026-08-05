<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PromoBanner extends Model
{
    use HasTranslations;

    protected $fillable = [
        'badge_text', 'title', 'description', 'code',
        'button_text', 'button_url', 'background_color',
        'sort_order', 'is_active',
    ];

    public array $translatable = ['badge_text', 'title', 'description', 'button_text'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
