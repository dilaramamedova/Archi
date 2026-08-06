<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

class SearchService
{
    /**
     * Synonym map: searching any key also searches all its values.
     * Covers Azerbaijani, Russian loanwords, and English equivalents.
     */
    private static array $synonyms = [
        // Paint
        'krasqa' => ['boya', 'paint', 'краска', 'rəng'],
        'краска' => ['boya', 'paint', 'krasqa', 'rəng'],
        'paint' => ['boya', 'krasqa', 'краска', 'rəng'],
        'boya' => ['paint', 'krasqa', 'краска', 'rəng'],
        'rəng' => ['boya', 'paint', 'krasqa', 'краска'],

        // Tile
        'plitka' => ['kafel', 'metlax', 'tile', 'плитка'],
        'kafel' => ['plitka', 'metlax', 'tile', 'плитка'],
        'tile' => ['kafel', 'plitka', 'metlax', 'плитка'],
        'плитка' => ['kafel', 'plitka', 'metlax', 'tile'],
        'metlax' => ['kafel', 'plitka', 'tile', 'плитка'],

        // Specialist / master
        'usta' => ['master', 'specialist', 'мастер', 'mütəxəssis'],
        'master' => ['usta', 'specialist', 'мастер', 'mütəxəssis'],
        'мастер' => ['usta', 'master', 'specialist', 'mütəxəssis'],
        'specialist' => ['usta', 'master', 'мастер', 'mütəxəssis'],
        'mütəxəssis' => ['usta', 'master', 'specialist', 'мастер'],

        // Plumbing
        'santexnika' => ['plumbing', 'сантехника', 'su sistemi'],
        'plumbing' => ['santexnika', 'сантехника', 'su sistemi'],
        'сантехника' => ['santexnika', 'plumbing', 'su sistemi'],

        // Electrical
        'elektrik' => ['electrical', 'электрика', 'electric'],
        'electrical' => ['elektrik', 'электрика', 'electric'],
        'электрика' => ['elektrik', 'electrical', 'electric'],

        // Cement / concrete
        'sement' => ['cement', 'цемент', 'beton'],
        'cement' => ['sement', 'цемент', 'beton'],
        'цемент' => ['sement', 'cement', 'beton'],
        'beton' => ['sement', 'cement', 'цемент'],

        // Laminate / flooring
        'laminat' => ['laminate', 'ламинат', 'parket', 'döşəmə'],
        'laminate' => ['laminat', 'ламинат', 'parket', 'döşəmə'],
        'ламинат' => ['laminat', 'laminate', 'parket', 'döşəmə'],
        'parket' => ['laminat', 'laminate', 'ламинат', 'döşəmə'],

        // Insulation
        'izolyasiya' => ['insulation', 'изоляция', 'istilik'],
        'insulation' => ['izolyasiya', 'изоляция', 'istilik'],
        'изоляция' => ['izolyasiya', 'insulation', 'istilik'],

        // Wallpaper
        'oboy' => ['wallpaper', 'обои', 'divar kağızı'],
        'wallpaper' => ['oboy', 'обои', 'divar kağızı'],
        'обои' => ['oboy', 'wallpaper', 'divar kağızı'],

        // Tools
        'alət' => ['tools', 'instrument', 'инструмент'],
        'tools' => ['alət', 'instrument', 'инструмент'],
        'instrument' => ['alət', 'tools', 'инструмент'],
        'инструмент' => ['alət', 'tools', 'instrument'],

        // Roof
        'dam' => ['roof', 'крыша', 'dam örtüyü'],
        'roof' => ['dam', 'крыша', 'dam örtüyü'],
        'крыша' => ['dam', 'roof', 'dam örtüyü'],

        // Door
        'qapı' => ['door', 'дверь', 'dver'],
        'door' => ['qapı', 'дверь', 'dver'],
        'дверь' => ['qapı', 'door', 'dver'],

        // Window
        'pəncərə' => ['window', 'окно', 'steklopaket'],
        'window' => ['pəncərə', 'окно', 'steklopaket'],
        'окно' => ['pəncərə', 'window', 'steklopaket'],

        // Pipe
        'boru' => ['pipe', 'труба', 'truba'],
        'pipe' => ['boru', 'труба', 'truba'],
        'труба' => ['boru', 'pipe', 'truba'],
        'truba' => ['boru', 'pipe', 'труба'],
    ];

    /**
     * Expand a search query into an array of terms including synonyms
     * and diacritics-stripped variants.
     */
    public static function expandQuery(string $query): array
    {
        $original = mb_strtolower(trim($query));
        if ($original === '') {
            return [];
        }

        $normalized = self::normalizeAzeri($original);
        $terms = [$original];
        if ($normalized !== $original) {
            $terms[] = $normalized;
        }

        // Check every synonym key against both original and normalized input
        foreach (self::$synonyms as $key => $values) {
            $lowerKey = mb_strtolower($key);
            if (mb_strpos($original, $lowerKey) !== false || mb_strpos($normalized, self::normalizeAzeri($lowerKey)) !== false) {
                foreach ($values as $v) {
                    $terms[] = mb_strtolower($v);
                }
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Build a product search query that checks translatable JSON columns
     * across all locales and uses synonym expansion.
     */
    public static function buildProductQuery(Builder $baseQuery, array $terms): Builder
    {
        return $baseQuery->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                // Search the raw JSON column so all locale values are checked.
                // Brand is a relation (brand_id) — the string column was dropped.
                $q->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn (Builder $bq) => $bq->where('name', 'like', "%{$term}%"));
            }

            // Also match by category name (translatable JSON) for any term
            $q->orWhereHas('category', function (Builder $cq) use ($terms) {
                $cq->where(function (Builder $inner) use ($terms) {
                    foreach ($terms as $term) {
                        $inner->orWhere('name', 'like', "%{$term}%");
                    }
                });
            });
        });
    }

    /**
     * Build a specialist/master search query that checks craft, skills,
     * and user name against all expanded terms.
     */
    public static function buildSpecialistQuery(Builder $baseQuery, array $terms): Builder
    {
        return $baseQuery->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere('craft', 'like', "%{$term}%")
                    ->orWhere('skills', 'like', "%{$term}%");
            }

            $q->orWhereHas('specialty', function (Builder $specialtyQuery) use ($terms) {
                $specialtyQuery->where(function (Builder $inner) use ($terms) {
                    foreach ($terms as $term) {
                        $inner->orWhere('name', 'like', "%{$term}%");
                    }
                });
            });

            $q->orWhereHas('user', function (Builder $uq) use ($terms) {
                $uq->where(function (Builder $inner) use ($terms) {
                    foreach ($terms as $term) {
                        $inner->orWhere('first_name', 'like', "%{$term}%")
                            ->orWhere('last_name', 'like', "%{$term}%");
                    }
                });
            });
        });
    }

    /**
     * Build a blog post search query across translatable title/excerpt/body.
     */
    public static function buildBlogQuery(Builder $baseQuery, array $terms): Builder
    {
        return $baseQuery->where(function (Builder $q) use ($terms) {
            foreach ($terms as $term) {
                $q->orWhere('title', 'like', "%{$term}%")
                    ->orWhere('excerpt', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%");
            }
        });
    }

    /**
     * Get synonym suggestions that match the query.
     * Returns terms the user might also be interested in.
     */
    public static function getSuggestions(string $query): array
    {
        $original = mb_strtolower(trim($query));
        $normalized = self::normalizeAzeri($original);
        $suggestions = [];

        foreach (self::$synonyms as $key => $values) {
            $lowerKey = mb_strtolower($key);
            if (mb_strpos($original, $lowerKey) !== false || mb_strpos($normalized, self::normalizeAzeri($lowerKey)) !== false) {
                foreach ($values as $v) {
                    $lower = mb_strtolower($v);
                    // Only suggest terms that differ from what the user typed
                    if ($lower !== $original && $lower !== $normalized) {
                        $suggestions[] = $lower;
                    }
                }
            }
        }

        return array_values(array_unique($suggestions));
    }

    /**
     * Find category IDs whose name (any locale) matches any of the terms.
     */
    public static function findMatchingCategoryIds(array $terms): array
    {
        return Category::active()
            ->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('name', 'like', "%{$term}%");
                }
            })
            ->pluck('id')
            ->toArray();
    }

    /**
     * Strip Azerbaijani diacritics to allow fuzzy matching.
     */
    public static function normalizeAzeri(string $text): string
    {
        return str_replace(
            ['ə', 'ö', 'ü', 'ç', 'ş', 'ğ', 'ı', 'Ə', 'Ö', 'Ü', 'Ç', 'Ş', 'Ğ', 'İ'],
            ['e', 'o', 'u', 'c', 's', 'g', 'i', 'E', 'O', 'U', 'C', 'S', 'G', 'I'],
            $text
        );
    }
}
