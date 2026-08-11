<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;

/**
 * Public store page of a seller — the buyer-facing counterpart of the private
 * /business/profile cabinet storefront. Same data source (SellerProfile +
 * the seller's published products), but only active sellers get a page and
 * only public-appropriate data is passed to the view.
 */
class StoreController extends Controller
{
    public function show(User $seller)
    {
        app()->setLocale(session('locale', config('app.locale')));

        // Only an active seller account has a public store; everyone else 404s
        // (buyers, masters, admins, pending/rejected/blocked sellers).
        abort_unless($seller->isSeller() && $seller->isActive(), 404);

        $profile = $seller->sellerProfile?->load('showrooms');
        $showrooms = $profile
            ? $profile->showrooms->where('status', 'active')->values()
            : collect();

        // Published products only — the same visible()+approved() pair the catalog uses.
        $publicProducts = $seller->products()->visible()->approved();

        $productsCount = (clone $publicProducts)->count();
        $products = (clone $publicProducts)
            ->with(['images', 'category', 'badges'])
            ->latest()
            ->paginate(12);

        // Average of approved review ratings across the seller's public products.
        $ratingStats = Review::where('reviewable_type', Product::class)
            ->whereIn('reviewable_id', (clone $publicProducts)->pluck('products.id'))
            ->where('status', 'approved')
            ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg')
            ->first();

        return view('pages.store', [
            'seller' => $seller,
            'profile' => $profile,
            'showrooms' => $showrooms,
            'products' => $products,
            'productsCount' => $productsCount,
            'reviewsCount' => (int) ($ratingStats->cnt ?? 0),
            'avgRating' => $ratingStats->cnt ? round((float) $ratingStats->avg, 1) : null,
        ]);
    }
}
