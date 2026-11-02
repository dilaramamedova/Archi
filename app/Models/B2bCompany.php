<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\B2bCooperationType;
use App\Enums\B2bPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Translatable\HasTranslations;

/**
 * A B2B company shown on /b2b (Figma «10 · B2B BEYNƏLXALQ / ArchiAds»).
 *
 * Admin-managed only — there is no self-registration. The cooperation types and
 * the data model come straight from the onboarding frames.
 */
final class B2bCompany extends Model
{
    use HasTranslations;

    /** Catalog photos are public (shown in the profile gallery). */
    public const IMAGES_DISK = 'public';

    protected $fillable = [
        'slug', 'b2b_category_id',
        'legal_name', 'brand_name', 'registration_no', 'country', 'legal_address',
        'phone', 'website', 'logo', 'description',
        'cooperation_type', 'brands', 'moq', 'catalog_description', 'catalog_images',
        'contact_person', 'contact_position', 'contact_phone', 'whatsapp', 'telegram',
        'email', 'working_hours', 'instagram', 'linkedin', 'facebook', 'languages',
        'market', 'plan', 'is_featured', 'is_verified', 'is_published',
        'sort_order', 'published_at',
    ];

    public array $translatable = ['description', 'catalog_description'];

    protected $casts = [
        'cooperation_type' => B2bCooperationType::class,
        'plan' => B2bPlan::class,
        'catalog_images' => 'array',
        'languages' => 'array',
        'is_featured' => 'boolean',
        'is_verified' => 'boolean',
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $company): void {
            if (blank($company->slug)) {
                $company->slug = self::uniqueSlug((string) ($company->brand_name ?: $company->legal_name));
            }

            if ($company->is_published && ! $company->published_at) {
                $company->published_at = now();
            }
        });

        // Remove catalog photos dropped in the panel (or with the company) from disk.
        static::updated(function (self $company): void {
            if ($company->wasChanged('catalog_images')) {
                $before = (array) json_decode((string) $company->getOriginal('catalog_images'), true);
                self::deleteImages(array_diff($before, (array) $company->catalog_images));
            }
        });

        static::deleted(fn (self $company) => self::deleteImages((array) $company->catalog_images));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(B2bCategory::class, 'b2b_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /** The name shown everywhere: the brand if set, else the legal name. */
    public function displayName(): string
    {
        return $this->brand_name ?: $this->legal_name;
    }

    /** "AC" for "Anatolia Ceramics" — the logo placeholder. */
    public function initials(): string
    {
        $words = preg_split('/\s+/u', trim($this->displayName())) ?: [];
        $letters = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr($words[1], 0, 1)
            : mb_substr($words[0] ?? '', 0, 2);

        return mb_strtoupper($letters);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return filled($this->logo) ? storage_url($this->logo) : null;
    }

    /**
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return collect($this->catalog_images ?? [])
            ->filter(fn ($path): bool => is_string($path) && $path !== '')
            ->map(fn (string $path): string => storage_url($path))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function languageLabels(): array
    {
        $map = [
            'az' => 'Azərbaycan', 'ru' => 'Rus', 'en' => 'İngilis',
            'tr' => 'Türk', 'other' => 'Digər',
        ];

        return collect($this->languages ?? [])
            ->map(fn ($code): string => $map[$code] ?? (string) $code)
            ->values()
            ->all();
    }

    public function whatsappUrl(): ?string
    {
        if (blank($this->whatsapp)) {
            return null;
        }

        return 'https://wa.me/'.preg_replace('/[^0-9]/', '', $this->whatsapp);
    }

    public function mailtoUrl(): ?string
    {
        return filled($this->email) ? 'mailto:'.$this->email : null;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $i = 2;

        while (self::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @param  array<int, mixed>  $paths
     */
    private static function deleteImages(array $paths): void
    {
        $paths = array_values(array_filter($paths, fn ($path): bool => is_string($path) && $path !== ''));

        if ($paths !== []) {
            Storage::disk(self::IMAGES_DISK)->delete($paths);
        }
    }
}
