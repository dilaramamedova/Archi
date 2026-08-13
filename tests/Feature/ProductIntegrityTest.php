<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Guards the "the site must not lie about a product" rules:
 * the 5-product seller cap, the mandatory publish image, and a product page /
 * catalog card that only ever shows the product's own data.
 */
class ProductIntegrityTest extends TestCase
{
    use RefreshDatabase;

    // ─── Seller product cap ───────────────────────────────────

    public function test_sixth_product_is_rejected(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $category = $this->category();

        for ($i = 1; $i <= 5; $i++) {
            $this->actingAs($seller)->post('/business/products', [
                'name' => "Məhsul {$i}",
                'category_id' => $category->id,
                'price' => 10,
                'stock' => 1,
                'publish' => 0,
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Məhsul 6',
            'category_id' => $category->id,
            'price' => 10,
            'stock' => 1,
            'publish' => 0,
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->assertSame(5, $seller->products()->count());
    }

    public function test_admins_are_not_capped(): void
    {
        $admin = User::factory()->admin()->create();
        $category = $this->category();

        for ($i = 1; $i <= 6; $i++) {
            $this->actingAs($admin)->post('/business/products', [
                'name' => "Admin məhsulu {$i}",
                'category_id' => $category->id,
                'price' => 10,
                'stock' => 1,
                'publish' => 0,
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $this->assertSame(6, $admin->products()->count());
    }

    public function test_create_form_shows_the_remaining_quota(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller);

        $this->actingAs($seller)->get('/business/products/create')
            ->assertOk()
            ->assertSee('4');
    }

    // ─── Seller cabinet search ────────────────────────────────

    public function test_seller_cabinet_search_is_case_insensitive(): void
    {
        $seller = User::factory()->seller()->create();
        $this->product($seller, ['name' => ['az' => 'Keramik kafel 60×60'], 'sku' => 'KRM-001']);

        // `products.name` is a JSON column — a raw LIKE would miss this.
        $this->actingAs($seller)->get('/business/profile/products?q=keramik')
            ->assertOk()
            ->assertSee('Keramik kafel 60×60');

        $this->actingAs($seller)->get('/business/inventory?q=krm-001')
            ->assertOk()
            ->assertSee('Keramik kafel 60×60');
    }

    // ─── Mandatory image on publish ───────────────────────────

    public function test_publishing_without_an_image_is_rejected(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Şəkilsiz məhsul',
            'category_id' => $this->category()->id,
            'price' => 10,
            'stock' => 1,
            'publish' => 1,
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images']);

        $this->assertSame(0, $seller->products()->count());
    }

    public function test_draft_without_an_image_is_allowed(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Qaralama',
            'category_id' => $this->category()->id,
            'price' => 10,
            'stock' => 1,
            'publish' => 0,
        ], ['Accept' => 'application/json'])->assertOk();

        $product = $seller->products()->first();
        $this->assertFalse($product->is_visible);
        $this->assertSame(0, $product->images()->count());
    }

    public function test_update_cannot_strip_the_last_image_of_a_published_product(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, ['is_visible' => true, 'is_approved' => true]);
        $this->image($product);

        // No kept_image_ids and no uploads → the product would end up image-less.
        $this->actingAs($seller)->putJson('/business/products/'.$product->id, [
            'name' => 'Yenilənmiş',
            'category_id' => $product->category_id,
            'price' => 20,
            'stock' => 4,
            'kept_image_ids' => [],
        ])->assertStatus(422)->assertJsonValidationErrors(['images']);

        $this->assertSame(1, $product->images()->count());
    }

    /**
     * Regression: the visibility toggle used to be the way around the publish-image
     * rule — an image-less draft could simply be flipped visible.
     */
    public function test_toggling_an_imageless_draft_visible_is_rejected(): void
    {
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, ['is_visible' => false]);

        $this->actingAs($seller)->postJson('/business/products/'.$product->id.'/toggle')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images']);

        $this->assertFalse($product->fresh()->is_visible);
    }

    /**
     * Regression: hide → strip every image (allowed while hidden) → re-publish
     * used to land an image-less product back in the catalog.
     */
    public function test_hide_strip_images_then_republish_is_rejected(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, ['is_visible' => true, 'is_approved' => true]);
        $this->image($product);

        // 1. hide it — allowed, and does not need an image
        $this->actingAs($seller)->postJson('/business/products/'.$product->id.'/toggle')
            ->assertOk()->assertJson(['is_visible' => false]);

        // 2. while hidden, drop every image — the update guard only covers visible products
        $this->actingAs($seller)->putJson('/business/products/'.$product->id, [
            'name' => 'Şəkilsiz',
            'category_id' => $product->category_id,
            'price' => 20,
            'stock' => 4,
            'kept_image_ids' => [],
        ])->assertOk();
        $this->assertSame(0, $product->fresh()->images()->count());

        // 3. re-publishing must now be refused
        $this->actingAs($seller)->postJson('/business/products/'.$product->id.'/toggle')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['images']);

        $this->assertFalse($product->fresh()->is_visible);
    }

    public function test_toggle_still_publishes_a_product_that_has_an_image(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();
        $product = $this->product($seller, ['is_visible' => false]);
        $this->image($product);

        $this->actingAs($seller)->postJson('/business/products/'.$product->id.'/toggle')
            ->assertOk()->assertJson(['is_visible' => true]);
    }

    /**
     * Regression: /sell creates the listing already visible, so it is a publish
     * action — it used to accept a listing with no image at all.
     */
    public function test_sell_form_requires_an_image(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/sell', [
            'name' => 'Şəkilsiz elan',
            'category_id' => $this->category()->id,
            'price' => 10,
        ], ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['image']);

        $this->assertSame(0, $seller->products()->count());
    }

    public function test_sell_form_accepts_a_listing_with_an_image(): void
    {
        Storage::fake('public');
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/sell', [
            'name' => 'Şəkilli elan',
            'category_id' => $this->category()->id,
            'price' => 10,
            'image' => UploadedFile::fake()->image('p.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $product = $seller->products()->first();
        $this->assertTrue($product->is_visible);
        $this->assertSame(1, $product->images()->count());
    }

    // ─── Product page tells the truth ─────────────────────────

    public function test_product_page_shows_real_stock_and_specs_and_no_demo_copy(): void
    {
        $product = $this->publicProduct([
            'stock' => 7,
            'unit' => 'm2',
            'sold_count' => 0,
            'specifications' => ['dimensions' => '30×60 sm', 'color' => 'Xrom'],
        ]);

        $response = $this->get('/product/'.$product->slug)->assertOk();

        // Real data
        $response->assertSee('Stokda var')
            ->assertSee('30×60 sm')
            ->assertSee('Xrom');

        // Fabricated demo copy must be gone
        $response->assertDontSee('Stokda var — 480 m² hazırdır')
            ->assertDontSee('3,200+ satıldı')
            ->assertDontSee('Mat səth, sürüşməyə davamlı (R10)')
            ->assertDontSee('60×60 sm · qutuda 1.44 m² (4 ədəd)')
            ->assertDontSee('Keramoqranit')
            ->assertDontSee('~2 saat')
            ->assertDontSee('Rəsmi mağaza');
    }

    public function test_out_of_stock_product_page_does_not_also_claim_to_be_in_stock(): void
    {
        $product = $this->publicProduct(['stock' => 0]);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('Stokda yoxdur')
            ->assertDontSee('Stokda var');
    }

    public function test_unverified_seller_gets_no_verified_badge(): void
    {
        $product = $this->publicProduct();

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertDontSee('Təsdiqlənmiş');
    }

    public function test_product_without_images_does_not_borrow_another_products_photos(): void
    {
        $product = $this->publicProduct();

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertDontSee('product-marble-tile-square.jpg')
            ->assertDontSee('interior-marble-corridor.jpg');
    }

    public function test_cart_brand_attribute_is_a_plain_name_not_json(): void
    {
        $brand = Brand::query()->create([
            'name' => ['az' => 'Grohe'],
            'slug' => 'grohe-'.uniqid(),
        ]);

        $product = $this->publicProduct(['brand_id' => $brand->id]);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('data-cart-brand="Grohe"', false)
            ->assertDontSee('data-cart-brand="{', false);
    }

    public function test_cart_brand_attribute_escapes_quotes_and_apostrophes(): void
    {
        $brand = Brand::query()->create([
            'name' => ['az' => 'O\'Brien "Premium" Tiles'],
            'slug' => 'obrien-'.uniqid(),
        ]);

        $product = $this->publicProduct(['brand_id' => $brand->id]);

        $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();

        // The raw quote must never close the attribute early.
        $this->assertStringNotContainsString('data-cart-brand="O\'Brien "Premium"', $html);
        $this->assertStringContainsString('data-cart-brand="O&#039;Brien &quot;Premium&quot; Tiles"', $html);
        $this->assertStringNotContainsString('data-cart-brand="{', $html);
    }

    public function test_product_without_a_brand_emits_no_json_and_no_empty_brand(): void
    {
        $product = $this->publicProduct(['brand_id' => null]);

        $html = $this->get('/product/'.$product->slug)->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-cart-brand="[^"]+"/', $html);
        $this->assertStringNotContainsString('data-cart-brand="{', $html);
        $this->assertStringNotContainsString('data-cart-brand=""', $html);
    }

    // ─── Listing badges ───────────────────────────────────────

    public function test_out_of_stock_card_does_not_show_an_in_stock_badge(): void
    {
        $category = $this->category();
        $this->publicProduct(['name' => ['az' => 'Bitmiş məhsul'], 'stock' => 0, 'category_id' => $category->id]);

        $this->get('/catalog')
            ->assertOk()
            ->assertSee('Bitmiş məhsul')
            ->assertSee('Stokda yoxdur')
            ->assertDontSee('Stokda var');
    }

    public function test_in_stock_card_shows_an_in_stock_badge(): void
    {
        $this->publicProduct(['name' => ['az' => 'Mövcud məhsul'], 'stock' => 12]);

        $this->get('/catalog')
            ->assertOk()
            ->assertSee('Mövcud məhsul')
            ->assertSee('Stokda var');
    }

    public function test_homepage_renders_no_fake_product_or_specialist_cards(): void
    {
        $this->get('/')
            ->assertOk()
            // The old @for-loop fallback rendered these four times over.
            ->assertDontSee('340 məhsul')
            ->assertDontSee('Keramik kafel 60×60, mat — açıq boz');
    }

    /**
     * The blog grid had its own @for fallback that invented four articles (fake
     * reading times, every card linking to the same generic route) whenever no
     * post was published. An empty grid must stay empty.
     */
    public function test_homepage_blog_grid_invents_no_posts_when_there_are_none(): void
    {
        $this->assertSame(0, \App\Models\BlogPost::query()->count());

        $html = $this->get('/')->assertOk()->getContent();

        $blogGrid = \Illuminate\Support\Str::between($html, 'id="blogGrid"', '</div>');

        $this->assertStringNotContainsString('x-post', $blogGrid);
        $this->assertStringNotContainsString('class="post', $blogGrid);
        $this->assertStringContainsString('yoxdur', $blogGrid);
    }

    // ─── Özəlliklər (feature bullets) ─────────────────────────

    public function test_product_without_features_shows_the_shared_defaults(): void
    {
        $product = $this->publicProduct();

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();

        $this->assertStringContainsString('pd-feats', $html);
        $this->assertStringContainsString((string) t('product.features.default_1'), $html);
        $this->assertStringContainsString((string) t('product.features.default_3'), $html);
    }

    public function test_seller_features_replace_the_defaults(): void
    {
        $product = $this->publicProduct();
        $product->update(['features' => ['10 il zəmanət', 'Sertifikatlı material']]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();

        $this->assertStringContainsString('10 il zəmanət', $html);
        $this->assertStringContainsString('Sertifikatlı material', $html);
        $this->assertStringNotContainsString((string) t('product.features.default_1'), $html);
    }

    public function test_seller_form_stores_features_one_per_line(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Özəllikli məhsul',
            'category_id' => $category->id,
            'price' => 10,
            'stock' => 1,
            'publish' => 0,
            'features' => "  10 il zəmanət \n\n Sertifikatlı material \n",
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(
            ['10 il zəmanət', 'Sertifikatlı material'],
            $seller->products()->latest('id')->first()->features,
        );
    }

    public function test_empty_features_box_stores_null_so_defaults_apply(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Özəlliksiz məhsul',
            'category_id' => $category->id,
            'price' => 10,
            'stock' => 1,
            'publish' => 0,
            'features' => "   \n  ",
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNull($seller->products()->latest('id')->first()->features);
    }

    // ─── Recommendation blocks ────────────────────────────────

    public function test_accessories_picked_in_admin_render_in_the_fbt_block(): void
    {
        $product = $this->publicProduct();
        $accessory = $this->publicProduct(['name' => ['az' => 'Aksessuar A']]);
        $product->accessoryProducts()->attach($accessory->id);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();

        $this->assertStringContainsString('id="fbtCards"', $html);
        $this->assertStringContainsString('Aksessuar A', $html);
    }

    public function test_hidden_accessory_is_not_offered_and_empties_the_block(): void
    {
        $product = $this->publicProduct();
        $accessory = $this->publicProduct(['name' => ['az' => 'Gizli aksessuar']]);
        $accessory->update(['is_visible' => false]);
        $product->accessoryProducts()->attach($accessory->id);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('Gizli aksessuar', $html);
        $this->assertStringNotContainsString('id="fbtCards"', $html);
    }

    public function test_fbt_block_is_absent_without_accessories(): void
    {
        $product = $this->publicProduct();

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertDontSee('id="fbtCards"', false);
    }

    public function test_similar_products_section_is_absent_without_siblings(): void
    {
        // publicProduct() mints a fresh category per product, so this one is alone.
        $product = $this->publicProduct();

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertDontSee('id="simGrid"', false);
    }

    public function test_similar_products_section_renders_with_a_sibling(): void
    {
        $product = $this->publicProduct();
        $this->publicProduct([
            'category_id' => $product->category_id,
            'name' => ['az' => 'Qonşu məhsul'],
        ]);

        $html = $this->get(route('product.show', $product->slug))->assertOk()->getContent();

        $this->assertStringContainsString('id="simGrid"', $html);
        $this->assertStringContainsString('Qonşu məhsul', $html);
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
            'name' => ['az' => 'Məhsul'],
            'description' => ['az' => ''],
            'slug' => 'p-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => false,
            'is_approved' => false,
        ], $overrides));
    }

    /** A product a visitor can actually open (visible + approved). */
    private function publicProduct(array $overrides = []): Product
    {
        $seller = User::factory()->seller()->create();

        return $this->product($seller, array_merge([
            'is_visible' => true,
            'is_approved' => true,
        ], $overrides));
    }

    private function image(Product $product): ProductImage
    {
        return ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/fake.jpg',
            'is_main' => true,
            'sort_order' => 0,
        ]);
    }
}
