<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Public store page (/store/{seller}) + the "Mağazaya keç" button on product pages.
 * Only active sellers get a public page; the product list is visible+approved only.
 */
class StorePageTest extends TestCase
{
    use RefreshDatabase;

    // ─── Store page access ────────────────────────────────────

    public function test_active_seller_store_page_shows_company_name_and_products(): void
    {
        $seller = User::factory()->seller()->create();
        SellerProfile::create([
            'user_id' => $seller->id,
            'brand_name' => 'Baldwin Trading',
            'legal_name' => 'Baldwin MMC',
        ]);
        $this->publicProduct($seller, ['name' => ['az' => 'Mağaza kafeli 60×60']]);

        $this->get('/store/'.$seller->id)
            ->assertOk()
            ->assertSee('Baldwin Trading')
            ->assertSee('Mağaza kafeli 60×60')
            ->assertSee('class="pcard', false);
    }

    public function test_store_page_works_for_a_seller_without_a_profile(): void
    {
        $seller = User::factory()->seller()->create(['name' => 'Profilsiz Satıcı']);
        $this->publicProduct($seller);

        // No SellerProfile row yet — the page falls back to the account name.
        $this->get('/store/'.$seller->id)
            ->assertOk()
            ->assertSee('Profilsiz Satıcı');
    }

    public function test_pending_seller_has_no_public_store(): void
    {
        $seller = User::factory()->seller()->pending()->create();

        $this->get('/store/'.$seller->id)->assertNotFound();
    }

    public function test_non_seller_user_has_no_public_store(): void
    {
        $buyer = User::factory()->buyer()->create();

        $this->get('/store/'.$buyer->id)->assertNotFound();

        $this->get('/store/999999')->assertNotFound();
    }

    public function test_store_page_lists_only_visible_and_approved_products(): void
    {
        $seller = User::factory()->seller()->create();
        $this->publicProduct($seller, ['name' => ['az' => 'Görünən məhsul']]);
        $this->product($seller, ['name' => ['az' => 'Gizli qaralama'], 'is_visible' => false, 'is_approved' => false]);
        $this->product($seller, ['name' => ['az' => 'Təsdiqsiz məhsul'], 'is_visible' => true, 'is_approved' => false]);

        $this->get('/store/'.$seller->id)
            ->assertOk()
            ->assertSee('Görünən məhsul')
            ->assertDontSee('Gizli qaralama')
            ->assertDontSee('Təsdiqsiz məhsul');
    }

    // ─── "Mağazaya keç" on the product page ───────────────────

    public function test_product_page_links_to_the_sellers_store(): void
    {
        $seller = User::factory()->seller()->create();
        $product = $this->publicProduct($seller);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('Mağazaya keç')
            ->assertSee(route('store.show', $seller), false);
    }

    public function test_store_button_is_hidden_when_the_seller_is_not_eligible(): void
    {
        // An admin-owned product: the owner has no public store page, so the
        // button must not render (a dead 404 link would be worse than none).
        $admin = User::factory()->admin()->create();
        $product = $this->publicProduct($admin);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertDontSee('Mağazaya keç')
            ->assertDontSee('/store/'.$admin->id);
    }

    // ─── Specs tab fallback (requirement #5) ──────────────────

    public function test_product_without_specifications_shows_no_specs_tab(): void
    {
        $product = $this->publicProduct(User::factory()->seller()->create(), [
            'specifications' => null,
        ]);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertDontSee('data-pane="specs"', false)
            ->assertDontSee('Bu məhsul üçün texniki xüsusiyyət əlavə edilməyib');
    }

    public function test_product_with_only_empty_specification_values_shows_no_specs_tab(): void
    {
        $product = $this->publicProduct(User::factory()->seller()->create(), [
            'specifications' => ['color' => '', 'material' => null, 'dimensions' => '   '],
        ]);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertDontSee('data-pane="specs"', false);
    }

    public function test_product_with_specifications_keeps_the_specs_tab(): void
    {
        $product = $this->publicProduct(User::factory()->seller()->create(), [
            'specifications' => ['dimensions' => '30×60 sm', 'color' => 'Xrom'],
        ]);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('data-pane="specs"', false)
            ->assertSee('30×60 sm')
            ->assertSee('Xrom');
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
    private function publicProduct(User $seller, array $overrides = []): Product
    {
        return $this->product($seller, array_merge([
            'is_visible' => true,
            'is_approved' => true,
        ], $overrides));
    }
}
