<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_sees_translated_status_line_items_and_delivery_address(): void
    {
        $user = User::factory()->buyer()->create();

        $order = Order::query()->create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => 120.00,
            'total' => 125.00,
            'delivery_name' => 'Nigar Əliyeva',
            'delivery_phone' => '+994701112233',
            'delivery_address' => 'Nizami küç. 1',
            'delivery_city' => 'Bakı',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_snapshot' => ['name' => 'Mat ağ kafel 60x60'],
            'quantity' => 3,
            'unit_price' => 40.00,
            'total' => 120.00,
        ]);

        $response = $this->actingAs($user)->get('/account/orders');

        $response->assertOk();
        $response->assertSee($order->order_number);
        // The raw enum value must never reach the customer.
        $response->assertDontSee('>pending<', false);
        // Buyer-facing wording, not the seller-facing "Yeni" fallback.
        $response->assertSee(t('account.order_status.pending'));
        // Line items and the delivery address are visible.
        $response->assertSee('Mat ağ kafel 60x60');
        $response->assertSee('3');
        $response->assertSee('Nizami küç. 1');
        $response->assertSee('Bakı');
    }

    public function test_orders_page_still_renders_for_a_buyer_with_no_orders(): void
    {
        $user = User::factory()->buyer()->create();

        $this->actingAs($user)->get('/account/orders')
            ->assertOk()
            ->assertSee(t('account.no_orders'));
    }
}
