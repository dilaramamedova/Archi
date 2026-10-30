<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * A tender announcement published by the admin (Figma «11 · TENDERLƏR»).
 *
 * Open/closed is derived from deadline_at — no deadline means open until the
 * admin unpublishes it. Replies go to the organizer's contact e-mail; the site
 * only shows the tender to subscribers (App\Support\TenderAccess).
 */
final class Tender extends Model
{
    use HasTranslations;

    /** Attachments live on the private disk — only TenderFileController serves them. */
    public const FILES_DISK = 'local';

    protected $fillable = [
        'number', 'tender_category_id', 'title', 'slug', 'summary', 'description',
        'scope', 'requirements', 'submission_documents',
        'organizer_name', 'organizer_logo', 'city', 'area_sqm',
        'budget_min', 'budget_max', 'currency', 'duration',
        'contact_name', 'contact_email', 'contact_phone',
        'files', 'file_names', 'published_at', 'deadline_at', 'is_published',
    ];

    public array $translatable = [
        'title', 'summary', 'description', 'scope', 'requirements',
        'submission_documents', 'duration', 'contact_name',
    ];

    protected $attributes = ['currency' => 'AZN'];

    protected $casts = [
        'files' => 'array',
        'file_names' => 'array',
        'area_sqm' => 'decimal:2',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'published_at' => 'datetime',
        'deadline_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $tender): void {
            if (blank($tender->slug)) {
                $tender->slug = self::uniqueSlug((string) $tender->getTranslation('title', 'az', false));
            }

            if (blank($tender->number)) {
                $tender->number = self::nextNumber();
            }

            if ($tender->is_published && ! $tender->published_at) {
                $tender->published_at = now();
            }
        });

        // Attachments removed in the panel (or with the tender) must not linger on disk.
        static::updated(function (self $tender): void {
            if ($tender->wasChanged('files')) {
                $before = (array) $tender->getOriginal('files');
                self::deleteFiles(array_diff($before, (array) $tender->files));
            }
        });

        static::deleted(fn (self $tender) => self::deleteFiles((array) $tender->files));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TenderCategory::class, 'tender_category_id');
    }

    public function savedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tender_saves')->withPivot('created_at');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->whereNull('deadline_at')->orWhere('deadline_at', '>', now()));
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->whereNotNull('deadline_at')->where('deadline_at', '<=', now());
    }

    public function isOpen(): bool
    {
        return $this->deadline_at === null || $this->deadline_at->isFuture();
    }

    /** "FD" for "Forma Development", "TE" for "Terra" — the logo placeholder. */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->organizer_name)) ?: [];
        $letters = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
            : mb_substr($words[0] ?? '', 0, 2);

        return mb_strtoupper($letters);
    }

    /**
     * Non-empty lines of a one-item-per-line field (scope, requirements, submission_documents).
     *
     * @return list<string>
     */
    public function lines(string $field): array
    {
        $text = (string) $this->getTranslation($field, app()->getLocale());

        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), 'strlen'));
    }

    /** "180 000–220 000 ₼", "95 000 ₼", or null for "Sorğu əsasında". */
    public function budgetLabel(): ?string
    {
        $format = fn ($value): string => number_format((float) $value, 0, '.', ' ');
        $symbol = $this->currency === 'AZN' ? '₼' : $this->currency;
        $min = $this->budget_min !== null ? $format($this->budget_min) : null;
        $max = $this->budget_max !== null ? $format($this->budget_max) : null;

        return match (true) {
            $min !== null && $max !== null && $min !== $max => "{$min}–{$max} {$symbol}",
            $min !== null => "{$min} {$symbol}",
            $max !== null => "{$max} {$symbol}",
            default => null,
        };
    }

    public function areaLabel(): ?string
    {
        if ($this->area_sqm === null) {
            return null;
        }

        $value = (float) $this->area_sqm;

        return number_format($value, fmod($value, 1.0) === 0.0 ? 0 : 1, '.', ' ').' m²';
    }

    public function getOrganizerLogoUrlAttribute(): ?string
    {
        return filled($this->organizer_logo) ? storage_url($this->organizer_logo) : null;
    }

    /** "mailto:" with the tender number as subject, as the design asks applicants to do. */
    public function mailtoUrl(): ?string
    {
        if (blank($this->contact_email)) {
            return null;
        }

        return 'mailto:'.$this->contact_email.'?subject='.rawurlencode($this->number.' — '.$this->title);
    }

    /**
     * @return list<array{index: int, name: string, extension: string, size: ?int}>
     */
    public function fileList(): array
    {
        $disk = Storage::disk(self::FILES_DISK);
        $names = (array) $this->file_names;

        return collect((array) $this->files)
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->values()
            ->map(fn (string $path, int $index): array => [
                'index' => $index,
                'name' => (string) ($names[$path] ?? basename($path)),
                'extension' => mb_strtolower(pathinfo((string) ($names[$path] ?? $path), PATHINFO_EXTENSION)),
                'size' => $disk->exists($path) ? $disk->size($path) : null,
            ])
            ->all();
    }

    public static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'tender';
        $slug = $base;
        $i = 2;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /** T-2026-001, T-2026-002, … — the sequence restarts every year. */
    public static function nextNumber(): string
    {
        $prefix = 'T-'.now()->year.'-';
        $last = self::query()->where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        do {
            $number = $prefix.str_pad((string) $next++, 3, '0', STR_PAD_LEFT);
        } while (self::query()->where('number', $number)->exists());

        return $number;
    }

    /**
     * @param  array<int, mixed>  $paths
     */
    private static function deleteFiles(array $paths): void
    {
        $paths = array_values(array_filter($paths, fn ($path): bool => is_string($path) && $path !== ''));

        if ($paths !== []) {
            Storage::disk(self::FILES_DISK)->delete($paths);
        }
    }
}
