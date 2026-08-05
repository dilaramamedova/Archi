<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\Product;
use App\Models\SpecialistProfile;

class ProductController extends Controller
{
    public function show(string $slug)
    {
        $product = Product::where('slug', $slug)
            ->visible()
            ->approved()
            ->with(['images', 'category', 'user.sellerProfile', 'badges'])
            ->firstOrFail();

        // Eager load approved reviews with their users
        $product->load(['reviews' => fn ($q) => $q->where('status', 'approved')->with('user')->latest()]);

        $product->increment('views_count');

        // Frequently bought together products
        $fbtIds = $product->frequently_bought_together ?? [];
        $fbtProducts = collect();
        if (!empty($fbtIds)) {
            $fbtProducts = Product::whereIn('id', $fbtIds)
                ->visible()
                ->approved()
                ->with('images')
                ->get();
        }

        $related = Product::visible()->approved()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('images')
            ->take(4)
            ->get();

        $specialists = SpecialistProfile::where('is_featured', true)
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->with('user')
            ->take(4)
            ->get();

        return view('pages.product', compact('product', 'related', 'specialists', 'fbtProducts'));
    }
}
