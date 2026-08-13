<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use Throwable;

trait TranslatesAdminMessages
{
    /**
     * Database-backed admin string with a hardcoded Azerbaijani fallback.
     *
     * t() echoes the key back while it is still missing from the translations
     * table, and resources are also booted from console commands where the table
     * may be unreachable — both cases fall back to the literal az text.
     *
     * @param  array<string, string|int>  $replace
     */
    protected static function adminMessage(string $key, string $fallback, array $replace = []): string
    {
        try {
            $value = t($key, $replace);
        } catch (Throwable) {
            $value = null;
        }

        if (is_string($value) && $value !== '' && $value !== $key) {
            return $value;
        }

        $placeholders = [];

        foreach ($replace as $placeholder => $replacement) {
            $placeholders[':'.$placeholder] = (string) $replacement;
        }

        return $placeholders === [] ? $fallback : strtr($fallback, $placeholders);
    }
}
