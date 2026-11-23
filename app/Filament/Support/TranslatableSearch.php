<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Case-insensitive search over a Spatie-translatable JSON column.
 *
 * MySQL compares JSON values in binary, so Filament's default `name like '%…%'`
 * found «Tavan» but not «tavan». This matches the unquoted value of the az
 * translation (and the current locale's) lower-cased (ı folded into i), under the
 * table's case-insensitive collation.
 */
final class TranslatableSearch
{
    /**
     * Adds a nested `(az LIKE … OR <locale> LIKE …)` constraint.
     */
    public static function apply(Builder $query, string $column, string $search, string $boolean = 'and'): Builder
    {
        $term = self::term($search);

        if ($term === null) {
            return $query;
        }

        $wrapped = $query->getQuery()->getGrammar()->wrap($column);

        return $query->where(function (Builder $query) use ($wrapped, $term): void {
            foreach (self::locales() as $locale) {
                $query->orWhereRaw(
                    "REPLACE(LOWER(JSON_UNQUOTE(JSON_EXTRACT({$wrapped}, ?))), 'ı', 'i') COLLATE utf8mb4_unicode_ci LIKE ?",
                    ['$."'.$locale.'"', $term],
                );
            }
        }, boolean: $boolean);
    }

    /** `%term%`, lower-cased with LIKE wildcards escaped; null for a blank search. */
    private static function term(string $search): ?string
    {
        $search = trim($search);

        if ($search === '') {
            return null;
        }

        // «İ» lower-cases to «i» + a combining dot in PHP, which then matches nothing;
        // and Azerbaijani «I» is the capital of «ı», not «i» («ALÇI» → «Alçıpan»), so
        // ı and i are folded together on both sides.
        $search = str_replace('ı', 'i', mb_strtolower(str_replace('İ', 'i', $search)));

        return '%'.addcslashes($search, '\\%_').'%';
    }

    /** @return list<string> */
    private static function locales(): array
    {
        $current = preg_replace('/[^A-Za-z_-]/', '', (string) app()->getLocale()) ?: 'az';

        return array_values(array_unique(['az', $current]));
    }
}
