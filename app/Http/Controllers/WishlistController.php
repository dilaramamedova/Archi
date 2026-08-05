<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index()
    {
        $items = auth()->user()->wishlists()
            ->with('product.images', 'product.category')
            ->latest()
            ->get();

        return view('pages.wishlist', compact('items'));
    }

    public function toggle(Request $request): JsonResponse
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $existing = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $request->product_id)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['added' => false, 'count' => auth()->user()->wishlists()->count()]);
        }

        Wishlist::create(['user_id' => auth()->id(), 'product_id' => $request->product_id]);
        return response()->json(['added' => true, 'count' => auth()->user()->wishlists()->count()]);
    }
}
