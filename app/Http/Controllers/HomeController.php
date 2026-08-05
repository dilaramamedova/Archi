<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Banner;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use App\Models\PromoBanner;
use App\Models\SpecialistProfile;

class HomeController extends Controller
{
    public function index()
    {
        $heroMain = Banner::position('hero_main')->active()->ordered()->first();

        $heroPromo = Banner::position('hero_promo')->active()->ordered()->get();

        $heroRole = Banner::position('hero_role')->active()->ordered()->get();

        $categories = Category::roots()->active()->ordered()->take(7)->get();

        $saleProducts = Product::visible()->approved()->sale()
            ->with(['images', 'category'])
            ->take(4)
            ->latest()
            ->get();

        $featuredProducts = Product::visible()->approved()->featured()
            ->with(['images', 'category'])
            ->take(4)
            ->latest()
            ->get();

        $specialists = SpecialistProfile::where('is_featured', true)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with('user')
            ->take(4)
            ->get();

        $blogPosts = BlogPost::published()->showOnHome()
            ->latest('published_at')
            ->take(4)
            ->get();

        $promoBanners = PromoBanner::active()->ordered()->get();

        return view('pages.home', compact(
            'heroMain', 'heroPromo', 'heroRole',
            'categories', 'saleProducts', 'featuredProducts',
            'specialists', 'blogPosts', 'promoBanners'
        ));
    }
}
