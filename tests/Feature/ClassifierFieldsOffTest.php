<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AttributeComplexity;
use App\Enums\AttributeFieldType;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Application;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\LaminateSpec;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Laminate\LaminateCatalog;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Class-specific fields switched off (config catalog.classifier_fields = false):
 * every product, in every class — laminate included — is added with the same
 * plain form, nothing class-specific is asked or required, and values already
 * stored on a product survive an edit.
 */
class ClassifierFieldsOffTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Category $section;

    private Category $group;

    private SubCategory $class;

    private SubCategory $laminate;

    private Attribute $required;

    protected function setUp(): void
    {
        parent::setUp();

        config(['catalog.classifier_fields' => false]);
        LaminateCatalog::flush();
        Storage::fake('public');

        $this->seller = User::factory()->seller()->create();

        $this->section = Category::create(['name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler', 'is_active' => true]);
        $this->group = Category::create(['name' => ['az' => 'Polimer'], 'slug' => 'polimer', 'parent_id' => $this->section->id, 'is_active' => true]);
        $this->class = SubCategory::create(['category_id' => $this->group->id, 'name' => ['az' => 'Parket'], 'slug' => 'parket', 'is_active' => true]);
        $this->laminate = SubCategory::create(['category_id' => $this->group->id, 'name' => ['az' => 'Laminat'], 'slug' => LaminateCatalog::CLASS_SLUG, 'is_active' => true]);

        // A basic required attribute: with the fields on, it blocks a save.
        $this->required = Attribute::create([
            'name' => ['az' => 'Sinif'], 'slug' => 'sinif-test',
            'field_type' => AttributeFieldType::Dropdown,
            'complexity' => AttributeComplexity::Basic,
            'is_active' => true,
        ]);
        AttributeOption::create(['attribute_id' => $this->required->id, 'value' => ['az' => 'AC4'], 'slug' => 'ac4-test', 'sort_order' => 1]);
        $this->class->attributes()->attach($this->required->id, ['is_required' => true, 'sort_order' => 1]);

        $app = Application::create(['name' => ['az' => 'Mətbəx'], 'slug' => 'metbex-test', 'is_active' => true]);
        $this->class->applications()->attach($app->id);
    }

    private function payload(SubCategory $class, array $extra = []): array
    {
        return array_merge([
            'name' => 'Sadə məhsul',
            'section_id' => $this->section->id,
            'category_id' => $this->group->id,
            'sub_category_id' => $class->id,
            'price' => '18.50',
            'stock' => 40,
            'unit' => 'piece',
            'description' => 'Qısa açıqlama',
            'publish' => 1,
            'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra);
    }

    public function test_the_form_definition_asks_nothing_class_specific(): void
    {
        foreach ([$this->class, $this->laminate] as $class) {
            $this->actingAs($this->seller)
                ->getJson('/business/api/sub-categories/'.$class->id.'/form')
                ->assertOk()
                ->assertJsonPath('fields', [])
                ->assertJsonPath('applications', [])
                ->assertJsonMissingPath('laminate');
        }
    }

    public function test_a_product_publishes_without_the_required_class_field(): void
    {
        $this->actingAs($this->seller)
            ->post('/business/products', $this->payload($this->class), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $product = Product::firstOrFail();
        $this->assertTrue((bool) $product->is_visible);
        $this->assertSame(0, $product->attributeValues()->count());
        $this->assertSame(0, $product->applications()->count());
        $this->assertSame(1, $product->images()->count());
    }

    public function test_laminate_goes_through_the_plain_form(): void
    {
        $this->actingAs($this->seller)
            ->post('/business/products', $this->payload($this->laminate, [
                'unit' => 'piece',
                'color' => 'Palıd',
            ]), ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('missing_fields');

        $product = Product::firstOrFail();
        $this->assertSame('piece', $product->unit);
        $this->assertSame('Palıd', $product->specifications['color'] ?? null);
        $this->assertFalse(LaminateSpec::where('product_id', $product->id)->exists());
    }

    public function test_an_edit_keeps_the_values_already_stored(): void
    {
        $this->actingAs($this->seller)->post('/business/products', $this->payload($this->class), ['Accept' => 'application/json'])->assertOk();
        $product = Product::firstOrFail();

        ProductAttributeValue::create([
            'product_id' => $product->id,
            'attribute_id' => $this->required->id,
            'attribute_option_id' => $this->required->options()->value('id'),
        ]);

        $this->actingAs($this->seller)
            ->put('/business/products/'.$product->id, $this->payload($this->class, [
                'name' => 'Sadə məhsul 2',
                'images' => [],
                'kept_image_ids' => $product->images()->pluck('id')->all(),
            ]), ['Accept' => 'application/json'])
            ->assertOk();

        $this->assertSame('Sadə məhsul 2', $product->fresh()->name);
        $this->assertSame(1, $product->attributeValues()->count());
    }

    public function test_admin_creates_a_product_without_class_fields(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        Livewire::test(CreateProduct::class)
            ->assertFormFieldIsHidden('applications')
            ->fillForm([
                'name' => ['az' => 'Admin məhsulu'],
                'slug' => 'admin-mehsulu',
                'user_id' => $this->seller->id,
                'category_id' => $this->group->id,
                'sub_category_id' => $this->class->id,
                'price' => '25',
                'stock' => 10,
                'is_visible' => true,
                'is_approved' => true,
            ])
            ->assertDontSee('Klassifikator xüsusiyyətləri')
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('slug', 'admin-mehsulu')->firstOrFail();
        $this->assertSame($this->class->id, $product->sub_category_id);
        $this->assertSame(0, $product->attributeValues()->count());
    }
}
