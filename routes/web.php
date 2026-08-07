<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Cabinet\SecurityController;
use App\Http\Controllers\Cabinet\SpecialistCabinetController;
use App\Http\Controllers\Cabinet\SpecialistProfileController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ConsultationRequestController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SpecialistController;
use App\Http\Controllers\WishlistController;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\Review;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
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
Route::post('/consultation-requests', [ConsultationRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('consultation-requests.store');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/article', function () {
    $post = BlogPost::published()->latest('published_at')->first();
    if ($post) {
        return redirect()->route('blog.show', $post->slug);
    }

    return redirect()->route('blog');
})->name('blog.article');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/about', [AboutController::class, 'index'])->name('about');

// Legacy routes — keep named routes that frontend references
Route::get('/product', function () {
    $product = Product::visible()->approved()->latest()->first();
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
Route::get('/register', function () {
    app()->setLocale(session('locale', config('app.locale')));

    return view('pages.register', [
        'specialties' => SpecialistSpecialty::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'),
    ]);
})->middleware('guest')->name('register');
Route::get('/forgot-password', fn () => archiView('pages.forgot-password'))->middleware('guest')->name('password.request');
Route::get('/reset-password', fn () => archiView('pages.reset-password'))->middleware('guest')->name('password.reset');
Route::get('/otp', fn () => archiView('pages.otp'))->middleware('guest')->name('otp.verify');
Route::get('/help', [HelpController::class, 'index'])->name('help');
Route::get('/cart', [CartController::class, 'index'])->name('cart');

// Specialist owner mode + onboarding + cabinet (must be before the {specialist} wildcard).
Route::get('/specialist/owner', function () {
    app()->setLocale(session('locale', config('app.locale')));
    $user = auth()->user();
    $profile = $user?->specialistProfile?->load(['services', 'portfolioItems']);

    return view('pages.specialist-owner', compact('user', 'profile'));
})->middleware(['auth', 'role:master'])->name('specialist.owner');

Route::get('/specialist/onboarding', function () {
    app()->setLocale(session('locale', config('app.locale')));
    $user = auth()->user();
    $profile = $user?->specialistProfile;

    return view('pages.specialist-onboarding', compact('user', 'profile'));
})->middleware(['auth', 'role:master'])->name('specialist.onboarding');

Route::middleware(['auth', 'role:master'])->group(function () {
    Route::get('/specialist/cabinet', [SpecialistCabinetController::class, 'profile'])->name('specialist.cabinet');

    Route::get('/specialist/cabinet/security', function () {
        app()->setLocale(session('locale', config('app.locale')));

        return view('pages.specialist-cabinet-security');
    })->name('specialist.cabinet.security');

    Route::get('/specialist/cabinet/portfolio', [SpecialistCabinetController::class, 'portfolio'])->name('specialist.cabinet.portfolio');
    Route::post('/specialist/cabinet/portfolio', [SpecialistCabinetController::class, 'updatePortfolio'])->name('specialist.cabinet.portfolio.update');

    Route::get('/specialist/cabinet/services', [SpecialistCabinetController::class, 'services'])->name('specialist.cabinet.services');
    Route::put('/specialist/cabinet/services', [SpecialistCabinetController::class, 'updateServices'])->name('specialist.cabinet.services.update');

    Route::get('/specialist/cabinet/schedule', [SpecialistCabinetController::class, 'schedule'])->name('specialist.cabinet.schedule');
    Route::put('/specialist/cabinet/schedule', [SpecialistCabinetController::class, 'updateSchedule'])->name('specialist.cabinet.schedule.update');

    Route::put('/specialist/cabinet', [SpecialistProfileController::class, 'update'])->name('specialist.cabinet.update');
    Route::post('/specialist/cabinet/avatar', [SpecialistProfileController::class, 'updateAvatar'])->name('specialist.cabinet.avatar.update');
    Route::delete('/specialist/cabinet/avatar', [SpecialistProfileController::class, 'deleteAvatar'])->name('specialist.cabinet.avatar.destroy');

    Route::get('/specialist/cabinet/reviews', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $user = auth()->user();
        $profile = $user->specialistProfile;
        $reviews = $profile
            ? Review::where('reviewable_type', SpecialistProfile::class)
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

// Legal / policy pages
Route::get('/terms', [LegalPageController::class, 'show'])->defaults('slug', 'terms')->name('legal.terms');
Route::get('/privacy', [LegalPageController::class, 'show'])->defaults('slug', 'privacy')->name('legal.privacy');
Route::get('/delivery', [LegalPageController::class, 'show'])->defaults('slug', 'delivery')->name('legal.delivery');
Route::get('/cookies', [LegalPageController::class, 'show'])->defaults('slug', 'cookies')->name('legal.cookies');
Route::get('/calculator/detailed', fn () => archiView('pages.calculator-detailed'))->name('calculator.detailed');

Route::get('/business/register', fn () => archiView('pages.business-register'))->name('business.register');
Route::get('/business/onboarding/step-1', fn () => archiView('pages.business-onboarding-step1'))->name('business.onboarding.step1');
Route::get('/business/onboarding/step-2', fn () => archiView('pages.business-onboarding-step2'))->name('business.onboarding.step2');
Route::get('/business/onboarding/step-3', fn () => archiView('pages.business-onboarding-step3'))->name('business.onboarding.step3');
Route::get('/business/onboarding/step-4', fn () => archiView('pages.business-onboarding-step4'))->name('business.onboarding.step4');

Route::middleware(['auth', 'role:seller,admin'])->group(function () {
    // Cabinet pages
    Route::get('/business/profile', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'storefront'])->name('business.profile');

    Route::get('/business/profile/company', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $profile = auth()->user()->sellerProfile;
        return view('pages.business-profile-company', compact('profile'));
    })->name('business.profile.company');

    Route::get('/business/profile/contact', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $profile = auth()->user()->sellerProfile;
        return view('pages.business-profile-contact', compact('profile'));
    })->name('business.profile.contact');

    Route::get('/business/profile/showrooms', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $profile = auth()->user()->sellerProfile;
        $showrooms = $profile ? $profile->showrooms : collect();
        return view('pages.business-profile-showrooms', compact('profile', 'showrooms'));
    })->name('business.profile.showrooms');

    Route::get('/business/profile/notifications', function () {
        app()->setLocale(session('locale', config('app.locale')));
        $profile = auth()->user()->sellerProfile;
        return view('pages.business-profile-notifications', compact('profile'));
    })->name('business.profile.notifications');

    Route::get('/business/profile/security', fn () => archiView('pages.business-profile-security'))->name('business.profile.security');

    // Products & inventory
    Route::get('/business/profile/products', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'index'])->name('business.profile.products');
    Route::get('/business/products/create', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'create'])->name('business.products.create');
    Route::get('/business/products/{product}/edit', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'edit'])->name('business.products.edit');
    Route::get('/business/inventory', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'inventory'])->name('business.inventory');
    Route::post('/business/products', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'store'])->name('business.products.store');
    Route::put('/business/products/{product}', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'update'])->name('business.products.update');
    Route::post('/business/products/{product}/toggle', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'toggleVisibility'])->name('business.products.toggle');
    Route::post('/business/products/{product}/stock', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'updateStock'])->name('business.products.stock');
    Route::delete('/business/products/{product}', [\App\Http\Controllers\Cabinet\BusinessProductController::class, 'destroy'])->name('business.products.destroy');

    // Orders
    Route::get('/business/orders', [\App\Http\Controllers\Cabinet\BusinessOrderController::class, 'index'])->name('business.orders');
    Route::get('/business/orders/{order}', [\App\Http\Controllers\Cabinet\BusinessOrderController::class, 'show'])->name('business.orders.show');
    Route::post('/business/orders/{order}/status', [\App\Http\Controllers\Cabinet\BusinessOrderController::class, 'updateStatus'])->name('business.orders.status');

    // Profile API
    Route::put('/business/profile/company', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'updateCompany'])->name('business.profile.company.update');
    Route::put('/business/profile/contact', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'updateContact'])->name('business.profile.contact.update');
    Route::put('/business/profile/notifications', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'updateNotifications'])->name('business.profile.notifications.update');
    Route::post('/business/profile/logo', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'updateLogo'])->name('business.profile.logo');
    Route::delete('/business/profile/logo', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'deleteLogo'])->name('business.profile.logo.delete');
    Route::post('/business/profile/cover', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'updateCover'])->name('business.profile.cover');
    Route::delete('/business/profile/cover', [\App\Http\Controllers\Cabinet\BusinessProfileController::class, 'deleteCover'])->name('business.profile.cover.delete');

    // Showrooms API
    Route::post('/business/showrooms', [\App\Http\Controllers\Cabinet\ShowroomController::class, 'store'])->name('business.showrooms.store');
    Route::put('/business/showrooms/{showroom}', [\App\Http\Controllers\Cabinet\ShowroomController::class, 'update'])->name('business.showrooms.update');
    Route::delete('/business/showrooms/{showroom}', [\App\Http\Controllers\Cabinet\ShowroomController::class, 'destroy'])->name('business.showrooms.destroy');
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
    Route::post('/cabinet/password', [SecurityController::class, 'changePassword'])->name('cabinet.password');
    Route::post('/cabinet/deactivate', [SecurityController::class, 'deactivateAccount'])->name('cabinet.deactivate');

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
