<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The storefront cart lives in localStorage, the account cart on the server, and
 * the calculator's cart step merges the two. These endpoints are what keeps them
 * from drifting apart: without them, a product removed on /cart kept reappearing
 * in the calculator from the stale server copy.
 */
class CartSyncTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $category = Category::query()->create([
            'name' => ['az' => 'Kafel'],
            'slug' => 'kafel-'.uniqid(),
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'name' => ['az' => 'Test məhsul'],
            'description' => ['az' => ''],
            'slug' => 'test-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => true,
        ], $attributes));
    }

    public function test_guests_get_an_empty_items_list(): void
    {
        $this->getJson('/api/cart/items')
            ->assertOk()
            ->assertExactJson(['items' => []]);
    }

    public function test_the_items_endpoint_returns_the_account_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 3]);

        $this->actingAs($user)
            ->getJson('/api/cart/items')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $product->id)
            ->assertJsonPath('items.0.quantity', 3);
    }

    public function test_hidden_products_are_not_served_from_the_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['is_visible' => false]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($user)
            ->getJson('/api/cart/items')
            ->assertOk()
            ->assertJsonCount(0, 'items');
    }

    public function test_removing_by_product_deletes_the_server_row(): void
    {
        $user = User::factory()->create();
        $product = $this->product();
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)
            ->deleteJson("/api/cart/product/{$product->id}")
            ->assertOk()
            ->assertJsonPath('count', 0);

        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_removing_by_product_only_touches_the_callers_cart(): void
    {
        $product = $this->product();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        CartItem::create(['user_id' => $owner->id, 'product_id' => $product->id, 'quantity' => 1]);
        CartItem::create(['user_id' => $other->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($owner)->deleteJson("/api/cart/product/{$product->id}")->assertOk();

        $this->assertDatabaseHas('cart_items', [
            'user_id' => $other->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_guests_cannot_remove_by_product(): void
    {
        $product = $this->product();

        $this->deleteJson("/api/cart/product/{$product->id}")->assertUnauthorized();
    }
}
