<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class ProductImage extends Model
{
    use HasTranslations;

    protected $fillable = ['product_id', 'path', 'alt_text', 'is_main', 'sort_order'];

    public array $translatable = ['alt_text'];

    protected $attributes = [
        'sort_order' => 0,
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
