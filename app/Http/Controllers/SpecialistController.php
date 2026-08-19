<?php

namespace App\Http\Controllers;

use App\Enums\City;
use App\Enums\SpecialistCategory;
use App\Enums\UserStatus;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SpecialistController extends Controller
{
    public function show(SpecialistProfile $specialist)
    {
        $specialist->load(['user', 'specialty', 'services' => fn ($q) => $q->where('is_active', true), 'approvedPortfolioItems']);

        // Abort if the owning user account is not active.
        if (! $specialist->user || $specialist->user->status !== UserStatus::Active) {
            abort(404);
        }

        // Load approved reviews with their authors
        $reviews = $specialist->reviews()
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->get();

        $avgRating = $reviews->avg('rating') ?? 0;
        $reviewsCount = $reviews->count();

        return view('pages.specialist', compact('specialist', 'reviews', 'avgRating', 'reviewsCount'));
    }

    /**
     * Reveal a specialist's phone number. The number is never rendered into the
     * public profile HTML; it is fetched from here so only signed-in users
     * (and only on an explicit click) can read it.
     */
    public function phone(SpecialistProfile $specialist): JsonResponse
    {
        if (! $specialist->user || $specialist->user->status !== UserStatus::Active) {
            abort(404);
        }

        $phone = $specialist->phone ?: ($specialist->whatsapp ?: $specialist->user->phone);

        return response()->json([
            'phone' => $phone ?: null,
            // Digits + leading "+" only, for the tel: href.
            'tel' => $phone ? preg_replace('/[^+\d]/', '', $phone) : null,
        ]);
    }

    public function index(Request $request)
    {
        $query = SpecialistProfile::query()
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with(['user', 'specialty', 'approvedPortfolioItems']);

        // Free-text search over craft/skills/specialty/user name.
        if ($request->filled('q')) {
            $query = SearchService::buildSpecialistQuery($query, (string) $request->input('q'));
        }

        // Accept either the slug ("baku") or the label ("Bakı") — rows store the label.
        if ($request->filled('city') && ($city = City::canonical($request->input('city')))) {
            $query->where('city', $city);
        }

        // Filter by the normalized specialist specialty relation.
        if ($request->filled('spec')) {
            $query->where('specialist_specialty_id', (int) $request->input('spec'));
        }

        // The header's "Mütəxəssislər" mega panel links here as ?type=architect etc.
        // Nothing used to read that parameter, so all four cards landed on the full
        // directory and the choice the visitor had just made was thrown away.
        // input() may hand back an array (?type[]=x) or null, and casting either to
        // string is a fatal — the catalog already tolerates junk in ?category=, so this
        // must not be the one filter that 500s on a malformed URL.
        $type = $request->input('type');
        $category = is_string($type) ? SpecialistCategory::tryFrom($type) : null;

        if ($category !== null) {
            $query->whereHas('specialty', fn ($q) => $q->category($category));
        }

        // Filter by featured/top
        if ($request->boolean('featured', false)) {
            $query->where('is_featured', true);
        }

        // Filter by minimum experience years
        if ($request->filled('min_years')) {
            $query->where('experience_years', '>=', (int) $request->input('min_years'));
        }

        // Filter by max experience years
        if ($request->filled('max_years')) {
            $query->where('experience_years', '<=', (int) $request->input('max_years'));
        }

        // Filter by availability (not on vacation)
        if ($request->boolean('free', false)) {
            $query->where('is_on_vacation', false);
        }

        if ($request->boolean('verified', false)) {
            $query->where('is_featured', true);
        }

        // Sort — keys match the template sort menu data-sort values
        $sort = $request->input('sort', 'rating');
        $query->orderBy(match ($sort) {
            'exp' => 'experience_years',
            'experience' => 'experience_years',
            'newest' => 'created_at',
            'cheap' => 'created_at', // no price column yet — fallback
            'rating' => 'experience_years', // no rating column yet — fallback to experience
            'reviews' => 'experience_years', // no review count column yet — fallback
            'projects' => 'experience_years', // no project count column yet — fallback
            default => 'experience_years',
        }, 'desc');

        // Featured specialists come first
        $query->orderByDesc('is_featured');

        $specialists = $query->paginate(20)->withQueryString();

        // The canonical list, not a DISTINCT over the column — the distinct is what let a
        // stale raw slug show up as its own filter row next to the proper city.
        $cities = collect(City::labels());

        $specialties = SpecialistSpecialty::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Passed through so the page can show which mega-panel card the visitor
        // arrived from. Without it a category with no active members renders as a
        // plain "no specialists" page and looks broken rather than empty.
        $activeCategory = $category;

        return view('pages.specialists', compact('specialists', 'cities', 'specialties', 'activeCategory'));
    }
}
