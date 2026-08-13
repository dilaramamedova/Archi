<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchSynonym;
use App\Models\SubCategory;
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
        'santexnik' => ['santexnika', 'plumbing', 'сантехника', 'su sistemi'],
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
     * Collation forced on every text comparison. The translatable columns are
     * MySQL `json`, and JSON is compared with a binary collation, which makes
     * `like` case-sensitive. Casting to CHAR and applying the database's own
     * case-insensitive collation restores expected behaviour; general_ci also
     * folds the Azerbaijani dotless "ı" onto "i".
     */
    private const CI_COLLATION = 'utf8mb4_general_ci';

    /**
     * Azerbaijani letters folded to their ASCII counterpart, mirroring
     * normalizeAzeri() so the stored value can be folded in SQL too.
     */
    private const AZERI_FOLD = [
        'ə' => 'e', 'ö' => 'o', 'ü' => 'u', 'ç' => 'c',
        'ş' => 's', 'ğ' => 'g', 'ı' => 'i',
    ];

    /**
     * Minimum length of a word considered for all-words (token) matching.
     */
    private const MIN_TOKEN_LENGTH = 2;

    /**
     * Expand a search query into an array of terms including synonyms
     * and diacritics-stripped variants.
     */
    public static function expandQuery(string $query): array
    {
        $original = self::lower(trim($query));
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
            $lowerKey = self::lower($key);
            if (mb_strpos($original, $lowerKey) !== false || mb_strpos($normalized, self::normalizeAzeri($lowerKey)) !== false) {
                // The key itself is a term too: "santexnika" should also find "Santexnik".
                $terms[] = $lowerKey;
                foreach ($values as $v) {
                    $terms[] = self::lower($v);
                }
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Lowercase for search. mb_strtolower() turns the Azerbaijani "İ" into
     * "i" + U+0307, which never matches stored text, so fold it first.
     */
    private static function lower(string $text): string
    {
        return mb_strtolower(str_replace('İ', 'i', $text));
    }

    /**
     * Split a query into words, each expanded with its own synonyms and
     * diacritics-stripped variant. Returns one variant list per word.
     *
     * @return array<int, array<int, string>>
     */
    public static function tokenGroups(string $query): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', self::lower(trim($query)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $groups = [];
        foreach ($words as $word) {
            if (mb_strlen($word) < self::MIN_TOKEN_LENGTH) {
                continue;
            }
            $groups[] = self::expandQuery($word);
        }

        return $groups;
    }

    /**
     * Build a product search query that checks translatable JSON columns
     * across all locales and uses synonym expansion.
     */
    public static function buildProductQuery(Builder $baseQuery, string $query): Builder
    {
        return self::applyMatcher(
            $baseQuery,
            $query,
            self::productTermMatcher(),
            self::findMatchingSubCategoryIds($query),
        );
    }

    /**
     * Classifier-aware query resolution: product-class (sub_categories) ids
     * whose name matches the query, or that own a search_synonyms phrase the
     * query overlaps with ("kvars vinil" -> "kvars vinil plitə" -> Laminat
     * class). Both directions are checked: phrase contains the query, and
     * query contains the whole phrase. Plain MySQL LIKE, case-insensitive,
     * Azerbaijani-diacritics folded — same collation rules as orLike().
     *
     * @return array<int, int>
     */
    public static function findMatchingSubCategoryIds(string $query): array
    {
        $original = self::lower(trim($query));

        // Too-short fragments would sweep whole classes into every search.
        if (mb_strlen($original) < 3) {
            return [];
        }

        $normalized = self::normalizeAzeri($original);
        $terms = array_values(array_unique([$original, $normalized]));

        $byClassName = SubCategory::query()
            ->where('is_active', true)
            ->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    self::orLike($q, 'sub_categories.name', $term);
                }
            })
            ->pluck('id');

        $byPhrase = SearchSynonym::query()
            ->where(function (Builder $q) use ($terms, $normalized) {
                // Phrase contains the query…
                foreach ($terms as $term) {
                    self::orLike($q, 'search_synonyms.phrase', $term);
                }
                // …or the query contains the whole phrase.
                $q->orWhereRaw(
                    "? like concat('%', ".self::columnExpression('search_synonyms.phrase', $normalized).", '%')",
                    [$normalized]
                );
            })
            ->pluck('sub_category_id');

        return $byClassName->merge($byPhrase)->unique()->values()->all();
    }

    /**
     * Build a specialist/master search query that checks craft, skills,
     * specialty and user name.
     */
    public static function buildSpecialistQuery(Builder $baseQuery, string $query): Builder
    {
        return self::applyMatcher($baseQuery, $query, self::specialistTermMatcher());
    }

    /**
     * Build a blog post search query across translatable title/excerpt/body.
     */
    public static function buildBlogQuery(Builder $baseQuery, string $query): Builder
    {
        return self::applyMatcher($baseQuery, $query, function (Builder $q, string $term): void {
            self::orLike($q, 'blog_posts.title', $term);
            self::orLike($q, 'blog_posts.excerpt', $term);
            self::orLike($q, 'blog_posts.body', $term);
        });
    }

    /**
     * Seller-cabinet product filter (name is a translatable json column, sku
     * is a plain varchar). Kept here so the cabinet gets the same
     * case-insensitive comparison as the public catalog.
     */
    public static function buildSellerProductQuery(Builder $baseQuery, string $search): Builder
    {
        $term = self::lower(trim($search));

        if ($term === '') {
            return $baseQuery;
        }

        return $baseQuery->where(function (Builder $q) use ($term) {
            self::orLike($q, 'products.name', $term);
            self::orLike($q, 'products.sku', $term);
        });
    }

    /**
     * Push products whose name contains the whole typed phrase to the top.
     * Applied before any other ordering so it acts as the primary sort.
     */
    public static function orderProductsByRelevance(Builder $query, string $search): Builder
    {
        $phrase = self::lower(trim($search));

        if ($phrase === '') {
            return $query;
        }

        return $query->orderByRaw(
            self::columnExpression('products.name', $phrase).' like ? desc',
            ['%'.$phrase.'%']
        );
    }

    /**
     * ORs a single term across every searchable product field.
     */
    private static function productTermMatcher(): callable
    {
        return function (Builder $q, string $term): void {
            // Search the raw JSON column so all locale values are checked.
            // Brand is a relation (brand_id) — the string column was dropped.
            self::orLike($q, 'products.name', $term);
            self::orLike($q, 'products.description', $term);
            // The relation callbacks must nest, otherwise the OR would escape
            // the closure and cancel the foreign-key constraint of the EXISTS.
            $q->orWhereHas('brand', fn (Builder $bq) => $bq->where(fn (Builder $i) => self::orLike($i, 'brands.name', $term)));
            $q->orWhereHas('category', fn (Builder $cq) => $cq->where(fn (Builder $i) => self::orLike($i, 'categories.name', $term)));
        };
    }

    /**
     * ORs a single term across every searchable specialist field.
     */
    private static function specialistTermMatcher(): callable
    {
        return function (Builder $q, string $term): void {
            self::orLike($q, 'specialist_profiles.craft', $term);
            self::orLike($q, 'specialist_profiles.skills', $term);
            $q->orWhereHas('specialty', fn (Builder $sq) => $sq->where(fn (Builder $i) => self::orLike($i, 'specialist_specialties.name', $term)));
            $q->orWhereHas('user', function (Builder $uq) use ($term) {
                $uq->where(function (Builder $inner) use ($term) {
                    self::orLike($inner, 'users.first_name', $term);
                    self::orLike($inner, 'users.last_name', $term);
                });
            });
        };
    }

    /**
     * Wrap the base query in "whole phrase matches OR every word matches".
     * The phrase branch keeps the original synonym-expansion behaviour; the
     * token branch makes multi-word queries match when the words are spread
     * across a name (e.g. "Keramik kafel 30" -> "Keramik kafel 30x60 ağ mat").
     */
    private static function applyMatcher(Builder $baseQuery, string $query, callable $orTerm, array $subCategoryIds = []): Builder
    {
        $phraseTerms = self::phraseTerms($query);

        if ($phraseTerms === []) {
            return $baseQuery;
        }

        $tokenGroups = self::tokenGroups($query);

        return $baseQuery->where(function (Builder $q) use ($phraseTerms, $tokenGroups, $orTerm, $subCategoryIds) {
            $q->where(function (Builder $phrase) use ($phraseTerms, $orTerm) {
                foreach ($phraseTerms as $term) {
                    $orTerm($phrase, $term);
                }
            });

            if ($tokenGroups !== []) {
                $q->orWhere(function (Builder $all) use ($tokenGroups, $orTerm) {
                    foreach ($tokenGroups as $variants) {
                        $all->where(function (Builder $word) use ($variants, $orTerm) {
                            foreach ($variants as $term) {
                                $orTerm($word, $term);
                            }
                        });
                    }
                });
            }

            // Classifier branch: the query resolved to product classes (via the
            // class name or a seeded search synonym) — include their products.
            if ($subCategoryIds !== []) {
                $q->orWhereIn('products.sub_category_id', $subCategoryIds);
            }
        });
    }

    /**
     * Terms for the "whole phrase" branch of the matcher.
     *
     * A single word keeps the full synonym expansion — there the synonyms *are*
     * the whole query. For a multi-word phrase the synonyms are substituted
     * INSIDE the phrase instead of being emitted as bare terms: otherwise
     * "Keramik kafel 30" would OR a lone "kafel" (and "tile", "plitka", ...)
     * and match every tile in the catalog even though "Keramik" matches
     * nothing. Word-level matching is the token branch's job, and it ANDs the
     * words instead of ORing them.
     *
     * @return array<int, string>
     */
    private static function phraseTerms(string $query): array
    {
        $original = self::lower(trim($query));

        if ($original === '') {
            return [];
        }

        $words = preg_split('/[^\p{L}\p{N}]+/u', $original, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) <= 1) {
            return self::expandQuery($query);
        }

        $normalized = self::normalizeAzeri($original);
        $terms = [$original];

        if ($normalized !== $original) {
            $terms[] = $normalized;
        }

        foreach (self::$synonyms as $key => $values) {
            $lowerKey = self::lower($key);

            foreach (array_unique([$original, $normalized]) as $subject) {
                if (mb_strpos($subject, $lowerKey) === false) {
                    continue;
                }

                foreach ($values as $value) {
                    $terms[] = str_replace($lowerKey, self::lower($value), $subject);
                }
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Case-insensitive `like` on a (possibly JSON) column, OR-ed into $q.
     * When the term is a diacritics-stripped variant the column is folded the
     * same way, so "doseme" can still find "döşəmə".
     */
    private static function orLike(Builder $q, string $column, string $term): void
    {
        $q->orWhereRaw(self::columnExpression($column, $term).' like ?', ['%'.$term.'%']);
    }

    /**
     * SQL expression for a searchable column, case-insensitive and — when the
     * term itself carries no Azerbaijani diacritics — accent-folded.
     */
    private static function columnExpression(string $column, string $term): string
    {
        $expression = 'lower(cast('.self::wrapColumn($column).' as char) collate '.self::CI_COLLATION.')';

        if ($term !== self::normalizeAzeri($term)) {
            return $expression;
        }

        foreach (self::AZERI_FOLD as $from => $to) {
            $expression = "replace({$expression}, '{$from}', '{$to}')";
        }

        return $expression;
    }

    /**
     * Quote a (optionally table-qualified) column identifier.
     */
    private static function wrapColumn(string $column): string
    {
        if (! preg_match('/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)?$/', $column)) {
            throw new \InvalidArgumentException("Invalid search column [{$column}].");
        }

        return implode('.', array_map(fn (string $part) => '`'.$part.'`', explode('.', $column)));
    }

    /**
     * Get synonym suggestions that match the query.
     * Returns terms the user might also be interested in.
     */
    public static function getSuggestions(string $query): array
    {
        $original = self::lower(trim($query));
        $normalized = self::normalizeAzeri($original);
        $suggestions = [];

        foreach (self::$synonyms as $key => $values) {
            $lowerKey = self::lower($key);
            if (mb_strpos($original, $lowerKey) !== false || mb_strpos($normalized, self::normalizeAzeri($lowerKey)) !== false) {
                foreach ($values as $v) {
                    $lower = self::lower($v);
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
    public static function findMatchingCategoryIds(string $query): array
    {
        $terms = self::expandQuery($query);

        if ($terms === []) {
            return [];
        }

        return Category::active()
            ->where(function (Builder $q) use ($terms) {
                foreach ($terms as $term) {
                    self::orLike($q, 'categories.name', $term);
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
