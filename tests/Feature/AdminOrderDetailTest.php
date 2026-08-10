<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminOrderDetailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_order_edit_page_shows_buyer_product_seller_and_totals(): void
    {
        $admin = User::factory()->admin()->create();
        $buyer = User::factory()->buyer()->create([
            'name' => 'Test Buyer',
            'email' => 'buyer-detail@example.com',
        ]);
        $order = Order::create([
            'order_number' => 'ARCHI-DETAIL-001',
            'user_id' => $buyer->id,
            'status' => 'pending',
            'subtotal' => 75,
            'discount' => 0,
            'delivery_fee' => 10,
            'total' => 85,
            'delivery_name' => 'Delivery Person',
            'delivery_phone' => '+994501112233',
            'delivery_address' => 'Test address 10',
            'delivery_city' => 'Bakı',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_snapshot' => [
                'name' => 'Snapshot Product',
                'sku' => 'SKU-DETAIL',
                'brand' => 'Snapshot Brand',
                'cat' => 'Snapshot Category',
                'unit' => 'm2',
                'seller' => [
                    'id' => 99,
                    'name' => 'Snapshot Seller',
                    'email' => 'seller-detail@example.com',
                    'phone' => '+994509998877',
                ],
            ],
            'quantity' => 3,
            'unit_price' => 25,
            'total' => 75,
        ]);

        $this->actingAs($admin, 'admin');

        Livewire::test(EditOrder::class, ['record' => $order->getRouteKey()])
            ->assertOk()
            ->assertSee('ARCHI-DETAIL-001')
            ->assertSee('Test Buyer')
            ->assertSee('buyer-detail@example.com')
            ->assertSee('Snapshot Product')
            ->assertSee('Snapshot Seller')
            ->assertSee('seller-detail@example.com')
            ->assertSee('SKU-DETAIL');
    }

    public function test_navigation_badge_returns_a_string_or_null(): void
    {
        $this->assertNull(OrderResource::getNavigationBadge());

        $buyer = User::factory()->buyer()->create();
        Order::create([
            'order_number' => 'ARCHI-BADGE-001',
            'user_id' => $buyer->id,
            'status' => 'pending',
            'subtotal' => 10,
            'discount' => 0,
            'delivery_fee' => 0,
            'total' => 10,
        ]);

        $this->assertSame('1', OrderResource::getNavigationBadge());
    }
}
