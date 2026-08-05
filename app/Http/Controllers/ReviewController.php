<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
            'reviewable_type' => ['required', Rule::in(['product', 'specialist'])],
            'reviewable_id' => 'required|integer',
        ]);

        // Map short type names to model classes
        $typeMap = [
            'product' => \App\Models\Product::class,
            'specialist' => \App\Models\SpecialistProfile::class,
        ];

        $morphType = $typeMap[$validated['reviewable_type']];

        // Verify the reviewable entity exists
        $morphType::findOrFail($validated['reviewable_id']);

        // Check user hasn't already reviewed this item
        $existing = Review::where('user_id', $request->user()->id)
            ->where('reviewable_type', $morphType)
            ->where('reviewable_id', $validated['reviewable_id'])
            ->exists();

        if ($existing) {
            return response()->json([
                'message' => 'Bu məhsul üçün artıq rəy yazmısınız.',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'reviewable_type' => $morphType,
            'reviewable_id' => $validated['reviewable_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Rəyiniz göndərildi və təsdiq gözləyir.',
            'review' => $review,
        ], 201);
    }

    public function helpful(Request $request, Review $review)
    {
        $user = $request->user();

        $toggled = $review->helpfulVoters()->toggle($user->id);

        if (count($toggled['attached']) > 0) {
            $review->increment('helpful_count');
        } else {
            $review->decrement('helpful_count');
        }

        return response()->json([
            'helpful_count' => $review->fresh()->helpful_count,
            'voted' => count($toggled['attached']) > 0,
        ]);
    }
}
