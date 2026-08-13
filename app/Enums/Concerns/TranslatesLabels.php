<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use Throwable;

trait TranslatesLabels
{
    /**
     * Database-backed label with a hardcoded Azerbaijani fallback.
     *
     * Enum labels are rendered from seeders, console commands and migrations too,
     * where the translations table (or the cache backing it) may not be reachable
     * yet — every failure mode falls back to the literal az string instead of
     * blowing up, and a missing key (t() echoes the key back) does the same.
     */
    protected static function translatedLabel(string $key, string $fallback): string
    {
        try {
            $value = t($key);
        } catch (Throwable) {
            return $fallback;
        }

        return is_string($value) && $value !== '' && $value !== $key ? $value : $fallback;
    }
}
