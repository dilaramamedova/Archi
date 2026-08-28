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

    /**
     * Remove by product id. The storefront cart lives in localStorage, whose rows
     * carry only the product id — the browser has no way to know the CartItem id
     * when it deletes a row, so this is the endpoint cart.js actually calls.
     */
    public function destroyByProduct(int $productId): JsonResponse
    {
        auth()->user()->cartItems()->where('product_id', $productId)->delete();

        return response()->json([
            'success' => true,
            'count' => auth()->user()->cartItems()->count(),
        ]);
    }

    /**
     * The account cart as JSON, in the shape the detailed calculator's cart step
     * uses. Lets the calculator re-read the server cart without a page reload —
     * e.g. after the visitor deletes a product on /cart and comes back through
     * the browser's back/forward cache.
     */
    public function items(): JsonResponse
    {
        if (! auth()->check()) {
            return response()->json(['items' => []]);
        }

        $items = auth()->user()->cartItems()
            ->with('product.images')
            ->get()
            ->filter(fn (CartItem $item) => $item->product
                && $item->product->is_visible
                && $item->product->is_approved)
            ->map(fn (CartItem $item) => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'price' => (float) $item->product->price,
                'unit' => $item->product->unit,
                'image' => $item->product->mainImageUrl,
                'packageSize' => $item->product->package_size ? (float) $item->product->package_size : null,
                'quantity' => max(1, (int) $item->quantity),
            ])
            ->values();

        return response()->json(['items' => $items]);
    }

    public function count(): JsonResponse
    {
        $count = auth()->check() ? auth()->user()->cartItems()->count() : 0;
        return response()->json(['count' => $count]);
    }
}
