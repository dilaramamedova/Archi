<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
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

    public function index(Request $request)
    {
        $query = SpecialistProfile::query()
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with(['user', 'specialty', 'approvedPortfolioItems'])
            ->withCount(['reviews as approved_reviews_count' => fn ($q) => $q->where('status', 'approved')]);

        if ($request->filled('city')) {
            $city = $request->input('city');
            $query->where('city', $city);
        }

        // Filter by the normalized specialist specialty relation.
        if ($request->filled('spec')) {
            $query->where('specialist_specialty_id', (int) $request->input('spec'));
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

        // Gather filter options from all active specialist profiles
        $cities = SpecialistProfile::query()
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->whereNotNull('city')
            ->distinct()
            ->pluck('city');

        $specialties = SpecialistSpecialty::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('pages.specialists', compact('specialists', 'cities', 'specialties'));
    }
}
