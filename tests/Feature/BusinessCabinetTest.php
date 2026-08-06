<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BusinessCabinetTest extends TestCase
{
    use RefreshDatabase;

    // ─── Access control ───────────────────────────────────────

    public function test_only_sellers_can_access_business_cabinet_routes(): void
    {
        // Guests are sent to login first…
        $this->get('/business/orders')->assertRedirect('/login');

        // …authenticated non-sellers are forbidden.
        $buyer = User::factory()->buyer()->create();

        foreach ([
            '/business/profile/company',
            '/business/profile/products',
            '/business/inventory',
            '/business/orders',
            '/business/products/create',
        ] as $url) {
            $this->actingAs($buyer)->get($url)->assertForbidden();
        }
    }

    public function test_seller_can_open_cabinet_pages(): void
    {
        $seller = User::factory()->seller()->create();

        foreach ([
            '/business/profile/company',
            '/business/profile/contact',
            '/business/profile/showrooms',
            '/business/profile/notifications',
            '/business/profile/products',
            '/business/inventory',
            '/business/orders',
            '/business/products/create',
        ] as $url) {
            $this->actingAs($seller)->get($url)->assertOk();
        }
    }

    // ─── Profile ──────────────────────────────────────────────

    public function test_seller_can_update_company_profile(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->putJson('/business/profile/company', [
            'legal_name' => 'Archi Tikinti MMC',
            'brand_name' => 'ArchiStore',
            'tax_id' => '1234567890',
            'city' => 'Bakı',
            'address' => 'Nizami küç. 5',
            'about' => 'Tikinti materialları satışı.',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('seller_profiles', [
            'user_id' => $seller->id,
            'legal_name' => 'Archi Tikinti MMC',
            'brand_name' => 'ArchiStore',
        ]);
    }

    public function test_seller_can_update_contact_and_notifications(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->putJson('/business/profile/contact', [
            'contact_person' => 'Elvin Məmmədov',
            'contact_phone' => '+994501112233',
            'languages' => ['az', 'ru'],
        ])->assertOk()->assertJson(['success' => true]);

        $this->actingAs($seller)->putJson('/business/profile/notifications', [
            'notification_settings' => [
                'orders' => true,
                'reviews' => false,
                'channels' => ['email', 'sms'],
            ],
        ])->assertOk()->assertJson(['success' => true]);

        $profile = $seller->fresh()->sellerProfile;
        $this->assertSame('Elvin Məmmədov', $profile->contact_person);
        $this->assertSame(['az', 'ru'], $profile->languages);
    }

    public function test_seller_can_upload_and_delete_logo(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/profile/logo', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertOk()->assertJson(['success' => true]);

        $path = $seller->fresh()->sellerProfile->logo_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($seller)->delete('/business/profile/logo')->assertOk();
        $this->assertNull($seller->fresh()->sellerProfile->logo_path);
        Storage::disk('public')->assertMissing($path);
    }

    // ─── Product lifecycle & moderation ───────────────────────

    public function test_publishing_a_product_sends_it_to_moderation(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $category = $this->category();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Keramik kafel 60×60',
            'category_id' => $category->id,
            'price' => 23.90,
            'stock' => 148,
            'publish' => 1,
            'images' => [UploadedFile::fake()->image('p.jpg')],
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['success' => true]);

        $product = Product::where('user_id', $seller->id)->first();
        $this->assertTrue($product->is_visible);
        $this->assertFalse($product->is_approved);
        $this->assertSame('pending', $product->moderation_status);
        $this->assertTrue($product->images()->where('is_main', true)->exists());
    }

    public function test_draft_product_is_hidden(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Qaralama məhsul',
            'category_id' => $this->category()->id,
            'price' => 10,
            'stock' => 5,
            'publish' => 0,
        ], ['Accept' => 'application/json'])->assertOk();

        $product = Product::where('user_id', $seller->id)->first();
        $this->assertFalse($product->is_visible);
    }

    public function test_pending_product_is_not_publicly_visible_until_admin_approves(): void
    {
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, ['is_visible' => true, 'is_approved' => false]);

        // Pending → hidden from the public product page
        $this->get('/product/'.$product->slug)->assertNotFound();

        // Admin approves → public
        $product->update(['is_approved' => true]);
        $this->get('/product/'.$product->slug)->assertOk();
    }

    public function test_editing_a_product_resets_moderation(): void
    {
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, ['is_visible' => true, 'is_approved' => true]);

        $this->actingAs($seller)->putJson('/business/products/'.$product->id, [
            'name' => 'Yenilənmiş ad',
            'category_id' => $product->category_id,
            'price' => 30,
            'stock' => 10,
        ])->assertOk();

        $product->refresh();
        $this->assertFalse($product->is_approved);
        $this->assertSame('pending', $product->moderation_status);
    }

    public function test_rejected_product_state(): void
    {
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, [
            'is_visible' => false,
            'is_approved' => false,
            'rejected_at' => now(),
            'rejection_reason' => 'Şəkil keyfiyyəti aşağıdır',
        ]);

        $this->assertSame('rejected', $product->moderation_status);

        // Re-submitting clears the rejection
        $this->actingAs($seller)->putJson('/business/products/'.$product->id, [
            'name' => 'Düzəldilmiş məhsul',
            'category_id' => $product->category_id,
            'price' => 20,
            'stock' => 3,
            'publish' => 1,
        ])->assertOk();

        $this->assertSame('pending', $product->fresh()->moderation_status);
    }

    public function test_seller_cannot_touch_other_sellers_product(): void
    {
        $owner = User::factory()->seller()->create();
        $intruder = User::factory()->seller()->create();
        $product = $this->product($owner);

        $this->actingAs($intruder)->putJson('/business/products/'.$product->id, [
            'name' => 'Hack', 'category_id' => $product->category_id, 'price' => 1, 'stock' => 1,
        ])->assertForbidden();
        $this->actingAs($intruder)->post('/business/products/'.$product->id.'/toggle')->assertForbidden();
        $this->actingAs($intruder)->delete('/business/products/'.$product->id)->assertForbidden();
        $this->actingAs($intruder)->get('/business/products/'.$product->id.'/edit')->assertForbidden();
    }

    public function test_visibility_toggle_and_stock_update(): void
    {
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, ['is_visible' => true, 'stock' => 5]);

        $this->actingAs($seller)->post('/business/products/'.$product->id.'/toggle')
            ->assertOk()->assertJson(['is_visible' => false]);

        $this->actingAs($seller)->postJson('/business/products/'.$product->id.'/stock', ['stock' => 42])
            ->assertOk()->assertJson(['stock' => 42]);

        $this->assertSame(42, $product->fresh()->stock);
    }

    // ─── Showrooms ────────────────────────────────────────────

    public function test_showroom_crud_and_authorization(): void
    {
        $seller = User::factory()->seller()->create();

        $res = $this->actingAs($seller)->postJson('/business/showrooms', [
            'name' => 'Gənclik filialı',
            'city' => 'Bakı',
            'status' => 'active',
        ])->assertOk()->assertJson(['success' => true]);

        $id = $res->json('showroom.id');

        $this->actingAs($seller)->putJson('/business/showrooms/'.$id, [
            'name' => 'Gənclik Mall filialı',
            'status' => 'hidden',
        ])->assertOk();

        $this->assertDatabaseHas('showrooms', ['id' => $id, 'name' => 'Gənclik Mall filialı', 'status' => 'hidden']);

        $other = User::factory()->seller()->create();
        $this->actingAs($other)->deleteJson('/business/showrooms/'.$id)->assertForbidden();

        $this->actingAs($seller)->deleteJson('/business/showrooms/'.$id)->assertOk();
        $this->assertDatabaseMissing('showrooms', ['id' => $id]);
    }

    public function test_business_counts_and_inventory_layout_use_real_data(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller);
        $this->product($seller);

        $this->actingAs($seller)->postJson('/business/showrooms', [
            'name' => 'Yeganə şourum',
            'status' => 'active',
        ])->assertOk();

        $this->actingAs($seller)->get('/business/profile/products')
            ->assertOk()
            ->assertSee('Məhsullar')
            ->assertDontSee('Məhsullar (1,240)')
            ->assertDontSee('Dərc olunub');

        $this->actingAs($seller)->get('/business/profile/showrooms')
            ->assertOk()
            ->assertSee('Şourumlar (1)')
            ->assertSee('max-h-[calc(100vh-2rem)]', false);

        $this->actingAs($seller)->get('/business/inventory')
            ->assertOk()
            ->assertDontSee('Anbar dəyəri')
            ->assertSee('w-full overflow-x-auto', false);
    }

    public function test_business_security_and_order_pages_hide_removed_controls_and_deactivate_account(): void
    {
        $seller = User::factory()->seller()->create([
            'password' => bcrypt('seller-password'),
        ]);

        $this->actingAs($seller)->get('/business/profile/security')
            ->assertOk()
            ->assertDontSee('İki mərhələli doğrulama')
            ->assertDontSee('Aktiv sessiyalar')
            ->assertSee('id="deactivateConfirmBtn"', false);

        $this->actingAs($seller)->get('/business/orders')
            ->assertOk()
            ->assertDontSee('status=pending', false)
            ->assertDontSee('name="q"', false)
            ->assertDontSee('Hesabat ↗');

        $this->actingAs($seller)->get('/business/inventory')
            ->assertOk()
            ->assertDontSee('Excel ilə yüklə ↗');

        $this->actingAs($seller)->postJson('/cabinet/deactivate', [
            'password' => 'wrong-password',
        ])->assertUnprocessable();
        $this->assertTrue($seller->fresh()->isActive());

        $this->actingAs($seller)->postJson('/cabinet/deactivate', [
            'password' => 'seller-password',
        ])->assertOk()->assertJsonStructure(['redirect']);
        $this->assertFalse($seller->fresh()->isActive());
        $this->assertGuest();
    }

    // ─── Orders ───────────────────────────────────────────────

    public function test_seller_sees_only_orders_containing_their_products(): void
    {
        $seller = User::factory()->seller()->create();
        $otherSeller = User::factory()->seller()->create();
        $buyer = User::factory()->buyer()->create();

        $mine = $this->orderWithProduct($buyer, $seller);
        $foreign = $this->orderWithProduct($buyer, $otherSeller);

        $this->actingAs($seller)->get('/business/orders')
            ->assertOk()
            ->assertSee($mine->order_number)
            ->assertDontSee($foreign->order_number);

        $this->actingAs($seller)->get('/business/orders/'.$foreign->id)->assertForbidden();
        $this->actingAs($seller)->get('/business/orders/'.$mine->id)->assertOk();
    }

    public function test_order_status_flow_is_forward_only(): void
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->buyer()->create();
        $order = $this->orderWithProduct($buyer, $seller); // status: pending

        // Illegal jump pending → delivered
        $this->actingAs($seller)->postJson("/business/orders/{$order->id}/status", ['status' => 'delivered'])
            ->assertStatus(422);

        // Legal chain
        foreach (['processing', 'shipped', 'delivered'] as $next) {
            $this->actingAs($seller)->postJson("/business/orders/{$order->id}/status", ['status' => $next])
                ->assertOk()->assertJson(['status' => $next]);
        }

        // Delivered orders cannot be cancelled
        $this->actingAs($seller)->postJson("/business/orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertStatus(422);
    }

    public function test_order_can_be_cancelled_before_delivery(): void
    {
        $seller = User::factory()->seller()->create();
        $buyer = User::factory()->buyer()->create();
        $order = $this->orderWithProduct($buyer, $seller);

        $this->actingAs($seller)->postJson("/business/orders/{$order->id}/status", ['status' => 'cancelled'])
            ->assertOk();

        // Cancelled is terminal
        $this->actingAs($seller)->postJson("/business/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertStatus(422);
    }

    // ─── Helpers ──────────────────────────────────────────────

    private function category(): Category
    {
        return Category::query()->create([
            'name' => ['az' => 'Kafel'],
            'slug' => 'kafel-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function product(User $seller, array $overrides = []): Product
    {
        return Product::create(array_merge([
            'user_id' => $seller->id,
            'category_id' => $this->category()->id,
            'name' => ['az' => 'Test məhsul'],
            'description' => ['az' => ''],
            'slug' => 'test-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => false,
        ], $overrides));
    }

    private function orderWithProduct(User $buyer, User $seller): Order
    {
        $product = $this->product($seller, ['is_approved' => true]);

        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $buyer->id,
            'status' => 'pending',
            'subtotal' => 20,
            'total' => 20,
            'delivery_name' => $buyer->name,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_snapshot' => ['name' => 'Test məhsul'],
            'quantity' => 2,
            'unit_price' => 10,
            'total' => 20,
        ]);

        return $order;
    }
}
