<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Seller product form: creatable brand combobox, category → subcategory chain and
 * the free-form key-value attribute rows (owner requirements #1 and #4).
 */
class BusinessProductFormTest extends TestCase
{
    use RefreshDatabase;

    // ─── Brand ────────────────────────────────────────────────

    public function test_creating_a_product_with_a_new_brand_name_creates_and_links_the_brand(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Laminat 8mm',
            'category_id' => $this->category()->id,
            'price' => 19.90,
            'stock' => 30,
            'new_brand' => 'Kronospan',
        ], ['Accept' => 'application/json'])->assertOk()->assertJson(['success' => true]);

        $brand = Brand::where('slug', 'kronospan')->first();
        $this->assertNotNull($brand);
        $this->assertTrue($brand->is_active);
        // Seller-created brands must not pollute catalog filters until admin opts in.
        $this->assertFalse($brand->show_in_filters);
        // Name is stored for every locale so the brand renders in az/ru/en.
        $this->assertSame('Kronospan', $brand->getTranslation('name', 'az'));
        $this->assertSame('Kronospan', $brand->getTranslation('name', 'ru'));
        $this->assertSame('Kronospan', $brand->getTranslation('name', 'en'));

        $this->assertSame($brand->id, Product::where('user_id', $seller->id)->first()->brand_id);
    }

    public function test_submitting_the_same_new_brand_name_twice_reuses_the_brand(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();

        foreach (['Kale', 'KALE'] as $i => $typed) {
            $this->actingAs($seller)->post('/business/products', [
                'name' => 'Məhsul '.$i,
                'category_id' => $category->id,
                'price' => 5,
                'stock' => 1,
                'new_brand' => $typed,
            ], ['Accept' => 'application/json'])->assertOk();
        }

        $this->assertSame(1, Brand::count());
        $this->assertSame(2, Product::where('brand_id', Brand::first()->id)->count());
    }

    public function test_existing_brand_id_wins_over_new_brand_text(): void
    {
        $seller = User::factory()->seller()->create();
        $brand = $this->brand('Egger');

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Parket',
            'category_id' => $this->category()->id,
            'price' => 40,
            'stock' => 4,
            'brand_id' => $brand->id,
            'new_brand' => 'Egger', // stale combobox text must not create a duplicate
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame(1, Brand::count());
        $this->assertSame($brand->id, Product::first()->brand_id);
    }

    public function test_unknown_brand_id_is_rejected(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->postJson('/business/products', [
            'name' => 'X',
            'category_id' => $this->category()->id,
            'price' => 1,
            'stock' => 1,
            'brand_id' => 999999,
        ])->assertStatus(422)->assertJsonValidationErrors('brand_id');
    }

    // ─── Category / subcategory ───────────────────────────────

    public function test_product_saves_with_a_subcategory_of_the_chosen_category(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $sub = $this->subCategory($category, 'Metlax');

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Metlax kafel',
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'price' => 12,
            'stock' => 7,
        ], ['Accept' => 'application/json'])->assertOk();

        $product = Product::where('user_id', $seller->id)->first();
        $this->assertSame($category->id, $product->category_id);
        $this->assertSame($sub->id, $product->sub_category_id);
    }

    public function test_subcategory_of_another_category_is_rejected(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $other = $this->category();
        $foreignSub = $this->subCategory($other, 'Yad alt kateqoriya');

        $this->actingAs($seller)->postJson('/business/products', [
            'name' => 'Səhv alt kateqoriya',
            'category_id' => $category->id,
            'sub_category_id' => $foreignSub->id,
            'price' => 12,
            'stock' => 7,
        ])->assertStatus(422)->assertJsonValidationErrors('sub_category_id');
    }

    public function test_parent_category_alone_still_saves(): void
    {
        // Backward compatibility: categories without subcategories keep working.
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Yalnız kateqoriya',
            'category_id' => $this->category()->id,
            'price' => 3,
            'stock' => 2,
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertNull(Product::first()->sub_category_id);
    }

    // ─── Key-value attributes ─────────────────────────────────

    public function test_attributes_are_saved_into_specifications_alongside_fixed_fields(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Kafel 60×60',
            'category_id' => $this->category()->id,
            'price' => 23.90,
            'stock' => 148,
            'material' => 'Keramoqranit',
            'attributes' => [
                ['key' => 'Çəki', 'value' => '5 kq'],
                ['key' => 'Səth', 'value' => 'Mat'],
                ['key' => '', 'value' => ''], // empty row is stripped, not an error
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $specs = Product::first()->specifications;
        // Same flat key=>value format the Filament KeyValue field edits.
        $this->assertSame('Keramoqranit', $specs['material']);
        $this->assertSame('5 kq', $specs['Çəki']);
        $this->assertSame('Mat', $specs['Səth']);
        $this->assertArrayNotHasKey('', $specs);
    }

    public function test_half_filled_attribute_row_is_rejected(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->postJson('/business/products', [
            'name' => 'Yarımçıq sətir',
            'category_id' => $this->category()->id,
            'price' => 1,
            'stock' => 1,
            'attributes' => [
                ['key' => 'Çəki', 'value' => ''],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors('attributes');
    }

    public function test_fixed_field_wins_a_key_collision_with_a_custom_attribute(): void
    {
        $seller = User::factory()->seller()->create();

        $this->actingAs($seller)->post('/business/products', [
            'name' => 'Kolliziya',
            'category_id' => $this->category()->id,
            'price' => 1,
            'stock' => 1,
            'color' => 'Ağ',
            'attributes' => [
                ['key' => 'color', 'value' => 'Qara'],
            ],
        ], ['Accept' => 'application/json'])->assertOk();

        $this->assertSame('Ağ', Product::first()->specifications['color']);
    }

    public function test_update_keeps_brand_subcategory_and_attributes_in_sync(): void
    {
        $seller = User::factory()->seller()->create();
        $category = $this->category();
        $sub = $this->subCategory($category, 'Divar kafeli');

        $product = Product::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'name' => ['az' => 'Köhnə məhsul'],
            'description' => ['az' => ''],
            'slug' => 'kohne-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => false,
            'is_approved' => false,
            'specifications' => ['material' => 'Keramika', 'Çəki' => '2 kq'],
        ]);

        $this->actingAs($seller)->putJson('/business/products/'.$product->id, [
            'name' => 'Yenilənmiş məhsul',
            'category_id' => $category->id,
            'sub_category_id' => $sub->id,
            'new_brand' => 'Vitra',
            'price' => 15,
            'stock' => 9,
            'attributes' => [
                ['key' => 'Səth', 'value' => 'Parlaq'],
            ],
        ])->assertOk();

        $product->refresh();
        $this->assertSame($sub->id, $product->sub_category_id);
        $this->assertSame('Vitra', (string) $product->brand->getTranslation('name', 'az'));
        // Attribute rows fully define the custom part of specifications on save:
        // the removed "Çəki" row is gone, the new "Səth" row is there.
        $this->assertSame('Parlaq', $product->specifications['Səth']);
        $this->assertArrayNotHasKey('Çəki', $product->specifications);
    }

    // ─── Helpers (mirroring BusinessCabinetTest conventions) ──

    public function test_create_form_keeps_every_specification_layer_in_one_card(): void
    {
        $seller = User::factory()->seller()->create();

        $html = $this->actingAs($seller)->get('/business/products/create')->assertOk()->getContent();

        // The card hosts the generic fields and the free-form rows, so it may not
        // start hidden the way it did when it only held class attributes.
        $this->assertDoesNotMatchRegularExpression('/id="classAttrsCard"[^>]*display:\s*none/', $html);

        $cardStart = strpos($html, 'id="classAttrsCard"');
        $genericStart = strpos($html, 'id="genericAttrs"');
        $rowsStart = strpos($html, 'id="attrRows"');
        $this->assertNotFalse($cardStart);
        $this->assertGreaterThan($cardStart, $genericStart, 'generic fields must live inside the specifications card');
        $this->assertGreaterThan($genericStart, $rowsStart, 'free-form rows must come after the generic fields');

        // Each generic input exists exactly once — the whole point of the merge.
        foreach (['pDim', 'pMaterial', 'pColor', 'pCountry'] as $id) {
            $this->assertSame(1, substr_count($html, 'id="'.$id.'"'), $id.' must appear once');
        }

        // …and each carries the term its class-field counterpart is matched against,
        // including the look-alikes that must NOT count as a duplicate.
        $this->assertStringContainsString('data-generic-match="ölçü|qabarit"', $html);
        $this->assertStringContainsString('data-generic-match="material"', $html);
        $this->assertStringContainsString('data-generic-match="rəng"', $html);
        $this->assertStringContainsString('data-generic-match="ölkə"', $html);
        $this->assertStringContainsString('göz ölçüsü', $html);
        $this->assertStringContainsString('rəng temperaturu', $html);
    }

    public function test_class_form_endpoint_sends_a_locale_independent_match_key(): void
    {
        $seller = User::factory()->seller()->create();
        $subCategory = $this->subCategory($this->category(), 'Divan');

        $attribute = \App\Models\Attribute::query()->create([
            'name' => ['az' => 'Qabaritlər (U×E×H)', 'ru' => 'Габариты', 'en' => 'Dimensions'],
            'slug' => 'qabaritler-'.uniqid(),
            'field_type' => \App\Enums\AttributeFieldType::Text,
            'complexity' => \App\Enums\AttributeComplexity::Basic,
            'is_active' => true,
        ]);
        $subCategory->attributes()->attach($attribute, ['sort_order' => 0]);

        // The generic-field rule compares against this key, so it must stay
        // Azerbaijani even when the seller browses in another language.
        session(['locale' => 'en']);

        $field = $this->actingAs($seller)
            ->getJson("/business/api/sub-categories/{$subCategory->id}/form")
            ->assertOk()
            ->json('fields.0');

        $this->assertSame('Dimensions', $field['name']);
        $this->assertSame('qabaritlər (u×e×h)', $field['match']);
    }

    private function category(): Category
    {
        return Category::query()->create([
            'name' => ['az' => 'Kafel'],
            'slug' => 'kafel-'.uniqid(),
            'is_active' => true,
        ]);
    }

    private function subCategory(Category $category, string $name): SubCategory
    {
        return SubCategory::query()->create([
            'category_id' => $category->id,
            'name' => ['az' => $name],
            'is_active' => true,
        ]);
    }

    private function brand(string $name): Brand
    {
        return Brand::query()->create([
            'name' => ['az' => $name, 'ru' => $name, 'en' => $name],
            'slug' => \Illuminate\Support\Str::slug($name),
            'is_active' => true,
            'show_in_filters' => false,
        ]);
    }
}
