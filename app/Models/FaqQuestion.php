<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class FaqQuestion extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['question', 'answer'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function topic(): BelongsTo
    {
        return $this->belongsTo(FaqTopic::class, 'faq_topic_id');
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
