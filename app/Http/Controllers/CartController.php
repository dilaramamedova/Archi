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
                ->get()
                // A product the seller has hidden, or one an admin has not approved,
                // used to keep rendering here with a price on it — and then block the
                // whole order at checkout with "bu məhsul hazırda satışda deyil". The
                // items() endpoint already filtered them; the page did not.
                ->filter(fn (CartItem $item) => $item->product
                    && $item->product->is_visible
                    && $item->product->is_approved)
                ->values();
        } else {
            $items = collect();
        }

        return view('pages.cart', [
            'items' => $items,
            // One source for the delivery rule — see OrderController. The cart used to
            // keep its own copy in JavaScript.
            'deliveryRule' => [
                'freeFrom' => OrderController::DELIVERY_FREE_FROM,
                'fee' => OrderController::DELIVERY_FEE,
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1|max:'.OrderController::MAX_LINE_QTY,
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

        $request->validate(['quantity' => 'required|integer|min:1|max:'.OrderController::MAX_LINE_QTY]);
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
            ->with('product.images', 'product.category')
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
                // The cart page caps its +/- stepper with this; the calculator ignores it.
                'stock' => (int) $item->product->stock,
                'cat' => (string) ($item->product->category?->name ?? ''),
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
