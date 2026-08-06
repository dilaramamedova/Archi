<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class BlogPost extends Model
{
    use HasTranslations;

    protected $guarded = ['id'];

    public array $translatable = ['title', 'excerpt', 'body', 'og_title', 'og_description'];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'show_on_home' => 'boolean',
        'show_in_header' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function blogCategory(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class);
    }

    protected static function booted(): void
    {
        static::saving(function (self $post) {
            if ($post->is_published && !$post->published_at) {
                $post->published_at = now();
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->whereNotNull('published_at');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeShowOnHome($query)
    {
        return $query->where('show_on_home', true);
    }

    public function scopeShowInHeader($query)
    {
        return $query->where('show_in_header', true);
    }

    public function getCoverImageUrlAttribute(): string
    {
        return storage_url($this->attributes['cover_image'] ?? '', '/assets/blog-cover-default.png');
    }

    public function getReadingTimeAttribute(): string
    {
        $words = str_word_count(strip_tags($this->body ?? ''));
        $minutes = max(1, (int) ceil($words / 200));
        return $minutes . ' ' . __('blog.reading_min');
    }
}
