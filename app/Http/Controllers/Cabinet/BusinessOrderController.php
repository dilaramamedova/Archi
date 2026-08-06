<?php

namespace App\Http\Controllers\Cabinet;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BusinessOrderController extends Controller
{
    /** Order statuses a seller can move an order through, in flow order. */
    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function index(Request $request)
    {
        app()->setLocale(session('locale', config('app.locale')));

        $sellerId = $request->user()->id;

        $base = Order::whereHas('items.product', fn ($q) => $q->where('user_id', $sellerId));

        $query = (clone $base)->with([
            'user',
            'items' => fn ($q) => $q->whereHas('product', fn ($p) => $p->where('user_id', $sellerId)),
            'items.product.images',
        ]);

        $orders = $query->latest()->paginate(10);

        return view('pages.business-orders', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorizeOrder($request, $order);
        app()->setLocale(session('locale', config('app.locale')));

        $sellerId = $request->user()->id;

        $order->load([
            'user',
            'items' => fn ($q) => $q->whereHas('product', fn ($p) => $p->where('user_id', $sellerId)),
            'items.product.images',
        ]);

        // Seller-side earnings: own items' subtotal minus platform commission.
        $itemsSubtotal = $order->items->sum('total');
        $commissionRate = 0.08;
        $commission = round($itemsSubtotal * $commissionRate, 2);

        // The platform absorbs cart-level discounts; show proportional share for context.
        $discountShare = $order->subtotal > 0
            ? round((float) $order->discount * ($itemsSubtotal / (float) $order->subtotal), 2)
            : 0.0;

        $payout = round($itemsSubtotal - $commission, 2);

        return view('pages.business-order-detail', compact('order', 'itemsSubtotal', 'commission', 'discountShare', 'payout'));
    }

    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($request, $order);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', self::STATUSES)],
        ]);

        $current = array_search($order->status, self::STATUSES, true);
        $target = array_search($validated['status'], self::STATUSES, true);

        // Forward-only flow (pending→processing→shipped→delivered); cancel allowed
        // any time before delivery. No un-cancelling, no rewinding.
        $isCancel = $validated['status'] === 'cancelled';
        $allowed = $order->status !== 'cancelled' && (
            ($isCancel && $order->status !== 'delivered')
            || ($current !== false && $target !== false && $target === $current + 1 && ! $isCancel)
        );

        if (! $allowed) {
            return response()->json([
                'success' => false,
                'message' => __('business-cabinet.invalid_status_transition'),
            ], 422);
        }

        $order->update(['status' => $validated['status']]);

        return response()->json(['success' => true, 'status' => $order->status]);
    }

    private function authorizeOrder(Request $request, Order $order): void
    {
        abort_unless(
            $order->items()->whereHas('product', fn ($q) => $q->where('user_id', $request->user()->id))->exists(),
            403
        );
    }
}
