<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class AboutTeamMember extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['name', 'role'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): string
    {
        return storage_url($this->attributes['image'] ?? '', '/assets/avatar-placeholder.png');
    }

    public function getInitialsAttribute(): string
    {
        $name = $this->name;
        $parts = preg_split('/\s+/', trim($name));
        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[1], 0, 1));
        }

        return mb_strtoupper(mb_substr($name, 0, 2));
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
