<?php

namespace App\Http\Controllers;

use App\Enums\City;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private const DELIVERY_FREE_FROM = 100;

    private const DELIVERY_FEE = 10;

    /** Upper bound for a single order line — a sanity guard, stock is checked separately. */
    private const MAX_LINE_QTY = 999;

    private const MAX_LINES = 50;

    /** Orders accepted per minute, per user (or per IP for guests). */
    private const MAX_ORDERS_PER_MINUTE = 10;

    /** Guest orders placed in this session; used for the order-success ownership check. */
    private const SESSION_ORDERS_KEY = 'placed_order_ids';

    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // routes/web.php has no throttle middleware on this endpoint, so the limiter lives here.
        $limiterKey = 'orders:'.($user?->id ?? $request->ip());
        if (RateLimiter::tooManyAttempts($limiterKey, self::MAX_ORDERS_PER_MINUTE)) {
            return response()->json([
                'success' => false,
                'message' => self::text('cart.errors.too_many', [], 'Too many orders. Please try again in a minute.'),
            ], 429);
        }
        RateLimiter::hit($limiterKey, 60);

        $acceptedCities = self::acceptedCityValues();

        $rules = [
            'items' => 'required|array|min:1|max:'.self::MAX_LINES,
            'items.*.product_id' => 'nullable|integer|min:1',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.qty' => 'required|integer|min:1|max:'.self::MAX_LINE_QTY,
            'delivery_city' => ['required', 'string', 'max:100'],
            'notes' => 'nullable|string|max:1000',
        ];

        if ($acceptedCities) {
            $rules['delivery_city'][] = Rule::in($acceptedCities);
        }

        // The route is auth-gated, so there is always a user. Contact details are still
        // mandatory: the courier needs them, and an order with no address is undeliverable.
        // The checkout modal pre-fills name/phone from the account and requires all three.
        $rules['delivery_name'] = 'required|string|max:255';
        $rules['delivery_phone'] = 'required|string|max:50';
        $rules['delivery_address'] = 'required|string|max:500';

        $validated = $request->validate($rules);

        $order = DB::transaction(function () use ($user, $validated) {
            // Prices, stock and the product snapshot all come from the database —
            // nothing money-related is ever taken from the request body.
            $lines = $this->resolveLines($validated['items']);

            $subtotal = round(array_sum(array_column($lines, 'total')), 2);
            $deliveryFee = $subtotal >= self::DELIVERY_FREE_FROM ? 0 : self::DELIVERY_FEE;

            $order = Order::create([
                'order_number' => Order::generateOrderNumber(),
                'user_id' => $user?->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => 0,
                'delivery_fee' => $deliveryFee,
                'total' => round($subtotal + $deliveryFee, 2),
                'promo_code' => null,
                'delivery_name' => $validated['delivery_name'] ?? $user?->name ?? '',
                'delivery_phone' => $validated['delivery_phone'] ?? $user?->phone ?? '',
                'delivery_address' => $validated['delivery_address'] ?? '',
                'delivery_city' => self::canonicalCity($validated['delivery_city']),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_snapshot' => [
                        'id' => $product->id,
                        'name' => (string) $product->name,
                        'sku' => (string) ($product->sku ?? ''),
                        'brand' => (string) ($product->brand?->name ?? ''),
                        'cat' => (string) ($product->category?->name ?? ''),
                        'unit' => (string) ($product->unit ?? 'ədəd'),
                        'price' => $line['unit_price'],
                        'seller' => [
                            'id' => $product->user?->id,
                            'name' => (string) ($product->user?->name ?? ''),
                            'email' => (string) ($product->user?->email ?? ''),
                            'phone' => (string) ($product->user?->phone ?? ''),
                        ],
                    ],
                    'quantity' => $line['qty'],
                    'unit_price' => $line['unit_price'],
                    'total' => $line['total'],
                ]);

                $product->applySale($line['qty']);
            }

            // Clear server-side cart for logged-in users
            if ($user) {
                $user->cartItems()->delete();
            }

            return $order;
        });

        // Lets a guest read their own success page without exposing it to everyone.
        $request->session()->push(self::SESSION_ORDERS_KEY, $order->id);

        $this->notifySellers($order);

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

        if (! $this->canViewOrder($request, $order)) {
            // 404 rather than 403 so the endpoint does not confirm the number exists.
            abort(404);
        }

        return view('pages.order-success', compact('order'));
    }

    /**
     * One notification per seller, carrying only that seller's lines.
     * Runs after the transaction commits and never breaks a placed order.
     */
    private function notifySellers(Order $order): void
    {
        try {
            $items = $order->items()->with('product')->get();

            $items->groupBy(fn (OrderItem $item) => $item->product?->user_id)
                ->filter(fn ($sellerItems, $sellerId) => (bool) $sellerId)
                ->each(function ($sellerItems, $sellerId) use ($order) {
                    $seller = User::find($sellerId);
                    $seller?->notify(new NewOrderNotification($order, $sellerItems));
                });
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * The buyer for a user order, or the session that placed a guest order.
     */
    private function canViewOrder(Request $request, Order $order): bool
    {
        if ($order->user_id && $order->user_id === Auth::id()) {
            return true;
        }

        return in_array($order->id, (array) $request->session()->get(self::SESSION_ORDERS_KEY, []), true);
    }

    /**
     * Turns the posted cart into priced lines backed by locked product rows.
     * Duplicate lines for the same product are merged so the stock check sees the real total.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{product: Product, qty: int, unit_price: float, total: float}>
     *
     * @throws ValidationException
     */
    private function resolveLines(array $items): array
    {
        $lines = [];

        foreach ($items as $index => $item) {
            $product = $this->resolveProduct($item);

            if (! $product || ! $product->isOrderable()) {
                throw ValidationException::withMessages([
                    "items.$index.name" => self::text(
                        'cart.errors.unavailable',
                        ['name' => (string) ($item['name'] ?? '')],
                        'This product is no longer available.'
                    ),
                ]);
            }

            $key = $product->id;
            $qty = (int) $item['qty'];

            if (isset($lines[$key])) {
                $lines[$key]['qty'] += $qty;
                $lines[$key]['index'] = $index;
            } else {
                $lines[$key] = [
                    'product' => $product,
                    'qty' => $qty,
                    'unit_price' => (float) $product->price,
                    'index' => $index,
                ];
            }
        }

        foreach ($lines as $key => $line) {
            /** @var Product $product */
            $product = $line['product'];
            $index = $line['index'];
            $qty = $line['qty'];
            $minOrder = max(1, (int) $product->min_order);
            $stock = (int) $product->stock;

            if ($qty < $minOrder) {
                throw ValidationException::withMessages([
                    "items.$index.qty" => self::text(
                        'cart.errors.min_order',
                        ['name' => (string) $product->name, 'min' => $minOrder],
                        "\"{$product->name}\": the minimum order is {$minOrder}."
                    ),
                ]);
            }

            if ($qty > $stock) {
                throw ValidationException::withMessages([
                    "items.$index.qty" => self::text(
                        'cart.errors.out_of_stock',
                        ['name' => (string) $product->name, 'available' => $stock],
                        "\"{$product->name}\": only {$stock} left in stock."
                    ),
                ]);
            }

            $lines[$key]['total'] = round($line['unit_price'] * $qty, 2);
        }

        return array_values($lines);
    }

    /**
     * product_id is the canonical reference; the name lookup only exists for carts
     * that were written to localStorage before the id was stored.
     */
    private function resolveProduct(array $item): ?Product
    {
        $query = Product::query()
            ->with(['brand', 'category', 'user'])
            ->lockForUpdate();

        if (! empty($item['product_id'])) {
            return $query->find((int) $item['product_id']);
        }

        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            return null;
        }

        return $query->where(function ($q) use ($name) {
            $q->where('name->az', $name)
                ->orWhere('name->ru', $name)
                ->orWhere('name->en', $name);
        })->first();
    }

    /**
     * Options for the checkout city selector. App\Enums\City is the canonical list;
     * the register.cities translation list is only a fallback while it does not exist.
     *
     * @return array<string, string> value => label
     */
    public static function deliveryCities(): array
    {
        if (enum_exists(City::class)) {
            return City::options();
        }

        $cities = t('register.cities');

        return is_array($cities) ? $cities : [];
    }

    /**
     * Slugs and labels are both accepted on input; the label is the storage form,
     * matching the profile `city` columns.
     *
     * @return list<string>
     */
    private static function acceptedCityValues(): array
    {
        if (enum_exists(City::class)) {
            return City::acceptedValues();
        }

        $cities = self::deliveryCities();

        return $cities ? array_merge(array_keys($cities), array_values($cities)) : [];
    }

    private static function canonicalCity(string $value): string
    {
        if (enum_exists(City::class)) {
            return City::canonical($value) ?? $value;
        }

        return self::deliveryCities()[$value] ?? $value;
    }

    /**
     * DB translation with a fallback, so a not-yet-seeded key never surfaces as a raw key.
     */
    private static function text(string $key, array $replace, string $fallback): string
    {
        $value = t($key, $replace);

        return is_string($value) && $value !== $key ? $value : $fallback;
    }
}
