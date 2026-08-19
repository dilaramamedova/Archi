<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\PromoBanner;
use App\Models\SaleBanner;
use App\Models\SearchSynonym;
use App\Models\ServiceIcon;
use App\Models\SocialLink;
use App\Models\SubCategory;
use App\Observers\ContentCacheObserver;
use App\Support\CatalogNavigation;
use App\Support\VersionedCache;
use App\Support\WindowsSafeFilesystem;
use App\Translation\DatabaseTranslationLoader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Translatable\Facades\Translatable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Which cache domains a model's edits invalidate. The header/footer chrome
     * is on every page, so a stale menu is the most visible kind of staleness —
     * these edits drop the cache immediately rather than waiting out a TTL.
     *
     * @var array<class-string, list<string>>
     */
    private const CACHE_INVALIDATION = [
        MenuItem::class => [VersionedCache::CHROME],
        SocialLink::class => [VersionedCache::CHROME],
        Category::class => [VersionedCache::CHROME, VersionedCache::CATALOG, VersionedCache::HOME],
        SubCategory::class => [VersionedCache::CATALOG],
        Brand::class => [VersionedCache::CATALOG],
        // Not cached anywhere, but the observer also refreshes the products'
        // denormalized search context, which a new synonym has to reach.
        SearchSynonym::class => [],
        Banner::class => [VersionedCache::HOME],
        PromoBanner::class => [VersionedCache::HOME],
        SaleBanner::class => [VersionedCache::HOME],
        ServiceIcon::class => [VersionedCache::HOME],
        BlogCategory::class => [VersionedCache::HOME],
        BlogPost::class => [VersionedCache::CHROME, VersionedCache::HOME],
    ];

    public function register(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->app->forgetInstance('files');
            $this->app->singleton('files', fn (): WindowsSafeFilesystem => new WindowsSafeFilesystem);
        }

        // Serve UI translations from the database (Filament "Tərcümələr" module),
        // falling back to lang/*/validation.php and other framework files on disk.
        $this->app->extend('translation.loader', function ($loader, $app): DatabaseTranslationLoader {
            return new DatabaseTranslationLoader($app['files'], $app['path.lang']);
        });
    }

    public function boot(): void
    {
        // Content is authored in Azerbaijani first; ru/en fall back to az until translated.
        Translatable::fallback(fallbackLocale: 'az');

        // Laravel's bundled paginator view switches its halves with sm:hidden/sm:flex,
        // and Tailwind does not scan vendor/, so those utilities were never compiled and
        // the numbered block was permanently display:none — every listing on the site
        // showed prev/next only. See resources/views/vendor/pagination/archi.blade.php.
        Paginator::defaultView('vendor.pagination.archi');
        Paginator::defaultSimpleView('vendor.pagination.archi');

        $this->registerCacheInvalidation();
        $this->guardAgainstLazyLoading();
        $this->composeLayout();
    }

    private function registerCacheInvalidation(): void
    {
        ContentCacheObserver::$domains = self::CACHE_INVALIDATION;

        foreach (array_keys(self::CACHE_INVALIDATION) as $model) {
            $model::observe(ContentCacheObserver::class);
        }
    }

    /**
     * Lazy loading is how an N+1 gets into production unnoticed: it is invisible
     * on a laptop with fifty products and quadratic on a catalog with sixty
     * thousand. In local development it now throws, so a missing with() surfaces
     * the moment the page is opened; production keeps serving the page.
     *
     * Tests are excluded deliberately. They walk relations on a single model to
     * assert its shape — `$class->category->parent`, `$attribute->options` —
     * which is one query for one row, not an N+1, and flagging it would only
     * push eager loads into tests that gain nothing from them. The guard earns
     * its keep against real pages rendering real lists.
     */
    private function guardAgainstLazyLoading(): void
    {
        Model::preventLazyLoading(! $this->app->isProduction() && ! $this->app->runningUnitTests());
    }

    /**
     * Header and footer furniture. This runs on every single page, and used to
     * cost ten queries per request for data that changes when an admin edits a
     * menu — perhaps weekly. It is now one cache read, invalidated by
     * ContentCacheObserver, with the per-user cart count left live.
     */
    private function composeLayout(): void
    {
        View::composer('components.layout', function ($view) {
            $chrome = VersionedCache::remember(
                VersionedCache::CHROME,
                'layout:'.app()->getLocale(),
                VersionedCache::TTL_STRUCTURAL,
                fn () => $this->buildChrome(),
            );

            $view->with($chrome + [
                // Per-user, so it can never be part of the shared cache entry.
                'cartCount' => auth()->check()
                    ? CartItem::where('user_id', auth()->id())->count()
                    : 0,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChrome(): array
    {
        $megaCatalog = MenuItem::location('header_mega_catalog')->roots()->active()->ordered()->get();

        return [
            'headerMenu' => MenuItem::location('header_main')->roots()->active()->ordered()
                ->with(['children' => fn ($q) => $q->active()->ordered()])
                ->get(),

            'megaCatalog' => $megaCatalog,

            // The same twelve section cards, grouped into the classifier's five
            // navigation clusters (App\Support\CatalogNavigation) — used by the
            // "Kataloq" mega panel and the mobile drawer. Empty when no card
            // maps to a section, in which case the navbar keeps the flat grid.
            'megaCatalogClusters' => CatalogNavigation::clusterMenuItems($megaCatalog),

            'megaSpecialists' => MenuItem::location('header_mega_specialists')->roots()->active()->ordered()->get(),

            // Admin-managed cards for the Bloq dropdown ("Header — Mega Bloq" in Filament).
            // When empty the panel falls back to the latest blog posts below.
            'megaBlogMenu' => MenuItem::location('header_mega_blog')->roots()->active()->ordered()->get(),

            'megaBlog' => BlogPost::published()->showInHeader()->latest('published_at')->take(3)->get(),

            'footerMenu' => MenuItem::location('footer')->roots()->active()->ordered()
                ->with(['children' => fn ($q) => $q->active()->ordered()])
                ->get(),

            'footerLegal' => MenuItem::location('footer_legal')->roots()->active()->ordered()->get(),

            'socialLinks' => SocialLink::active()->ordered()->get(),
        ];
    }
}
