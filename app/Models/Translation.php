<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Translatable\HasTranslations;

class Translation extends Model
{
    use HasTranslations;

    protected $fillable = ['group', 'key', 'value'];

    public array $translatable = ['value'];

    public static function trans(string $groupAndKey): string
    {
        [$group, $key] = explode('.', $groupAndKey, 2);

        $cacheKey = "translations_{$group}_" . app()->getLocale();

        $translations = Cache::remember($cacheKey, 3600, function () use ($group) {
            return static::where('group', $group)
                ->pluck('value', 'key')
                ->toArray();
        });

        return $translations[$key] ?? $groupAndKey;
    }

    public static function clearCache(): void
    {
        foreach (['az', 'ru', 'en'] as $locale) {
            $groups = static::distinct()->pluck('group');
            foreach ($groups as $group) {
                Cache::forget("translations_{$group}_{$locale}");
            }
        }
    }
}
