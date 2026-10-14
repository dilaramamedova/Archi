<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttributeComplexity;
use App\Enums\AttributeFieldType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\SpecialistProfile;
use App\Models\SpecialistSpecialty;
use App\Models\SubCategory;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Str;
use Tests\Concerns\CommitsToDatabase;
use Tests\TestCase;

/**
 * Regressions found driving /catalog, /product and /search by hand:
 *
 *  - array-valued query parameters (`?sort[]=cheap`) returned a 500;
 *  - the search page died on the lazy-loading guard as soon as a specialist
 *    matched, because `specialty` was not eager loaded;
 *  - the size / surface filter chips submitted values that match no attribute
 *    option, so they silently left the listing unfiltered;
 *  - the product page's heart always rendered "not favourited", so the first
 *    click on an already-favourited product removed it from the wishlist.
 */
class CatalogRobustnessTest extends TestCase
{
    use CommitsToDatabase;

    /** Every scalar filter parameter, handed an array instead. */
    public function test_array_valued_query_parameters_do_not_break_the_catalog(): void
    {
        $this->product();

        foreach (['q', 'sort', 'page', 'category', 'section', 'group', 'class', 'brand', 'size', 'surface', 'min_price', 'max_price'] as $key) {
            $this->get('/catalog?'.$key.'[]=x')
                ->assertOk()
                ->assertSee('cat-grid', false);
        }
    }

    public function test_array_valued_query_does_not_break_the_search_endpoints(): void
    {
        $this->get('/search?q[]=x')->assertOk();
        $this->get('/api/search?q[]=x')->assertOk()->assertJsonStructure(['suggests', 'products', 'masters', 'total']);
    }

    public function test_malformed_price_and_page_values_still_render_the_catalog(): void
    {
        $this->product();

        $this->get('/catalog?min_price=abc&max_price=xyz')->assertOk();
        $this->get('/catalog?page=abc')->assertOk();
        $this->get('/catalog?page=99999')->assertOk();
        $this->get('/catalog?sort=nonsense')->assertOk();
    }

    public function test_search_page_renders_when_a_specialist_matches(): void
    {
        $this->specialist('Elvin Məmmədov', 'Kafelçi');

        $this->get('/search?'.http_build_query(['q' => 'Kafelçi']))
            ->assertOk()
            ->assertSeeText('Kafelçi');
    }

    public function test_surface_chip_filters_on_a_real_attribute_option(): void
    {
        [$matte, $glossy] = $this->surfaceProducts();

        $this->get('/catalog?surface=mat')
            ->assertOk()
            ->assertSeeText($matte->name)
            ->assertDontSeeText($glossy->name);
    }

    public function test_product_page_shows_an_already_favourited_product_as_favourited(): void
    {
        $buyer = User::factory()->create();
        $product = $this->product();
        Wishlist::create(['user_id' => $buyer->id, 'product_id' => $product->id]);

        $this->actingAs($buyer)
            ->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('id="pdWish" data-liked="true"', false);
    }

    /** @return array{0: Product, 1: Product} */
    private function surfaceProducts(): array
    {
        $attribute = Attribute::create([
            'name' => ['az' => 'Səth'],
            'slug' => 'seth-'.uniqid(),
            'field_type' => AttributeFieldType::Dropdown,
            'complexity' => AttributeComplexity::Basic,
            'is_active' => true,
        ]);
        $mat = AttributeOption::create(['attribute_id' => $attribute->id, 'value' => ['az' => 'mat'], 'slug' => 'mat']);
        $parlaq = AttributeOption::create(['attribute_id' => $attribute->id, 'value' => ['az' => 'parlaq'], 'slug' => 'parlaq']);

        $matte = $this->product(['name' => ['az' => 'Mat kafel']]);
        $glossy = $this->product(['name' => ['az' => 'Parlaq kafel']]);

        ProductAttributeValue::create([
            'product_id' => $matte->id, 'attribute_id' => $attribute->id, 'attribute_option_id' => $mat->id,
        ]);
        ProductAttributeValue::create([
            'product_id' => $glossy->id, 'attribute_id' => $attribute->id, 'attribute_option_id' => $parlaq->id,
        ]);

        return [$matte, $glossy];
    }

    private function product(array $overrides = []): Product
    {
        $seller = User::factory()->seller()->create();

        $category = Category::create([
            'name' => ['az' => 'Kateqoriya'],
            'slug' => 'cat-'.uniqid(),
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'sub_category_id' => SubCategory::create([
                'name' => ['az' => 'Sinif'],
                'slug' => 'class-'.uniqid(),
                'category_id' => $category->id,
                'is_active' => true,
            ])->id,
            'name' => ['az' => 'Məhsul'],
            'description' => ['az' => ''],
            'slug' => 'p-'.uniqid(),
            'price' => 10,
            'stock' => 10,
            'is_visible' => true,
            'is_approved' => true,
        ], $overrides));
    }

    private function specialist(string $name, string $craft): SpecialistProfile
    {
        $user = User::factory()->master()->create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? '',
        ]);

        $specialty = SpecialistSpecialty::firstOrCreate(
            ['slug' => Str::slug($craft)],
            ['name' => $craft, 'is_active' => true],
        );

        return SpecialistProfile::create([
            'user_id' => $user->id,
            'specialist_specialty_id' => $specialty->id,
            'craft' => $craft,
            'city' => 'Bakı',
        ]);
    }
}
