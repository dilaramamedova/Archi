<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

if (! function_exists('archiView')) {
    // setLocale must run inside the request (here), not at file-load time:
    // the session is not available while the route file is being parsed.
    function archiView(string $view)
    {
        app()->setLocale(session('locale', config('app.locale')));

        return view($view);
    }
}

Route::get('/', fn () => archiView('pages.home'))->name('home');
Route::get('/catalog', fn () => archiView('pages.catalog'))->name('catalog');
Route::get('/product', fn () => archiView('pages.product'))->name('product');
Route::get('/search', fn () => archiView('pages.search'))->name('search');
Route::get('/specialists', fn () => archiView('pages.specialists'))->name('specialists');
Route::get('/specialist', fn () => archiView('pages.specialist'))->name('specialist');
Route::get('/blog', fn () => archiView('pages.blog'))->name('blog');
Route::get('/blog/article', fn () => archiView('pages.blog-article'))->name('blog.article');
Route::get('/about', fn () => archiView('pages.about'))->name('about');
Route::get('/sell', fn () => archiView('pages.sell'))->name('sell');
Route::get('/login', fn () => archiView('pages.login'))->name('login');
Route::get('/register', fn () => archiView('pages.register'))->name('register');
Route::get('/cart', fn () => archiView('pages.cart'))->name('cart');

// Specialist owner mode + onboarding + cabinet.
Route::get('/specialist/owner', fn () => archiView('pages.specialist-owner'))->name('specialist.owner');
Route::get('/specialist/onboarding', fn () => archiView('pages.specialist-onboarding'))->name('specialist.onboarding');

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

    Route::get('/specialist/cabinet/portfolio', fn () => archiView('pages.specialist-cabinet-portfolio'))->name('specialist.cabinet.portfolio');
    Route::get('/specialist/cabinet/services', fn () => archiView('pages.specialist-cabinet-services'))->name('specialist.cabinet.services');
    Route::get('/specialist/cabinet/schedule', fn () => archiView('pages.specialist-cabinet-schedule'))->name('specialist.cabinet.schedule');
    Route::get('/specialist/cabinet/reviews', fn () => archiView('pages.specialist-cabinet-reviews'))->name('specialist.cabinet.reviews');
    Route::get('/specialist/cabinet/notifications', fn () => archiView('pages.specialist-cabinet-notifications'))->name('specialist.cabinet.notifications');
});

Route::get('/calculator', fn () => archiView('pages.calculator'))->name('calculator');
Route::get('/calculator/detailed', fn () => archiView('pages.calculator-detailed'))->name('calculator.detailed');

Route::get('/business/register', fn () => archiView('pages.business-register'))->name('business.register');
Route::get('/business/onboarding/step-1', fn () => archiView('pages.business-onboarding-step1'))->name('business.onboarding.step1');
Route::get('/business/onboarding/step-2', fn () => archiView('pages.business-onboarding-step2'))->name('business.onboarding.step2');
Route::get('/business/onboarding/step-3', fn () => archiView('pages.business-onboarding-step3'))->name('business.onboarding.step3');
Route::get('/business/onboarding/step-4', fn () => archiView('pages.business-onboarding-step4'))->name('business.onboarding.step4');

Route::get('/business/profile', fn () => archiView('pages.business-profile'))->name('business.profile');
Route::get('/business/profile/company', fn () => archiView('pages.business-profile-company'))->name('business.profile.company');
Route::get('/business/profile/contact', fn () => archiView('pages.business-profile-contact'))->name('business.profile.contact');
Route::get('/business/profile/products', fn () => archiView('pages.business-profile-products'))->name('business.profile.products');
Route::get('/business/profile/showrooms', fn () => archiView('pages.business-profile-showrooms'))->name('business.profile.showrooms');
Route::get('/business/profile/notifications', fn () => archiView('pages.business-profile-notifications'))->name('business.profile.notifications');
Route::get('/business/profile/security', fn () => archiView('pages.business-profile-security'))->name('business.profile.security');

// Auth routes
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Cabinet routes (authenticated)
Route::middleware('auth')->group(function () {
    // Security (shared by all user types)
    Route::post('/cabinet/password', [\App\Http\Controllers\Cabinet\SecurityController::class, 'changePassword'])->name('cabinet.password');
    Route::get('/cabinet/sessions', [\App\Http\Controllers\Cabinet\SecurityController::class, 'sessions'])->name('cabinet.sessions');
    Route::delete('/cabinet/sessions', [\App\Http\Controllers\Cabinet\SecurityController::class, 'destroySession'])->name('cabinet.sessions.destroy');
    Route::post('/cabinet/deactivate', [\App\Http\Controllers\Cabinet\SecurityController::class, 'deactivateAccount'])->name('cabinet.deactivate');

    // Specialist profile
    Route::put('/specialist/cabinet', [\App\Http\Controllers\Cabinet\SpecialistProfileController::class, 'update'])->name('specialist.cabinet.update');
});

// Language switch: stores the locale in the session and returns the user to the same page.
Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['az', 'ru', 'en'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('lang');
