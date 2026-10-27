<?php

declare(strict_types=1);

namespace App\Services\Laminate;

/**
 * Exact decimal handling for the laminate card (TZ §6 «Дробные числа»).
 *
 * Every measurement travels as a canonical decimal STRING — "1.835", never 1.835 —
 * because a float cannot hold 1.835 exactly and the TZ forbids float storage for
 * sizes, area and price. bcmath does the arithmetic.
 */
final class LaminateDecimal
{
    public const NUMBER_FORMAT = 'NUMBER_FORMAT';

    public const PRECISION_EXCEEDED = 'PRECISION_EXCEEDED';

    public const OUT_OF_RANGE = 'OUT_OF_RANGE';

    public const POSITIVE_REQUIRED = 'POSITIVE_REQUIRED';

    /**
     * Parse what a seller typed into a canonical positive decimal.
     *
     * Accepts "1,835" and "1.835" alike (acceptance 4). Rejects — instead of
     * guessing — a sign, an exponent, units ("8 мм"), NaN, Infinity, a thousands
     * separator, more fractional digits than the column holds (it never rounds
     * silently) and anything not greater than zero.
     *
     * @param  int  $scale  digits allowed after the point
     * @param  int  $integerDigits  digits allowed before it (precision − scale)
     * @return array{0: ?string, 1: ?string} [canonical value or null, error code or null]
     */
    public static function parse(mixed $raw, int $scale, int $integerDigits): array
    {
        if ($raw === null) {
            return [null, null];
        }

        if (! is_string($raw) && ! is_int($raw)) {
            // A float has already lost the exact value; an array is not a number.
            return [null, self::NUMBER_FORMAT];
        }

        $s = trim((string) $raw);

        if ($s === '') {
            return [null, null];
        }

        // One decimal comma becomes a point. Both a comma and a point, or two of
        // either, is a thousands separator or a typo — ambiguous, so refused.
        if (str_contains($s, ',')) {
            if (str_contains($s, '.') || substr_count($s, ',') > 1) {
                return [null, self::NUMBER_FORMAT];
            }

            $s = str_replace(',', '.', $s);
        }

        if (! preg_match('/^(\d+)(?:\.(\d+))?$/D', $s, $m)) {
            return [null, self::NUMBER_FORMAT];
        }

        $int = ltrim($m[1], '0');
        $frac = $m[2] ?? '';

        if (strlen($frac) > $scale) {
            return [null, self::PRECISION_EXCEEDED];
        }

        if (strlen($int) > $integerDigits) {
            return [null, self::OUT_OF_RANGE];
        }

        $frac = rtrim($frac, '0');
        $canonical = ($int === '' ? '0' : $int).($frac !== '' ? '.'.$frac : '');

        if ($canonical === '0') {
            return [null, self::POSITIVE_REQUIRED];
        }

        return [$canonical, null];
    }

    /** "8.0000" → "8", "1.8350" → "1.835", null stays null. */
    public static function trim(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! str_contains($value, '.')) {
            return $value;
        }

        $value = rtrim(rtrim($value, '0'), '.');

        return $value === '' ? '0' : $value;
    }

    /** Is $a strictly greater than $b? Both canonical decimal strings. */
    public static function greaterThan(string $a, string $b): bool
    {
        return bccomp($a, $b, 6) === 1;
    }

    /**
     * Round half-up to $scale for display. bcmath truncates, so the half is added
     * explicitly — a pack at 31.0125 ₼ must print 31.01, and 31.015 must print 31.02.
     */
    public static function round(string $value, int $scale): string
    {
        $half = '0.'.str_repeat('0', $scale).'5';

        return bcadd($value, $half, $scale);
    }
}
