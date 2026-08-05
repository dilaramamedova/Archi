<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SpecialistController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

if (! function_exists('archiView')) {
    function archiView(string $view)
    {
        app()->setLocale(session('locale', config('app.locale')));

        return view($view);
    }
}

// --- Dynamic pages (controller-driven) ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/article', function () {
    $post = \App\Models\BlogPost::published()->latest('published_at')->first();
    if ($post) {
        return redirect()->route('blog.show', $post->slug);
    }
    return redirect()->route('blog');
})->name('blog.article');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/about', [AboutController::class, 'index'])->name('about');

// Legacy routes — keep named routes that frontend references
Route::get('/product', function () {
    $product = \App\Models\Product::visible()->approved()->latest()->first();
    if ($product) {
        return redirect()->route('product.show', $product->slug);
    }
    return redirect()->route('catalog');
})->name('product');

// --- Static pages ---
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/specialists', [SpecialistController::class, 'index'])->name('specialists');
Route::get('/sell', [SellController::class, 'index'])->name('sell');
Route::post('/sell', [SellController::class, 'store'])->middleware('auth')->name('sell.store');
Route::get('/login', fn () => archiView('pages.login'))->middleware('guest')->name('login');
Route::get('/register', fn () => archiView('pages.register'))->middleware('guest')->name('register');
Route::get('/forgot-password', fn () => archiView('pages.forgot-password'))->middleware('guest')->name('password.request');
Route::get('/cart', [CartController::class, 'index'])->name('cart');

// Specialist owner mode + onboarding + cabinet (must be before the {specialist} wildcard).
Route::get('/specialist/owner', function () {
    app()->setLocale(session('locale', config('app.locale')));
    $user = auth()->user();
    $profile = $user?->specialistProfile?->load(['services', 'portfolioItems']);
    return view('pages.specialist-owner', compact('user', 'profile'));
})->middleware('auth')->name('specialist.owner');

Route::get('/specialist/onboarding', function () {
    app()->setLocale(session('locale', config('app.locale')));
    $user = auth()->user();
    $profile = $user?->specialistProfile;
    return view('pages.specialist-onboarding', compact('user', 'profile'));
})->middleware('auth')->name('specialist.onboarding');

Route::middleware('auth')->group(function () {
    Route::get('/specialist/cabinet', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $user = auth()->user();
        $profile = $user->specialistProfile;

        return view('pages.specialist-cabinet', compact('user', 'profile'));
    })->name('specialist.cabinet');

    Route::get('/specialist/cabinet/security', function () {
        app()->setLocale(session('locale', config('app.locale')));

        return view('pages.specialist-cabinet-security');
    })->name('specialist.cabinet.security');

    Route::get('/specialist/cabinet/portfolio', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $user = auth()->user();
        $profile = $user->specialistProfile;
        $portfolioItems = $profile ? $profile->portfolioItems()->orderBy('sort_order')->get() : collect();
        $maxPortfolio = 30;

        return view('pages.specialist-cabinet-portfolio', compact('user', 'profile', 'portfolioItems', 'maxPortfolio'));
    })->name('specialist.cabinet.portfolio');

    Route::get('/specialist/cabinet/services', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $user = auth()->user();
        $profile = $user->specialistProfile;
        $services = $profile ? $profile->services()->orderBy('sort_order')->get() : collect();

        return view('pages.specialist-cabinet-services', compact('user', 'profile', 'services'));
    })->name('specialist.cabinet.services');

    Route::get('/specialist/cabinet/schedule', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $user = auth()->user();
        $profile = $user->specialistProfile;
        $schedules = $profile ? $profile->schedules()->orderBy('day_of_week')->get() : collect();

        return view('pages.specialist-cabinet-schedule', compact('user', 'profile', 'schedules'));
    })->name('specialist.cabinet.schedule');

    Route::get('/specialist/cabinet/reviews', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $user = auth()->user();
        $profile = $user->specialistProfile;
        $reviews = $profile
            ? \App\Models\Review::where('reviewable_type', \App\Models\SpecialistProfile::class)
                ->where('reviewable_id', $profile->id)
                ->with('user')
                ->latest()
                ->paginate(20)
            : collect();

        return view('pages.specialist-cabinet-reviews', compact('user', 'profile', 'reviews'));
    })->name('specialist.cabinet.reviews');

    Route::get('/specialist/cabinet/notifications', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $user = auth()->user();
        $profile = $user->specialistProfile;

        return view('pages.specialist-cabinet-notifications', compact('user', 'profile'));
    })->name('specialist.cabinet.notifications');
});

// Specialist detail page — placed after all /specialist/* fixed routes so {specialist}
// does not swallow "owner", "onboarding", or "cabinet" segments.
Route::get('/specialist/{specialist}', [SpecialistController::class, 'show'])->name('specialist.show');

Route::get('/calculator', fn () => archiView('pages.calculator'))->name('calculator');
Route::get('/calculator/detailed', fn () => archiView('pages.calculator-detailed'))->name('calculator.detailed');

Route::get('/business/register', fn () => archiView('pages.business-register'))->name('business.register');
Route::get('/business/onboarding/step-1', fn () => archiView('pages.business-onboarding-step1'))->name('business.onboarding.step1');
Route::get('/business/onboarding/step-2', fn () => archiView('pages.business-onboarding-step2'))->name('business.onboarding.step2');
Route::get('/business/onboarding/step-3', fn () => archiView('pages.business-onboarding-step3'))->name('business.onboarding.step3');
Route::get('/business/onboarding/step-4', fn () => archiView('pages.business-onboarding-step4'))->name('business.onboarding.step4');

Route::middleware('auth')->group(function () {
    Route::get('/business/profile', fn () => archiView('pages.business-profile'))->name('business.profile');
    Route::get('/business/profile/company', fn () => archiView('pages.business-profile-company'))->name('business.profile.company');
    Route::get('/business/profile/contact', fn () => archiView('pages.business-profile-contact'))->name('business.profile.contact');
    Route::get('/business/profile/products', fn () => archiView('pages.business-profile-products'))->name('business.profile.products');
    Route::get('/business/profile/showrooms', fn () => archiView('pages.business-profile-showrooms'))->name('business.profile.showrooms');
    Route::get('/business/profile/notifications', fn () => archiView('pages.business-profile-notifications'))->name('business.profile.notifications');
    Route::get('/business/profile/security', fn () => archiView('pages.business-profile-security'))->name('business.profile.security');
});

// Auth routes
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// API-style routes
Route::get('/api/search', [SearchController::class, 'autocomplete'])->name('api.search');
Route::post('/api/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('api.newsletter.subscribe');
Route::get('/api/cart/count', [CartController::class, 'count'])->name('api.cart.count');

// Orders (guests + auth)
Route::post('/api/orders', [OrderController::class, 'store'])->name('api.orders.store');
Route::get('/order/{order}/success', [OrderController::class, 'success'])->name('order.success');

// Cabinet routes (authenticated)
Route::middleware('auth')->group(function () {
    // Account / profile
    Route::get('/account', [AccountController::class, 'profile'])->name('account');
    Route::put('/account', [AccountController::class, 'updateProfile'])->name('account.update');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    Route::post('/api/wishlist/toggle', [WishlistController::class, 'toggle'])->name('api.wishlist.toggle');

    // Cart API
    Route::post('/api/cart', [CartController::class, 'store'])->name('api.cart.store');
    Route::put('/api/cart/{cartItem}', [CartController::class, 'update'])->name('api.cart.update');
    Route::delete('/api/cart/{cartItem}', [CartController::class, 'destroy'])->name('api.cart.destroy');

    // Security (shared by all user types)
    Route::post('/cabinet/password', [\App\Http\Controllers\Cabinet\SecurityController::class, 'changePassword'])->name('cabinet.password');
    Route::get('/cabinet/sessions', [\App\Http\Controllers\Cabinet\SecurityController::class, 'sessions'])->name('cabinet.sessions');
    Route::delete('/cabinet/sessions', [\App\Http\Controllers\Cabinet\SecurityController::class, 'destroySession'])->name('cabinet.sessions.destroy');
    Route::post('/cabinet/deactivate', [\App\Http\Controllers\Cabinet\SecurityController::class, 'deactivateAccount'])->name('cabinet.deactivate');

    // Specialist profile
    Route::put('/specialist/cabinet', [\App\Http\Controllers\Cabinet\SpecialistProfileController::class, 'update'])->name('specialist.cabinet.update');

    // Reviews
    Route::post('/api/reviews', [ReviewController::class, 'store'])->name('api.reviews.store');
    Route::post('/api/reviews/{review}/helpful', [ReviewController::class, 'helpful'])->name('api.reviews.helpful');
});

// Language switch
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['az', 'ru', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang');
