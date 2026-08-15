# Production setup

Written against a catalog of ~60 000 products and ~100 000 users — the size this
deployment is expected to reach. Everything below was measured on that dataset,
seeded with `php artisan db:seed --class=ScaleSeeder`.

---

## 1. What the application already handles

These are code-level and need no infrastructure decision:

- Composite indexes matching every catalog access path, including
  descending-order indexes for the mixed-direction default sort.
- Review counts and average ratings denormalized onto `products` and
  `specialist_profiles`, maintained by `ReviewObserver`.
- Header/footer chrome, the classifier tree, the homepage and the catalog
  sidebar served from cache, invalidated on edit by `ContentCacheObserver`.
- Product search served by a MySQL FULLTEXT index over generated columns
  (`search_text`) plus a denormalized relational context (`search_context`).
- Product view counts buffered in cache instead of written per request.

## 2. What still needs infrastructure

### Redis — required

The single biggest remaining risk is the default file-based cache and session
storage. At 100 000 users, `SESSION_DRIVER=file` means hundreds of thousands of
files in one directory; every request stats, locks and rewrites one of them.

```dotenv
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_CLIENT=phpredis
```

Redis also makes `ProductViewCounter` behave as designed — on the file store it
still writes per view, just not to the database.

If Redis is genuinely unavailable, `CACHE_STORE=database` and
`SESSION_DRIVER=database` are the fallback. Never leave them on `file`.

### A queue worker — required

`QUEUE_CONNECTION=sync` runs every queued job inside the web request. Renaming a
brand dispatches `RefreshProductSearchContext`, which can touch thousands of
products; on `sync` the admin waits for all of them.

```bash
php artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
```

Run it under a process supervisor (systemd or supervisor) so it restarts.

### The scheduler — required

One cron entry drives all background maintenance:

```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Without it `views_count` stops updating and the catalog's popularity ordering
freezes. Nothing else breaks.

### Deploy-time caching

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

And ensure PHP OPcache is on with `opcache.validate_timestamps=0` in production.

### After the first deploy

```bash
php artisan migrate --force
php artisan search:rebuild
```

`search:rebuild` populates `products.search_context` for existing rows. It is
also required after any re-import of the classifier, because the seeder writes
with raw inserts and bypasses the model events that normally keep the column
fresh.

---

## 3. Measuring

```bash
php artisan bench:pages --warm
```

Reports query count and wall time per storefront route. Query *count* is the
number to watch: it should stay flat as the catalog grows. A count that rises
with the number of rows on the page is an N+1.

To fill the database to production size and to empty it again:

```bash
php artisan db:seed --class=ScaleSeeder
php artisan bench:purge
```

The seeder marks every row it creates with a `bench-` slug or email prefix, so
the purge removes exactly its own data and leaves real content alone.

Measured on 60 000 products / 100 000 users, warm:

| Route | Queries | Time |
|---|---|---|
| `/` | 8 | ~47 ms |
| `/catalog` | 12 | ~90 ms |
| `/catalog?sort=cheap` | 12 | ~93 ms |
| `/catalog?min_price=10&max_price=500` | 12 | ~86 ms |
| `/catalog?in_stock=1&free_delivery=1` | 12 | ~87 ms |
| `/search?q=laminat` | 11 | ~318 ms |
| `/api/search?q=lam` | 6 | ~77 ms |
| `/specialists` | 6 | ~24 ms |
| `/blog` | 5 | ~26 ms |

Individual measurements, before → after, on the same 60 000-product dataset:

| | Before | After |
|---|---|---|
| Default catalog listing (one query) | 163 ms, filesort over 58 515 rows | 17.5 ms, index |
| Catalog with stock + delivery filters | 89.5 ms | 1.7 ms |
| Paginator `COUNT` on a filtered catalog | 177 ms | 18.6 ms |
| Search for `santexnika` | **17 784 ms** | 114 ms |
| Search for `laminat` (one predicate) | 799 ms | 93 ms |
| Autocomplete, per keystroke | 4 × the above predicate | 1 ×, indexed |
| Homepage | 60 queries | 8 |
| Admin product list (25 rows of 60k) | 723 ms | 32 ms |

The homepage and catalog also carried hidden per-row queries: `average_rating`
and `reviews_count` each ran their own query per product card (48 extra round
trips on a 24-product grid), and the header/footer cost ten queries on every
page of the site.

---

## 4. Known limits and trade-offs

**Search needs three characters.** Below that MySQL's FULLTEXT index has nothing
indexed (`innodb_ft_min_token_size` defaults to 3) and the only alternative is
an unindexed scan, which measured 17.8 s. `SearchService::MIN_SEARCH_LENGTH`
enforces the floor and the autocomplete endpoint returns empty below it. If
two-character search is a requirement, it needs either
`innodb_ft_min_token_size=2` (a server setting plus a full index rebuild) or an
external engine.

**The FULLTEXT index updates at COMMIT.** A row inserted inside an open
transaction is invisible to `MATCH` until that transaction commits. This is
harmless in production but means search tests cannot use `RefreshDatabase`,
which wraps each test in a transaction — `SearchTest`, `CatalogClassifierUxTest`
and `ProductIntegrityTest` use `DatabaseTruncation` for this reason.

**Very common words are slow.** Boolean-mode full text intersects posting lists,
so a query where every word appears in most products is expensive (a seeded
worst case: 1 s). Real catalog text is varied enough that this does not arise,
but it is the shape of query to watch if search ever feels slow.

**Cached content can be one interval stale.** Editor content invalidates
immediately on save. Aggregates that nobody edits directly — sidebar counts,
the price slider bounds, brand product counts — carry a 10-minute TTL, so a
newly approved product joins those numbers within ten minutes rather than
instantly. Admin sidebar badges are cached for one minute.

**Cached models are an explicit allowlist.** Laravel 13 blocks unserializing any
class from the cache by default (gadget-chain protection). `config/cache.php`
lists the read-only content models the storefront caches. Adding a model to a
cached payload without adding it there returns `__PHP_Incomplete_Class` and 500s
on the first cache read.

---

## 5. If the catalog grows well past this size

The current design should hold to a few hundred thousand products. Beyond that,
the next steps in order of value:

1. **Meilisearch or Typesense via Laravel Scout.** `SearchService` is the only
   caller-facing seam; swapping the matcher there does not touch controllers.
   This also removes the three-character floor and gives real typo tolerance.
2. **A read replica** for the storefront, leaving writes on the primary.
3. **Cursor pagination** on the catalog. `paginate()` needs a `COUNT` and a
   growing `OFFSET`; both degrade on deep pages.
4. **A CDN** in front of images and static assets.
