<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Catalog classifier EAV on the seller form: the Bölmə → Qrup → Sinif cascade,
 * the class form-definition endpoint, attrs[] validation/persistence into
 * product_attribute_values and the Tətbiq sahəsi (applications) sync.
 *
 * MVP contract under test: `is_required` blocks only complexity=basic fields;
 * professional required fields never block a save.
 */
class ProductClassAttributesTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Category $section;

    private Category $group;

    private SubCategory $class;

    private Attribute $dropdown;   // basic, required

    private Attribute $multi;      // basic

    private Attribute $numeric;    // basic, unit mm

    private Attribute $range;      // professional, unit kg

    private Attribute $proText;    // professional, required (must NOT block)

    protected function setUp(): void
    {
        parent::setUp();

        $this->seller = User::factory()->seller()->create();

        $this->section = Category::create(['name' => ['az' => 'Döşəmə'], 'slug' => 'dosheme', 'is_active' => true]);
        $this->group = Category::create(['name' => ['az' => 'Polimer örtüklər'], 'slug' => 'polimer', 'parent_id' => $this->section->id, 'is_active' => true]);
        $this->class = SubCategory::create(['category_id' => $this->group->id, 'name' => ['az' => 'Laminat'], 'slug' => 'laminat', 'is_active' => true]);

        $this->dropdown = $this->attribute('Sinif', 'dropdown', 'basic', ['AC3', 'AC4', 'AC5']);
        $this->multi = $this->attribute('Otaq', 'multiselect', 'basic', ['Mətbəx', 'Hamam', 'Qonaq otağı']);
        $this->numeric = $this->attribute('Qalınlıq', 'numeric', 'basic');
        $this->range = $this->attribute('İstismar temperaturu', 'range', 'professional');
        $this->proText = $this->attribute('Sertifikat', 'text', 'professional');

        $this->class->attributes()->attach([
            $this->dropdown->id => ['is_required' => true, 'sort_order' => 1],
            $this->multi->id => ['sort_order' => 2],
            $this->numeric->id => ['unit' => 'mm', 'sort_order' => 3],
            $this->range->id => ['unit' => '°C', 'sort_order' => 4],
            $this->proText->id => ['is_required' => true, 'sort_order' => 5],
        ]);
    }

    private function attribute(string $name, string $type, string $complexity, array $options = []): Attribute
    {
        $attribute = Attribute::create([
            'name' => ['az' => $name],
            'slug' => str_replace(' ', '-', mb_strtolower($name)).'-'.uniqid(),
            'field_type' => $type,
            'complexity' => $complexity,
            'tooltip' => ['az' => $name === 'Sinif' ? 'Aşınma davamlılığı sinfi' : ''],
            'is_active' => true,
        ]);

        foreach ($options as $i => $value) {
            AttributeOption::create([
                'attribute_id' => $attribute->id,
                'value' => ['az' => $value],
                'slug' => 'opt-'.uniqid(),
                'sort_order' => $i,
            ]);
        }

        return $attribute;
    }

    private function application(string $name): Application
    {
        return Application::create(['name' => ['az' => $name], 'slug' => 'app-'.uniqid(), 'is_active' => true]);
    }

    /** Valid base payload for the create endpoint. */
    private function payload(array $extra = []): array
    {
        return array_merge([
            'name' => 'Laminat 8mm',
            'section_id' => $this->section->id,
            'category_id' => $this->group->id,
            'sub_category_id' => $this->class->id,
            'price' => 19.90,
            'stock' => 30,
        ], $extra);
    }

    private function optionId(Attribute $attribute, int $index = 0): int
    {
        return $attribute->options()->orderBy('sort_order')->get()[$index]->id;
    }

    // ─── Form definition endpoint ─────────────────────────────

    public function test_form_definition_endpoint_returns_fields_and_applications(): void
    {
        $app = $this->application('Mətbəx üçün');
        $this->class->applications()->attach($app->id);

        $res = $this->actingAs($this->seller)
            ->getJson('/business/api/sub-categories/'.$this->class->id.'/form')
            ->assertOk()
            ->assertJsonPath('id', $this->class->id)
            ->assertJsonPath('applications.0.id', $app->id)
            ->assertJsonCount(5, 'fields');

        $fields = collect($res->json('fields'))->keyBy('id');
        $this->assertTrue($fields[$this->dropdown->id]['required']);
        $this->assertSame('basic', $fields[$this->dropdown->id]['complexity']);
        $this->assertSame('Aşınma davamlılığı sinfi', $fields[$this->dropdown->id]['tooltip']);
        $this->assertCount(3, $fields[$this->dropdown->id]['options']);
        $this->assertSame('mm', $fields[$this->numeric->id]['unit']);
        $this->assertNull($fields[$this->numeric->id]['tooltip']);
        $this->assertSame('professional', $fields[$this->range->id]['complexity']);
        // Fields arrive in pivot sort_order.
        $this->assertSame($this->dropdown->id, $res->json('fields.0.id'));
    }

    public function test_form_definition_endpoint_requires_a_seller_session(): void
    {
        $this->getJson('/business/api/sub-categories/'.$this->class->id.'/form')->assertUnauthorized();
    }

    // ─── Create with class attributes ─────────────────────────

    public function test_create_persists_every_field_type_into_product_attribute_values(): void
    {
        $acFour = $this->optionId($this->dropdown, 1);
        $rooms = [$this->optionId($this->multi, 0), $this->optionId($this->multi, 2)];

        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [
                $this->dropdown->id => $acFour,
                $this->multi->id => $rooms,
                $this->numeric->id => '8.5',
                $this->range->id => ['min' => '-5', 'max' => '40'],
            ],
        ]))->assertOk()->assertJson(['success' => true]);

        $product = Product::where('user_id', $this->seller->id)->firstOrFail();
        $this->assertSame($this->group->id, $product->category_id);
        $this->assertSame($this->class->id, $product->sub_category_id);

        // dropdown → one option row
        $this->assertDatabaseHas('product_attribute_values', [
            'product_id' => $product->id, 'attribute_id' => $this->dropdown->id, 'attribute_option_id' => $acFour,
        ]);

        // multiselect → one row per chosen option
        $this->assertSame(2, $product->attributeValues()->where('attribute_id', $this->multi->id)->count());
        foreach ($rooms as $optionId) {
            $this->assertDatabaseHas('product_attribute_values', [
                'product_id' => $product->id, 'attribute_id' => $this->multi->id, 'attribute_option_id' => $optionId,
            ]);
        }

        // numeric (its unit lives on the pivot, only the value is stored)
        $numeric = $product->attributeValues()->where('attribute_id', $this->numeric->id)->firstOrFail();
        $this->assertSame(8.5, (float) $numeric->value_numeric);

        // range → value_min / value_max on a single row
        $range = $product->attributeValues()->where('attribute_id', $this->range->id)->firstOrFail();
        $this->assertSame(-5.0, (float) $range->value_min);
        $this->assertSame(40.0, (float) $range->value_max);
    }

    public function test_option_id_from_another_attribute_is_rejected(): void
    {
        $foreignOption = $this->optionId($this->multi); // belongs to Otaq, not Sinif

        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [$this->dropdown->id => $foreignOption],
        ]))->assertStatus(422)->assertJsonValidationErrors('attrs.'.$this->dropdown->id);

        $this->assertSame(0, Product::count());
    }

    public function test_missing_required_basic_attribute_is_rejected(): void
    {
        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [$this->numeric->id => '8'], // Sinif (required basic) left empty
        ]))->assertStatus(422)->assertJsonValidationErrors('attrs.'.$this->dropdown->id);
    }

    public function test_missing_required_professional_attribute_does_not_block(): void
    {
        // Sertifikat is required on the pivot but professional — MVP lets it pass.
        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [$this->dropdown->id => $this->optionId($this->dropdown)],
        ]))->assertOk()->assertJson(['success' => true]);
    }

    public function test_non_numeric_and_inverted_range_are_rejected(): void
    {
        $base = ['attrs' => [$this->dropdown->id => $this->optionId($this->dropdown)]];

        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => $base['attrs'] + [$this->numeric->id => 'qalın'],
        ]))->assertStatus(422)->assertJsonValidationErrors('attrs.'.$this->numeric->id);

        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => $base['attrs'] + [$this->range->id => ['min' => '50', 'max' => '10']],
        ]))->assertStatus(422)->assertJsonValidationErrors('attrs.'.$this->range->id);
    }

    // ─── Cascade validation ───────────────────────────────────

    public function test_group_of_another_section_is_rejected(): void
    {
        $otherSection = Category::create(['name' => ['az' => 'Santexnika'], 'slug' => 'santex', 'is_active' => true]);

        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'section_id' => $otherSection->id,
            'attrs' => [$this->dropdown->id => $this->optionId($this->dropdown)],
        ]))->assertStatus(422)->assertJsonValidationErrors('category_id');
    }

    public function test_legacy_root_category_as_its_own_section_still_saves(): void
    {
        // Backward compatibility: pre-classifier products point at a ROOT category.
        $this->actingAs($this->seller)->postJson('/business/products', [
            'name' => 'Köhnə məhsul',
            'section_id' => $this->section->id,
            'category_id' => $this->section->id,
            'price' => 5,
            'stock' => 2,
        ])->assertOk();
    }

    // ─── Applications ─────────────────────────────────────────

    public function test_applications_are_synced_and_cleared_via_presence_marker(): void
    {
        $kitchen = $this->application('Mətbəx üçün');
        $bath = $this->application('Hamam üçün');
        $this->class->applications()->attach([$kitchen->id, $bath->id]);

        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [$this->dropdown->id => $this->optionId($this->dropdown)],
            'applications' => [$kitchen->id, $bath->id],
        ]))->assertOk();

        $product = Product::firstOrFail();
        $this->assertEqualsCanonicalizing([$kitchen->id, $bath->id], $product->applications()->pluck('applications.id')->all());

        // Un-checking every chip clears the pivot — the marker signals the block rendered.
        $this->actingAs($this->seller)->putJson('/business/products/'.$product->id, $this->payload([
            'attrs' => [$this->dropdown->id => $this->optionId($this->dropdown)],
            'applications_present' => 1,
        ]))->assertOk();

        $this->assertSame(0, $product->applications()->count());
    }

    public function test_unknown_application_id_is_rejected(): void
    {
        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [$this->dropdown->id => $this->optionId($this->dropdown)],
            'applications' => [999999],
        ]))->assertStatus(422)->assertJsonValidationErrors('applications.0');
    }

    // ─── Edit round-trip ──────────────────────────────────────

    public function test_edit_page_exposes_stored_values_and_update_replaces_them(): void
    {
        $acThree = $this->optionId($this->dropdown, 0);
        $acFive = $this->optionId($this->dropdown, 2);
        $rooms = [$this->optionId($this->multi, 0), $this->optionId($this->multi, 1)];

        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [
                $this->dropdown->id => $acThree,
                $this->multi->id => $rooms,
                $this->numeric->id => '8',
            ],
        ]))->assertOk();

        $product = Product::firstOrFail();

        // The edit page hands the stored EAV values to the JS renderer.
        $html = $this->actingAs($this->seller)->get('/business/products/'.$product->id.'/edit')
            ->assertOk()->getContent();
        $this->assertStringContainsString('id="attrInitial"', $html);
        preg_match('/<script type="application\/json" id="attrInitial">(.*?)<\/script>/s', $html, $m);
        $initial = json_decode($m[1] ?? '', true);
        $this->assertIsArray($initial);
        $this->assertSame([$acThree], $initial['values'][(string) $this->dropdown->id]['options']);
        $this->assertEqualsCanonicalizing($rooms, $initial['values'][(string) $this->multi->id]['options']);
        $this->assertSame(8, $initial['values'][(string) $this->numeric->id]['numeric']);

        // Update: new dropdown pick, one room dropped, numeric emptied (row deleted).
        $this->actingAs($this->seller)->putJson('/business/products/'.$product->id, $this->payload([
            'attrs' => [
                $this->dropdown->id => $acFive,
                $this->multi->id => [$rooms[0]],
                $this->numeric->id => '',
            ],
        ]))->assertOk();

        $values = $product->attributeValues()->get();
        $this->assertSame($acFive, $values->firstWhere('attribute_id', $this->dropdown->id)->attribute_option_id);
        $this->assertSame(1, $values->where('attribute_id', $this->multi->id)->count());
        $this->assertNull($values->firstWhere('attribute_id', $this->numeric->id));
    }

    public function test_switching_the_class_wipes_stale_values(): void
    {
        $this->actingAs($this->seller)->postJson('/business/products', $this->payload([
            'attrs' => [$this->dropdown->id => $this->optionId($this->dropdown)],
        ]))->assertOk();

        $product = Product::firstOrFail();
        $this->assertSame(1, $product->attributeValues()->count());

        $otherClass = SubCategory::create(['category_id' => $this->group->id, 'name' => ['az' => 'Parket'], 'slug' => 'parket', 'is_active' => true]);

        $this->actingAs($this->seller)->putJson('/business/products/'.$product->id, $this->payload([
            'sub_category_id' => $otherClass->id,
        ]))->assertOk();

        $this->assertSame(0, $product->attributeValues()->count());
    }
}
