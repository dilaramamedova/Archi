<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        if (auth()->check()) {
            $items = auth()->user()->cartItems()
                ->with('product.images', 'product.category')
                ->get();
        } else {
            $items = collect();
        }

        return view('pages.cart', compact('items'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:99',
        ]);

        $item = CartItem::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $request->product_id],
            ['quantity' => $request->input('quantity', 1)]
        );

        return response()->json([
            'success' => true,
            'count' => auth()->user()->cartItems()->count(),
        ]);
    }

    public function update(Request $request, CartItem $cartItem): JsonResponse
    {
        if ($cartItem->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate(['quantity' => 'required|integer|min:1|max:99']);
        $cartItem->update(['quantity' => $request->quantity]);

        return response()->json(['success' => true]);
    }

    public function destroy(CartItem $cartItem): JsonResponse
    {
        if ($cartItem->user_id !== auth()->id()) {
            abort(403);
        }

        $cartItem->delete();

        return response()->json([
            'success' => true,
            'count' => auth()->user()->cartItems()->count(),
        ]);
    }

    public function count(): JsonResponse
    {
        $count = auth()->check() ? auth()->user()->cartItems()->count() : 0;
        return response()->json(['count' => $count]);
    }
}
