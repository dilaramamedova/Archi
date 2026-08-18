<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\SearchSynonym;
use App\Models\SubCategory;
use App\Support\VersionedCache;
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
     * InnoDB's innodb_ft_min_token_size — words shorter than this are not in
     * the FULLTEXT index at all, so such queries fall back to the LIKE path.
     */
    private const FULLTEXT_MIN_TOKEN = 3;

    /**
     * Shortest query the search can serve. Below this the FULLTEXT index has
     * nothing to offer and the only alternative is an unindexed scan, so the
     * UI should not fire a search at all — see buildProductQuery().
     */
    public const MIN_SEARCH_LENGTH = 3;

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
        $expression = self::fullTextExpression($query);

        // Nothing in the query reaches InnoDB's minimum indexed word length —
        // a one or two letter search. There is deliberately no LIKE fallback
        // here: that path is a full table scan with nested replace() per row
        // and measured 17 seconds on 60k products, so a stray keystroke in the
        // autocomplete would have been enough to stall the database. A search
        // that short cannot produce a useful result in a catalog this size
        // anyway, so it returns nothing instead. Callers should not offer
        // product search below MIN_SEARCH_LENGTH characters.
        if ($expression === null) {
            return $baseQuery->whereRaw('1 = 0');
        }

        // One MATCH over both search columns and nothing else. search_text holds
        // the product's own translatable text; search_context holds its brand,
        // category, class and the class's colloquial synonyms, denormalized
        // onto the row precisely so this predicate stays a single index lookup
        // (see the 2026_08_14_100400 migration for the measurements that ruled
        // out the OR and UNION alternatives).
        return $baseQuery->whereFullText(
            ['products.search_text', 'products.search_context'],
            $expression,
            ['mode' => 'boolean'],
        );
    }

    /**
     * Translate a user query into a MySQL boolean-mode FULLTEXT expression that
     * reproduces the matcher's contract:
     *
     *   "whole phrase"  (+(word1 word1syn*) +(word2 word2syn*))
     *
     * — the quoted phrase OR every word present, each word satisfiable by any
     * of its synonyms. Trailing `*` gives prefix matching, so "lam" still finds
     * "laminat" the way the old `LIKE '%lam%'` did for a prefix.
     *
     * Returns null when any token is shorter than InnoDB's indexed word length,
     * because such a token would silently match nothing.
     */
    private static function fullTextExpression(string $query): ?string
    {
        // ORDER MATTERS in this expression, and not for readability: MySQL's
        // boolean mode treats a leading quoted phrase differently from a
        // trailing one. Measured on this schema: '"parket premium" (+(parket*)
        // +(premium*))' returned 1 row, while '(+(parket*) +(premium*))
        // "parket premium"' returned the correct 2 — a leading optional phrase
        // suppressed rows that only satisfied the word group. The word group
        // therefore always goes FIRST and every phrase is appended after it as
        // a widening alternative. (Found in end-to-end QA: a freshly published
        // product matched every word of the query and still didn't surface.)
        $phrases = [];

        foreach (self::phraseTerms($query) as $phrase) {
            if ($words = self::indexableWords($phrase)) {
                $phrases[] = '"'.implode(' ', $words).'"';
            }
        }

        // Reverse containment: the QUERY contains a whole class phrase, as in
        // "ucuz kvars vinil plitə almaq". The token branch below ANDs every
        // word, so "ucuz" and "almaq" would rule the class's products out; the
        // phrase is added as a widening alternative instead — it stays inside
        // the same single MATCH because the phrases are in the products'
        // search_context.
        foreach (self::phrasesContainedIn($query) as $phrase) {
            $phrases[] = '"'.$phrase.'"';
        }

        $required = [];

        foreach (self::tokenGroups($query) as $variants) {
            $alternatives = [];

            foreach ($variants as $variant) {
                $words = self::indexableWords($variant);

                if ($words === []) {
                    // A synonym the index cannot represent — "su sistemi"
                    // contains a two-letter word — is dropped, NOT escalated
                    // into abandoning the index for the whole query. Bailing
                    // out here is what made a search for "santexnika" fall back
                    // to the LIKE scan and take 17 seconds on 60k products.
                    continue;
                }

                // A multi-word synonym has to match as a phrase, a single word
                // gets the prefix wildcard so "lam" still finds "laminat".
                $alternatives[] = count($words) > 1
                    ? '"'.implode(' ', $words).'"'
                    : $words[0].'*';
            }

            if ($alternatives !== []) {
                $required[] = '+('.implode(' ', array_unique($alternatives)).')';
            }
        }

        $parts = [];

        if ($required !== []) {
            $parts[] = '('.implode(' ', $required).')';
        }

        // Phrases strictly after the word group — see the ordering note above.
        array_push($parts, ...$phrases);

        return $parts === [] ? null : implode(' ', $parts);
    }

    /**
     * Class synonym phrases the query wholly contains, folded and ready to be
     * quoted into a boolean-mode expression.
     *
     * search_synonyms holds a few hundred rows and is edited from the admin, so
     * scanning it per search is cheap — but the autocomplete fires per
     * keystroke, so the result is cached for the aggregate TTL anyway.
     *
     * @return list<string>
     */
    private static function phrasesContainedIn(string $query): array
    {
        $normalized = self::normalizeAzeri(self::lower(trim($query)));

        if (mb_strlen($normalized) < self::FULLTEXT_MIN_TOKEN) {
            return [];
        }

        return VersionedCache::remember(
            VersionedCache::CATALOG,
            'contained-phrases:'.md5($normalized),
            VersionedCache::TTL_AGGREGATE,
            function () use ($normalized) {
                $phrases = SearchSynonym::query()
                    ->whereRaw("? like concat('%', ".self::columnExpression('search_synonyms.phrase', $normalized).", '%')", [$normalized])
                    ->pluck('phrase');

                return $phrases
                    ->map(fn (string $phrase) => self::normalizeAzeri(self::lower($phrase)))
                    // A phrase whose every word is indexable; anything shorter
                    // cannot be matched and would only break the expression.
                    ->filter(fn (string $phrase) => self::indexableWords($phrase) !== [])
                    ->unique()
                    ->values()
                    ->all();
            },
        );
    }

    /**
     * Split a term into the words FULLTEXT will actually have indexed, folded
     * the same way the generated search_text column is. Returns an empty list
     * when any word is below the index's minimum token size, since the term as
     * a whole could then never match.
     *
     * @return list<string>
     */
    private static function indexableWords(string $term): array
    {
        $folded = self::normalizeAzeri(self::lower($term));
        $words = preg_split('/[^\p{L}\p{N}]+/u', $folded, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($words as $word) {
            if (mb_strlen($word) < self::FULLTEXT_MIN_TOKEN) {
                return [];
            }
        }

        return $words;
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
        $term = trim($search);

        if ($term === '') {
            return $baseQuery;
        }

        $expression = self::fullTextExpression($term);

        // sku is part of the generated search_text column, so one MATCH covers
        // both the name and the article number and neither needs a LIKE.
        if ($expression !== null) {
            return $baseQuery->whereFullText(
                ['products.search_text', 'products.search_context'],
                $expression,
                ['mode' => 'boolean'],
            );
        }

        // One or two characters: too short for the index. A prefix LIKE on the
        // sku still uses its unique index, which is the only thing that can be
        // answered cheaply at this length.
        return $baseQuery->where('products.sku', 'like', $term.'%');
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
