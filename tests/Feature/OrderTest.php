<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    /** Ordering requires an account, so every checkout test runs as a signed-in buyer. */
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(['role' => 'buyer', 'status' => 'active']));
    }

    // ─── Pricing ──────────────────────────────────────────────

    public function test_server_prices_the_order_from_the_database_and_ignores_the_posted_price(): void
    {
        $product = $this->product(['price' => 89, 'stock' => 500]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 500, 'price' => 0.01],
        ]))->assertOk()->assertJson(['success' => true]);

        $order = Order::firstOrFail();

        // 500 × 89 = 44 500, free delivery above 100 ₼
        $this->assertSame('44500.00', $order->subtotal);
        $this->assertSame('44500.00', $order->total);
        $this->assertSame('89.00', $order->items()->first()->unit_price);
    }

    public function test_delivery_fee_is_added_below_the_free_threshold(): void
    {
        $product = $this->product(['price' => 10, 'stock' => 50]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 2],
        ]))->assertOk();

        $order = Order::firstOrFail();
        $this->assertSame('20.00', $order->subtotal);
        $this->assertSame('10.00', $order->delivery_fee);
        $this->assertSame('30.00', $order->total);
    }

    public function test_line_without_product_id_still_resolves_and_prices_from_the_database(): void
    {
        $product = $this->product(['price' => 25, 'stock' => 10, 'name' => ['az' => 'Köhnə səbət məhsulu']]);

        $this->postJson('/api/orders', $this->payload([
            ['name' => 'Köhnə səbət məhsulu', 'qty' => 2, 'price' => 1],
        ]))->assertOk();

        $order = Order::firstOrFail();
        $this->assertSame($product->id, $order->items()->first()->product_id);
        $this->assertSame('50.00', $order->subtotal);
    }

    public function test_unresolvable_or_unpublished_products_are_rejected(): void
    {
        $hidden = $this->product(['is_visible' => false]);
        $pending = $this->product(['is_approved' => false]);

        $this->postJson('/api/orders', $this->payload([['product_id' => 999999, 'qty' => 1]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.name');

        $this->postJson('/api/orders', $this->payload([['product_id' => $hidden->id, 'qty' => 1]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.name');

        $this->postJson('/api/orders', $this->payload([['product_id' => $pending->id, 'qty' => 1]]))
            ->assertStatus(422)->assertJsonValidationErrors('items.0.name');

        $this->assertSame(0, Order::count());
    }

    // ─── Stock ────────────────────────────────────────────────

    public function test_ordering_more_than_the_available_stock_is_rejected(): void
    {
        $product = $this->product(['stock' => 3]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 4],
        ]))->assertStatus(422)->assertJsonValidationErrors('items.0.qty');

        $this->assertSame(0, Order::count());
        $this->assertSame(3, $product->fresh()->stock);
    }

    public function test_duplicate_lines_for_the_same_product_are_summed_before_the_stock_check(): void
    {
        $product = $this->product(['stock' => 3]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 2],
            ['product_id' => $product->id, 'qty' => 2],
        ]))->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    public function test_out_of_stock_product_cannot_be_ordered(): void
    {
        $product = $this->product(['stock' => 0]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 1],
        ]))->assertStatus(422);
    }

    public function test_min_order_is_enforced(): void
    {
        $product = $this->product(['stock' => 100, 'min_order' => 5]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 2],
        ]))->assertStatus(422)->assertJsonValidationErrors('items.0.qty');

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 5],
        ]))->assertOk();
    }

    public function test_per_line_quantity_has_an_upper_bound(): void
    {
        $product = $this->product(['stock' => 100000]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 1000],
        ]))->assertStatus(422)->assertJsonValidationErrors('items.0.qty');
    }

    public function test_successful_order_decrements_stock_and_increments_sold_count(): void
    {
        $product = $this->product(['stock' => 10, 'price' => 20]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 4],
        ]))->assertOk();

        $product->refresh();
        $this->assertSame(6, $product->stock);
        $this->assertSame(4, (int) $product->sold_count);
    }

    // ─── Sign-in required ─────────────────────────────────────

    public function test_a_guest_cannot_place_an_order(): void
    {
        $product = $this->product(['stock' => 5, 'price' => 30]);

        // Drop the buyer setUp() signed in, so this really is an anonymous visitor.
        auth()->logout();

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 2],
        ]))->assertUnauthorized();

        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_signed_in_buyer_can_complete_a_checkout(): void
    {
        $product = $this->product(['stock' => 5, 'price' => 30]);

        $response = $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 2],
        ]))->assertOk();

        $order = Order::firstOrFail();
        $this->assertSame(auth()->id(), $order->user_id);
        // the slug is canonicalised to the label App\Enums\City stores
        $this->assertSame('Bakı', $order->delivery_city);
        $this->assertSame('60.00', $order->subtotal);

        $this->get($response->json('redirect'))->assertOk()->assertSee($order->order_number);
    }

    public function test_delivery_address_is_required(): void
    {
        $product = $this->product();
        $payload = $this->payload([['product_id' => $product->id, 'qty' => 1]]);
        unset($payload['delivery_address']);

        $this->postJson('/api/orders', $payload)
            ->assertStatus(422)->assertJsonValidationErrors('delivery_address');
    }

    public function test_delivery_city_is_required(): void
    {
        $product = $this->product();

        $payload = $this->payload([['product_id' => $product->id, 'qty' => 1]]);
        unset($payload['delivery_city']);

        $this->postJson('/api/orders', $payload)
            ->assertStatus(422)->assertJsonValidationErrors('delivery_city');
    }

    // ─── Promo codes ──────────────────────────────────────────

    public function test_promo_codes_are_never_applied(): void
    {
        $product = $this->product(['price' => 100, 'stock' => 10]);

        $this->postJson('/api/orders', $this->payload(
            [['product_id' => $product->id, 'qty' => 2]],
            ['promo_code' => 'ARCHI15']
        ))->assertOk();

        $order = Order::firstOrFail();
        $this->assertSame('0.00', $order->discount);
        $this->assertNull($order->promo_code);
        $this->assertSame('200.00', $order->total);
    }

    // ─── Seller notifications ─────────────────────────────────

    public function test_each_seller_is_notified_once_with_only_their_own_items(): void
    {
        Notification::fake();

        $sellerA = User::factory()->seller()->create();
        $sellerB = User::factory()->seller()->create();

        $a1 = $this->product(['stock' => 10, 'user_id' => $sellerA->id]);
        $a2 = $this->product(['stock' => 10, 'user_id' => $sellerA->id]);
        $b1 = $this->product(['stock' => 10, 'user_id' => $sellerB->id]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $a1->id, 'qty' => 1],
            ['product_id' => $a2->id, 'qty' => 2],
            ['product_id' => $b1->id, 'qty' => 3],
        ]))->assertOk();

        Notification::assertSentToTimes($sellerA, NewOrderNotification::class, 1);
        Notification::assertSentToTimes($sellerB, NewOrderNotification::class, 1);

        Notification::assertSentTo($sellerA, NewOrderNotification::class, function (NewOrderNotification $n) use ($a1, $a2) {
            return $n->sellerItems->pluck('product_id')->sort()->values()->all()
                === collect([$a1->id, $a2->id])->sort()->values()->all();
        });

        Notification::assertSentTo($sellerB, NewOrderNotification::class, function (NewOrderNotification $n) use ($b1) {
            return $n->sellerItems->count() === 1
                && $n->sellerItems->first()->product_id === $b1->id
                && $n->sellerItems->first()->product_snapshot['name'] !== '';
        });
    }

    // ─── Cart page ────────────────────────────────────────────

    public function test_order_item_snapshot_preserves_product_and_seller_details(): void
    {
        $seller = User::factory()->seller()->create([
            'name' => 'Test Seller',
            'email' => 'snapshot-seller@example.com',
            'phone' => '+994501234567',
        ]);
        $product = $this->product([
            'user_id' => $seller->id,
            'sku' => 'SNAP-001',
            'unit' => 'm2',
        ]);

        $this->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 2],
        ]))->assertOk();

        $snapshot = Order::firstOrFail()->items()->firstOrFail()->product_snapshot;

        $this->assertSame($product->id, $snapshot['id']);
        $this->assertSame('SNAP-001', $snapshot['sku']);
        $this->assertSame('m2', $snapshot['unit']);
        $this->assertSame($seller->id, $snapshot['seller']['id']);
        $this->assertSame('Test Seller', $snapshot['seller']['name']);
        $this->assertSame('snapshot-seller@example.com', $snapshot['seller']['email']);
        $this->assertSame('+994501234567', $snapshot['seller']['phone']);
    }

    public function test_cart_page_no_longer_offers_promo_codes_and_exposes_the_city_list(): void
    {
        $response = $this->get('/cart')->assertOk();

        $response->assertDontSee('ARCHI15')
            ->assertDontSee('ctPromo')
            ->assertDontSee('data-promos', false);

        // the city map is JSON-encoded into data-cities, so labels arrive \u-escaped
        $response->assertSee('data-cities', false)->assertSee('baku', false);
    }

    // ─── Order success authorization ──────────────────────────

    public function test_order_success_page_is_not_readable_by_a_stranger(): void
    {
        $buyer = User::factory()->buyer()->create();
        $product = $this->product(['stock' => 5]);

        $this->actingAs($buyer)->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 1],
        ]))->assertOk();

        $order = Order::firstOrFail();

        $this->flushSession();
        $stranger = User::factory()->buyer()->create();
        $this->actingAs($stranger)->get("/order/{$order->order_number}/success")->assertNotFound();

        $this->flushSession();
        $this->get("/order/{$order->order_number}/success")->assertNotFound();
    }

    public function test_buyer_can_view_their_own_order_success_page(): void
    {
        $buyer = User::factory()->buyer()->create();
        $product = $this->product(['stock' => 5]);

        $this->actingAs($buyer)->postJson('/api/orders', $this->payload([
            ['product_id' => $product->id, 'qty' => 1],
        ]))->assertOk();

        $order = Order::firstOrFail();
        $this->flushSession();

        $this->actingAs($buyer)->get("/order/{$order->order_number}/success")->assertOk();
    }

    public function test_generated_order_number_keeps_its_shape_and_is_unpredictable(): void
    {
        $numbers = [];
        for ($i = 0; $i < 20; $i++) {
            $number = Order::generateOrderNumber();
            $this->assertMatchesRegularExpression('/^ARCHI-\d{8}-[A-Z2-9]{6}$/', $number);
            $numbers[] = $number;
        }

        $this->assertCount(20, array_unique($numbers));
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function payload(array $items, array $extra = []): array
    {
        return array_merge([
            'items' => $items,
            'delivery_name' => 'Test Alıcı',
            'delivery_phone' => '+994501112233',
            'delivery_city' => 'baku',
            'delivery_address' => 'Test küç. 1',
        ], $extra);
    }

    private function product(array $overrides = []): Product
    {
        $category = Category::query()->create([
            'name' => ['az' => 'Kafel'],
            'slug' => 'kafel-'.uniqid(),
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'user_id' => User::factory()->seller()->create()->id,
            'category_id' => $category->id,
            'name' => ['az' => 'Test məhsul '.uniqid()],
            'description' => ['az' => ''],
            'slug' => 'test-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'min_order' => 1,
            'is_visible' => true,
            'is_approved' => true,
        ], $overrides));
    }
}
