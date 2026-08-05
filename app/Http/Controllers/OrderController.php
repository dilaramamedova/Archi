<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.qty' => 'required|integer|min:1',
            'promo_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000',
        ];

        // Guests must provide contact info
        if (! $user) {
            $rules['delivery_name'] = 'required|string|max:255';
            $rules['delivery_phone'] = 'required|string|max:50';
            $rules['delivery_address'] = 'required|string|max:500';
        } else {
            $rules['delivery_name'] = 'nullable|string|max:255';
            $rules['delivery_phone'] = 'nullable|string|max:50';
            $rules['delivery_address'] = 'nullable|string|max:500';
        }

        $validated = $request->validate($rules);

        $items = collect($validated['items']);

        // Calculate totals
        $subtotal = $items->sum(fn ($item) => $item['price'] * $item['qty']);

        // Promo discount (same logic as frontend)
        $promos = [
            'ARCHI15' => ['type' => 'pct', 'val' => 0.15],
            'YENI10' => ['type' => 'pct', 'val' => 0.10],
            'QIS20' => ['type' => 'pct', 'val' => 0.20, 'min' => 200],
        ];

        $discount = 0;
        $promoCode = strtoupper(trim($validated['promo_code'] ?? ''));
        if ($promoCode && isset($promos[$promoCode])) {
            $promo = $promos[$promoCode];
            if (! isset($promo['min']) || $subtotal >= $promo['min']) {
                $discount = $promo['type'] === 'pct'
                    ? round($subtotal * $promo['val'], 2)
                    : $promo['val'];
            }
        }

        $afterDiscount = $subtotal - $discount;
        $deliveryFee = $afterDiscount >= 100 ? 0 : 10;
        $total = $afterDiscount + $deliveryFee;

        $order = DB::transaction(function () use ($user, $validated, $items, $subtotal, $discount, $deliveryFee, $total, $promoCode) {
            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user?->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'delivery_fee' => $deliveryFee,
                'total' => $total,
                'promo_code' => $promoCode ?: null,
                'delivery_name' => $validated['delivery_name'] ?? $user?->name ?? '',
                'delivery_phone' => $validated['delivery_phone'] ?? $user?->phone ?? '',
                'delivery_address' => $validated['delivery_address'] ?? '',
                'delivery_city' => '',
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                // Try to find matching product by name for snapshot
                $product = Product::where('name->az', $item['name'])
                    ->orWhere('name->ru', $item['name'])
                    ->orWhere('name->en', $item['name'])
                    ->first();

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product?->id,
                    'product_snapshot' => [
                        'name' => $item['name'],
                        'brand' => $item['brand'] ?? '',
                        'cat' => $item['cat'] ?? '',
                        'price' => $item['price'],
                    ],
                    'quantity' => $item['qty'],
                    'unit_price' => $item['price'],
                    'total' => round($item['price'] * $item['qty'], 2),
                ]);
            }

            // Clear server-side cart for logged-in users
            if ($user) {
                $user->cartItems()->delete();
            }

            return $order;
        });

        return response()->json([
            'success' => true,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'redirect' => route('order.success', ['order' => $order->order_number]),
        ]);
    }

    public function success(Request $request)
    {
        app()->setLocale(session('locale', config('app.locale')));

        $orderNumber = $request->route('order');
        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        return view('pages.order-success', compact('order'));
    }
}
