<?php

declare(strict_types=1);

namespace App\Services\ProductCard;

/**
 * Card messages from the translations table (group `product-card`), with the
 * Azerbaijani literal as the fallback while a key is not seeded yet.
 */
final class CardText
{
    /** @param  array<string, string|int|float>  $replace */
    public static function get(string $key, string $fallback, array $replace = []): string
    {
        $full = 'product-card.'.$key;
        $value = function_exists('t') ? t($full, $replace) : $full;

        if (is_string($value) && $value !== $full) {
            return $value;
        }

        return strtr($fallback, collect($replace)->mapWithKeys(fn ($v, $k) => [':'.$k => (string) $v])->all());
    }
}
