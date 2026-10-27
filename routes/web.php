<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\Cabinet\BusinessOrderController;
use App\Http\Controllers\Cabinet\BusinessProductController;
use App\Http\Controllers\Cabinet\BusinessProfileController;
use App\Http\Controllers\Cabinet\SecurityController;
use App\Http\Controllers\Cabinet\ShowroomController;
use App\Http\Controllers\Cabinet\SpecialistCabinetController;
use App\Http\Controllers\Cabinet\SpecialistProfileController;
use App\Http\Controllers\CalculatorController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ConsultationRequestController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LaminateDocumentController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SpecialistController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\WishlistController;
use App\Models\BlogPost;
use App\Models\Product;
use App\Models\Review;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use Illuminate\Support\Facades\Route;

// archiView() now lives in app/helpers.php — declaring it here meant it vanished as
// soon as the routes were cached, because a cached route file replaces this one.

// --- Dynamic pages (controller-driven) ---
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/consultation-requests', [ConsultationRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('consultation-requests.store');
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/product/{slug}', [ProductController::class, 'show'])->name('product.show');
// Laminate passports / guides / conditions. Public for an approved card, owner and
// admin otherwise — the controller decides; uploads never sit on the public disk.
Route::get('/laminate-documents/{document}', LaminateDocumentController::class)
    ->name('laminate.documents.show');
// Public store page of a seller (the "Mağazaya keç" target on product pages).
// StoreController 404s any user that is not an active seller.
Route::get('/store/{seller}', [StoreController::class, 'show'])->name('store.show');
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

// The phone number is kept out of the public HTML and revealed only to signed-in
// visitors, so it cannot be scraped straight off the profile page.
Route::get('/api/specialist/{specialist}/phone', [SpecialistController::class, 'phone'])
    ->middleware(['auth', 'throttle:20,1'])
    ->name('api.specialist.phone');

// Specialist detail page — placed after all /specialist/* fixed routes so {specialist}
// does not swallow "owner", "onboarding", or "cabinet" segments.
Route::get('/specialist/{specialist}', [SpecialistController::class, 'show'])->name('specialist.show');

// Renovation calculator. The pages are controller-driven because every price they
// show comes from the database — no rate may live in a Blade file or a JS module
// (TZ §30, master spec §24).
Route::get('/calculator', [CalculatorController::class, 'page'])->name('calculator');

// Legal / policy pages
Route::get('/terms', [LegalPageController::class, 'show'])->defaults('slug', 'terms')->name('legal.terms');
Route::get('/privacy', [LegalPageController::class, 'show'])->defaults('slug', 'privacy')->name('legal.privacy');
Route::get('/delivery', [LegalPageController::class, 'show'])->defaults('slug', 'delivery')->name('legal.delivery');
Route::get('/cookies', [LegalPageController::class, 'show'])->defaults('slug', 'cookies')->name('legal.cookies');
Route::get('/calculator/detailed', [CalculatorController::class, 'detailedPage'])->name('calculator.detailed');

// Calculator API. Read endpoints are public — the quick calculator has to work for a
// visitor who is not logged in and collects no personal data (TZ §46).
Route::prefix('api/calculator')->name('api.calculator.')->group(function () {
    Route::get('/quick-rates', [CalculatorController::class, 'quickRates'])->name('quick-rates');
    Route::post('/quick-estimate', [CalculatorController::class, 'quickEstimate'])->middleware('throttle:60,1')->name('quick-estimate');
    Route::get('/config', [CalculatorController::class, 'config'])->name('config');
    Route::post('/estimate', [CalculatorController::class, 'estimate'])->middleware('throttle:60,1')->name('estimate');
    Route::post('/sessions', [CalculatorController::class, 'storeSession'])->middleware('throttle:30,1')->name('sessions.store');
    Route::get('/sessions/{session}', [CalculatorController::class, 'showSession'])->name('sessions.show');
    Route::put('/sessions/{session}', [CalculatorController::class, 'updateSession'])->middleware('throttle:60,1')->name('sessions.update');
    Route::post('/sessions/{session}/snapshot', [CalculatorController::class, 'storeSnapshot'])->middleware('throttle:20,1')->name('sessions.snapshot');
    Route::post('/sessions/{session}/request', [CalculatorController::class, 'storeRequest'])->middleware('throttle:10,1')->name('sessions.request');
    Route::get('/snapshots/{snapshot}', [CalculatorController::class, 'showSnapshot'])->name('snapshots.show');
    Route::get('/snapshots/{snapshot}/pdf', [CalculatorController::class, 'snapshotPdf'])->middleware('throttle:20,1')->name('snapshots.pdf');
});

Route::get('/business/register', fn () => archiView('pages.business-register'))->name('business.register');
Route::get('/business/onboarding/step-1', fn () => archiView('pages.business-onboarding-step1'))->name('business.onboarding.step1');
Route::get('/business/onboarding/step-2', fn () => archiView('pages.business-onboarding-step2'))->name('business.onboarding.step2');
Route::get('/business/onboarding/step-3', fn () => archiView('pages.business-onboarding-step3'))->name('business.onboarding.step3');
Route::get('/business/onboarding/step-4', fn () => archiView('pages.business-onboarding-step4'))->name('business.onboarding.step4');

Route::middleware(['auth', 'role:seller,admin'])->group(function () {
    // Cabinet pages
    Route::get('/business/profile', [BusinessProfileController::class, 'storefront'])->name('business.profile');

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
    Route::get('/business/profile/products', [BusinessProductController::class, 'index'])->name('business.profile.products');
    Route::get('/business/products/create', [BusinessProductController::class, 'create'])->name('business.products.create');
    Route::get('/business/products/{product}/edit', [BusinessProductController::class, 'edit'])->name('business.products.edit');
    Route::get('/business/inventory', [BusinessProductController::class, 'inventory'])->name('business.inventory');
    Route::get('/business/api/sub-categories/{subCategory}/form', [BusinessProductController::class, 'subCategoryForm'])->name('business.api.subcategory.form');
    // Laminate card: find an approved card by brand + manufacturer article to copy from.
    Route::get('/business/api/laminate/lookup', [BusinessProductController::class, 'laminateLookup'])
        ->middleware('throttle:30,1')
        ->name('business.api.laminate.lookup');
    Route::post('/business/products', [BusinessProductController::class, 'store'])->name('business.products.store');
    Route::put('/business/products/{product}', [BusinessProductController::class, 'update'])->name('business.products.update');
    Route::post('/business/products/{product}/toggle', [BusinessProductController::class, 'toggleVisibility'])->name('business.products.toggle');
    Route::post('/business/products/{product}/stock', [BusinessProductController::class, 'updateStock'])->name('business.products.stock');
    Route::delete('/business/products/{product}', [BusinessProductController::class, 'destroy'])->name('business.products.destroy');

    // Orders
    Route::get('/business/orders', [BusinessOrderController::class, 'index'])->name('business.orders');
    Route::get('/business/orders/{order}', [BusinessOrderController::class, 'show'])->name('business.orders.show');
    Route::post('/business/orders/{order}/status', [BusinessOrderController::class, 'updateStatus'])->name('business.orders.status');

    // Profile API
    Route::put('/business/profile/company', [BusinessProfileController::class, 'updateCompany'])->name('business.profile.company.update');
    Route::put('/business/profile/contact', [BusinessProfileController::class, 'updateContact'])->name('business.profile.contact.update');
    Route::put('/business/profile/notifications', [BusinessProfileController::class, 'updateNotifications'])->name('business.profile.notifications.update');
    Route::post('/business/profile/logo', [BusinessProfileController::class, 'updateLogo'])->name('business.profile.logo');
    Route::delete('/business/profile/logo', [BusinessProfileController::class, 'deleteLogo'])->name('business.profile.logo.delete');
    Route::post('/business/profile/cover', [BusinessProfileController::class, 'updateCover'])->name('business.profile.cover');
    Route::delete('/business/profile/cover', [BusinessProfileController::class, 'deleteCover'])->name('business.profile.cover.delete');

    // Showrooms API
    Route::post('/business/showrooms', [ShowroomController::class, 'store'])->name('business.showrooms.store');
    Route::put('/business/showrooms/{showroom}', [ShowroomController::class, 'update'])->name('business.showrooms.update');
    Route::delete('/business/showrooms/{showroom}', [ShowroomController::class, 'destroy'])->name('business.showrooms.destroy');
});

// Auth routes
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Password reset — throttled, and deliberately neutral about whether an address exists.
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('password.email');
Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:6,1'])
    ->name('password.update');

// API-style routes
Route::get('/api/search', [SearchController::class, 'autocomplete'])->name('api.search');
Route::post('/api/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('api.newsletter.subscribe');
Route::get('/api/cart/count', [CartController::class, 'count'])->name('api.cart.count');
// Public on purpose: guests get an empty list, so the calculator can always ask.
Route::get('/api/cart/items', [CartController::class, 'items'])->name('api.cart.items');

// Placing an order requires an account: the cart opens the login modal for guests, and
// this guard is what actually enforces it.
Route::post('/api/orders', [OrderController::class, 'store'])->middleware('auth')->name('api.orders.store');
// Still reachable without auth so a buyer following the link in a new tab is not bounced;
// OrderController::success authorises the viewer itself.
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
    // localStorage rows only know the product id, so removals from /cart use this.
    Route::delete('/api/cart/product/{productId}', [CartController::class, 'destroyByProduct'])
        ->whereNumber('productId')->name('api.cart.destroyProduct');

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
