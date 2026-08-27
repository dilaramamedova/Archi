<?php

namespace App\Services\Calculator;

/**
 * Picks the right language out of a translatable JSON column.
 *
 * The calculator's configuration is cached once for all three locales rather than
 * once per locale: the names are a handful of kilobytes and caching them three times
 * would triple both the store and the number of ways it can go stale.
 */
class Translated
{
    public static function pick(array $translations, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();

        return $translations[$locale]
            ?? $translations[config('app.fallback_locale', 'az')]
            ?? ($translations !== [] ? reset($translations) : null);
    }

    /** Normalise whatever the database column holds into a locale => value map. */
    public static function normalise(mixed $value): array
    {
        if (is_array($value)) {
            return array_filter($value, fn ($v) => is_string($v) && $v !== '');
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [app()->getLocale() => $value];
        }

        return [];
    }
}
