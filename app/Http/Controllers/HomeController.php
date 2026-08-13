<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromoBanner;
use App\Models\SaleBanner;
use App\Models\ServiceIcon;
use App\Models\SpecialistProfile;
use App\Support\CatalogNavigation;

class HomeController extends Controller
{
    public function index()
    {
        $heroMain = Banner::position('hero_main')->active()->ordered()->first();

        $heroPromo = Banner::position('hero_promo')->active()->ordered()->get();

        $heroRole = Banner::position('hero_role')->active()->ordered()->get();

        $categories = Category::roots()->active()->showOnHome()->ordered()->get();

        // Nothing curated for the homepage yet (e.g. a fresh install where
        // show_on_home was never set) — fall back to the real root categories
        // rather than to hardcoded demo tiles.
        if ($categories->isEmpty()) {
            $categories = Category::roots()->active()->ordered()->take(7)->get();
        }

        // Real per-section count of products a visitor can actually open, rolled
        // up over the whole classifier subtree (section -> groups -> classes).
        // withCount('products') counted only products whose category_id is the
        // ROOT id — the classifier never attaches products there, so every tile
        // read "0 məhsul". Same rollup the catalog rail uses.
        CatalogNavigation::withRolledUpCounts($categories);

        // Home grids show only the curated subset: flagged (sale/featured) AND
        // explicitly picked for the homepage (show_on_homepage).
        $saleProducts = Product::visible()->approved()->sale()
            ->where('show_on_homepage', true)
            ->with(['images', 'category', 'badges'])
            // Same rule as the catalog: a product positioned in the admin panel
            // leads, the rest stay newest-first.
            ->orderByRaw('sort_order = 0')
            ->orderBy('sort_order')
            ->latest()
            ->take(4)
            ->get();

        $featuredProducts = Product::visible()->approved()->featured()
            ->where('show_on_homepage', true)
            ->with(['images', 'category', 'badges'])
            // Same rule as the catalog: a product positioned in the admin panel
            // leads, the rest stay newest-first.
            ->orderByRaw('sort_order = 0')
            ->orderBy('sort_order')
            ->latest()
            ->take(4)
            ->get();

        $specialists = SpecialistProfile::where('is_featured', true)
            ->where('show_on_homepage', true)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with(['user', 'specialty'])
            ->take(4)
            ->get();

        $blogPosts = BlogPost::published()->showOnHome()
            ->latest('published_at')
            ->take(4)
            ->get();

        $promoBanners = PromoBanner::active()->ordered()->get();

        $serviceIcons = ServiceIcon::active()->ordered()->get();

        $saleBanners = SaleBanner::active()->ordered()->get();

        return view('pages.home', compact(
            'heroMain', 'heroPromo', 'heroRole',
            'categories', 'saleProducts', 'featuredProducts',
            'specialists', 'blogPosts', 'promoBanners',
            'serviceIcons', 'saleBanners'
        ));
    }
}
