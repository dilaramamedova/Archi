<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SellerProfile extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'tax_id_verified' => 'boolean',
            'languages' => 'array',
            'notification_settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function showrooms(): HasMany
    {
        return $this->hasMany(Showroom::class)->orderBy('sort_order');
    }
}
