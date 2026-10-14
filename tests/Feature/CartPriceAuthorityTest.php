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
 * The basket on screen is drawn from localStorage, but the order is priced from
 * the database. A row saved before the seller changed the price used to keep the
 * old one, so the cart could show one total and the placed order charge another.
 * cart.js now reprices every row from these two payloads, which is what these
 * tests pin down: the page and the items endpoint must carry the live price
 * (and the stock the +/- stepper stops at).
 */
class CartPriceAuthorityTest extends TestCase
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

    public function test_items_endpoint_exposes_the_live_price_and_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['price' => 28.00, 'stock' => 7]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        $product->update(['price' => 31.50]);

        $this->actingAs($user)
            ->getJson('/api/cart/items')
            ->assertOk()
            ->assertJsonPath('items.0.price', 31.5)
            ->assertJsonPath('items.0.stock', 7);
    }

    public function test_cart_page_hands_the_database_price_and_stock_to_the_browser(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['price' => 28.00, 'stock' => 7]);
        CartItem::create(['user_id' => $user->id, 'product_id' => $product->id, 'quantity' => 2]);

        $response = $this->actingAs($user)->get('/cart')->assertOk();

        $serverCart = json_decode(
            $this->extractAttribute($response->getContent(), 'data-server-cart'),
            true,
            flags: JSON_THROW_ON_ERROR
        );

        $this->assertSame($product->id, $serverCart[0]['id']);
        $this->assertSame(28.0, (float) $serverCart[0]['price']);
        $this->assertSame(7, (int) $serverCart[0]['stock']);
    }

    /** The error the stepper shows when it refuses to go past the stock. */
    public function test_cart_page_carries_the_out_of_stock_wording(): void
    {
        $this->get('/cart')
            ->assertOk()
            ->assertSee('errOutOfStock', escape: false);
    }

    private function extractAttribute(string $html, string $attribute): string
    {
        preg_match('/'.preg_quote($attribute, '/').'="([^"]*)"/', $html, $matches);

        return html_entity_decode($matches[1] ?? '', ENT_QUOTES);
    }
}
