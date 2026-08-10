<?php

namespace App\Http\Controllers\Cabinet;

use App\Enums\City;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class BusinessProfileController extends Controller
{
    /**
     * Seller storefront (what a buyer sees) rendered with the seller's real data.
     * Currently reachable only by the owner (and admins) — the public {seller}
     * route will reuse the same view when messaging/follow ship.
     */
    public function storefront(Request $request)
    {
        app()->setLocale(session('locale', config('app.locale')));

        $user = $request->user();
        $profile = $user->sellerProfile?->load('showrooms');
        $showrooms = $profile
            ? $profile->showrooms->where('status', 'active')->values()
            : collect();

        $publicProducts = $user->products()->visible()->approved();

        $productsCount = (clone $publicProducts)->count();
        $products = (clone $publicProducts)->with(['images', 'category'])->latest()->take(6)->get();

        // Average of approved review ratings across all the seller's products.
        $ratingStats = \App\Models\Review::where('reviewable_type', \App\Models\Product::class)
            ->whereIn('reviewable_id', $user->products()->pluck('id'))
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg')
            ->first();

        return view('pages.business-profile', [
            'user' => $user,
            'profile' => $profile,
            'showrooms' => $showrooms,
            'products' => $products,
            'productsCount' => $productsCount,
            'reviewsCount' => (int) ($ratingStats->cnt ?? 0),
            'avgRating' => $ratingStats->cnt ? round((float) $ratingStats->avg, 1) : null,
            'isOwner' => true,
        ]);
    }

    public function updateCompany(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'brand_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:20'],
            'city' => ['nullable', Rule::in(City::acceptedValues())],
            'address' => ['nullable', 'string', 'max:255'],
            'about' => ['nullable', 'string', 'max:2000'],
        ]);

        // Store the canonical Azerbaijani label whatever form was submitted.
        $validated['city'] = City::canonical($validated['city'] ?? null);

        $request->user()->sellerProfile()->updateOrCreate([], $validated);

        return response()->json(['success' => true, 'message' => t('business-cabinet.saved')]);
    }

    public function updateContact(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_role' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'telegram' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'work_hours' => ['nullable', 'string', 'max:100'],
            'instagram' => ['nullable', 'string', 'max:255'],
            'linkedin' => ['nullable', 'string', 'max:255'],
            'facebook' => ['nullable', 'string', 'max:255'],
            'languages' => ['nullable', 'array', 'max:10'],
            'languages.*' => ['string', 'max:10'],
        ]);

        $request->user()->sellerProfile()->updateOrCreate([], $validated);

        return response()->json(['success' => true, 'message' => t('business-cabinet.saved')]);
    }

    public function updateNotifications(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notification_settings' => ['required', 'array'],
            'notification_settings.orders' => ['boolean'],
            'notification_settings.reviews' => ['boolean'],
            'notification_settings.stock' => ['boolean'],
            'notification_settings.report' => ['boolean'],
            'notification_settings.channels' => ['array'],
            'notification_settings.channels.*' => ['string', 'in:email,sms,push,telegram'],
        ]);

        $request->user()->sellerProfile()->updateOrCreate([], $validated);

        return response()->json(['success' => true, 'message' => t('business-cabinet.saved')]);
    }

    public function updateLogo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $profile = $request->user()->sellerProfile()->firstOrCreate();
        $this->deleteUpload($profile->logo_path);
        $path = $validated['logo']->store('sellers/logos', 'public');
        $profile->update(['logo_path' => $path]);

        return response()->json(['success' => true, 'url' => storage_url($path)]);
    }

    public function deleteLogo(Request $request): JsonResponse
    {
        $profile = $request->user()->sellerProfile;

        if ($profile) {
            $this->deleteUpload($profile->logo_path);
            $profile->update(['logo_path' => null]);
        }

        return response()->json(['success' => true]);
    }

    public function updateCover(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cover' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:8192'],
        ]);

        $profile = $request->user()->sellerProfile()->firstOrCreate();
        $this->deleteUpload($profile->cover_path);
        $path = $validated['cover']->store('sellers/covers', 'public');
        $profile->update(['cover_path' => $path]);

        return response()->json(['success' => true, 'url' => storage_url($path)]);
    }

    public function deleteCover(Request $request): JsonResponse
    {
        $profile = $request->user()->sellerProfile;

        if ($profile) {
            $this->deleteUpload($profile->cover_path);
            $profile->update(['cover_path' => null]);
        }

        return response()->json(['success' => true]);
    }

    private function deleteUpload(?string $path): void
    {
        if ($path && ! str_starts_with($path, 'assets/') && ! str_starts_with($path, '/assets/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
