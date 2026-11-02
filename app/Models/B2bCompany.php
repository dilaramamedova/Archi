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

    /** Admin-facing labels of the "Ünsiyyət dilləri" checkboxes (the profile translates them). */
    public const LANGUAGES = [
        'az' => 'Azərbaycan', 'ru' => 'Rus', 'en' => 'İngilis', 'tr' => 'Türk', 'other' => 'Digər',
    ];

    /**
     * What the Telegram / Instagram / LinkedIn / Facebook inputs accept: a handle
     * (`@anatolia`), a bare host+path (`instagram.com/anatolia`) or an http(s) link.
     * Anything with another scheme (javascript:, data:) is rejected.
     */
    public const HANDLE_OR_URL_REGEX = '/^(?:https?:\/\/[^\s<>"\']+|\+?[0-9][0-9\s()-]{5,}|@?[\pL\pN._-]+(?:\/[^\s<>"\']*)?)$/iu';

    private const SOCIAL_BASES = [
        'telegram' => 'https://t.me/',
        'instagram' => 'https://www.instagram.com/',
        'facebook' => 'https://www.facebook.com/',
        'linkedin' => 'https://www.linkedin.com/company/',
    ];

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
                $company->slug = self::uniqueSlug((string) ($company->brand_name ?: $company->legal_name), $company->getKey());
            }

            if ($company->is_published && ! $company->published_at) {
                $company->published_at = now();
            }
        });

        // Remove the logo / catalog photos replaced or dropped in the panel from disk.
        // getRawOriginal(): getOriginal() returns the *cast* array, which json_decode()
        // cannot read ("Array to string conversion").
        static::updated(function (self $company): void {
            if ($company->wasChanged('catalog_images')) {
                $before = (array) json_decode((string) $company->getRawOriginal('catalog_images'), true);
                self::deleteImages(array_diff($before, (array) $company->catalog_images));
            }

            if ($company->wasChanged('logo')) {
                self::deleteImages([$company->getRawOriginal('logo')]);
            }
        });

        static::deleted(fn (self $company) => self::deleteImages([...(array) $company->catalog_images, $company->logo]));
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
        return collect($this->languages ?? [])
            ->map(function ($code): string {
                $code = (string) $code;
                $key = 'b2b.lang.'.$code;
                $value = t($key);

                return is_string($value) && $value !== $key ? $value : (self::LANGUAGES[$code] ?? $code);
            })
            ->values()
            ->all();
    }

    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/[^0-9]/', '', (string) $this->whatsapp);

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }

    public function mailtoUrl(): ?string
    {
        return filled($this->email) ? 'mailto:'.$this->email : null;
    }

    /** `tel:` link for any phone string, or null when it has no digits. */
    public static function telUrl(?string $phone): ?string
    {
        $number = preg_replace('/[^0-9+]/', '', (string) $phone);

        return preg_match('/[0-9]/', $number) ? 'tel:'.$number : null;
    }

    /** The contact person's direct line — only when it is not just the office phone again. */
    public function directPhone(): ?string
    {
        if (blank($this->contact_phone)) {
            return null;
        }

        $digits = fn (?string $v): string => preg_replace('/[^0-9]/', '', (string) $v);

        return $digits($this->contact_phone) === $digits($this->phone) ? null : $this->contact_phone;
    }

    /** The website as a safe absolute http(s) link ("anatolia.com" → "https://anatolia.com"). */
    public function websiteUrl(): ?string
    {
        return self::safeUrl($this->website);
    }

    /**
     * Profile link for telegram / instagram / facebook / linkedin. The admin may type
     * a full link or just the handle; anything that is neither (e.g. `javascript:`)
     * yields null and the link is not rendered.
     */
    public function socialUrl(string $network): ?string
    {
        $value = trim((string) $this->getAttribute($network));

        if ($value === '' || ! isset(self::SOCIAL_BASES[$network])) {
            return null;
        }

        // Telegram also takes a phone number: t.me/+994501112233.
        if ($network === 'telegram' && preg_match('/^\+[0-9\s()-]{6,}$/', $value)) {
            return self::SOCIAL_BASES['telegram'].'+'.preg_replace('/[^0-9]/', '', $value);
        }

        // LinkedIn has people and pages under different paths: "in/aylin-kaya",
        // "company/anatolia", "school/…", "showcase/…". A bare handle stays a company.
        if ($network === 'linkedin' && preg_match('#^/?(in|company|school|showcase)/([\pL\pN._%-]+)/?$#u', $value, $m)) {
            return 'https://www.linkedin.com/'.$m[1].'/'.rawurlencode(rawurldecode($m[2]));
        }

        // "@anatolia.tiles" / "anatolia" is a handle; "instagram.com" / "t.me" is a host.
        if (preg_match('/^@?([\pL\pN._-]+)$/u', $value, $m)
            && ! preg_match('/^(?:www\.)?(?:t\.me|telegram\.me|instagram\.com|facebook\.com|fb\.com|linkedin\.com)$/i', $m[1])) {
            return self::SOCIAL_BASES[$network].rawurlencode($m[1]);
        }

        return self::safeUrl($value);
    }

    /** Absolute http(s) URL or null; a bare "host/path" gets https:// prepended. */
    public static function safeUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (! preg_match('#^[a-z][a-z0-9+.-]*://#i', $value)) {
            // A scheme without "//" (javascript:, data:, mailto:) is never a web link.
            if (preg_match('/^[a-z][a-z0-9+.-]*:(?!\d)/i', $value)) {
                return null;
            }

            $value = 'https://'.ltrim($value, '/');
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        $host = parse_url($value, PHP_URL_HOST);

        return in_array($scheme, ['http', 'https'], true) && is_string($host) && str_contains($host, '.')
            ? $value
            : null;
    }

    public static function uniqueSlug(string $name, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'company';
        $slug = $base;
        $i = 2;

        while (self::query()->where('slug', $slug)->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))->exists()) {
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
