<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\CardForm;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ConsultationRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\ProductCard\CardImporter;
use App\Services\ProductCard\CardSchema;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

/**
 * Category product cards (docs/product-cards.md), on the client's Döşəmə workbook:
 * the import, the seller form (draft / publish, conditions, rules), the computed sale
 * unit, the product page, the order and the admin form.
 */
class ProductCardTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Category $section;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        CardForm::flush();

        $this->seller = User::factory()->seller()->create();
        $this->brand = Brand::create(['name' => ['az' => 'Kronospan'], 'slug' => 'kronospan', 'is_active' => true]);
        $this->section = Category::create(['name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler', 'is_active' => true]);

        app(CardImporter::class)->importFile('doseme');
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    private function class(string $slug): SubCategory
    {
        return SubCategory::where('slug', $slug)->firstOrFail();
    }

    private function schema(string $slug): array
    {
        return CardSchema::for($this->class($slug)->id);
    }

    private function field(array $schema, string $slug): array
    {
        return collect($schema['fields'])->firstWhere('slug', $slug) ?? throw new RuntimeException("no field {$slug}");
    }

    private function option(array $schema, string $field, string $option): int
    {
        return collect($this->field($schema, $field)['options'])->firstWhere('slug', $option)['id'];
    }

    /** card[...] input from [field slug => option slug | [option slugs] | value | size array]. */
    private function card(array $schema, array $answers): array
    {
        $card = [];

        foreach ($answers as $slug => $answer) {
            $field = $this->field($schema, $slug);
            $card[$field['id']] = match ($field['type']) {
                'dropdown' => is_array($answer)
                    ? ['option' => $this->option($schema, $slug, $answer[0]), 'text' => $answer[1]]
                    : ['option' => $this->option($schema, $slug, $answer)],
                'multiselect' => ['options' => array_map(fn ($o) => $this->option($schema, $slug, $o), (array) $answer)],
                'size' => $answer,
                default => ['value' => $answer],
            };
        }

        return $card;
    }

    private function laminateCard(array $overrides = []): array
    {
        $schema = $this->schema('laminat');

        return $this->card($schema, array_merge([
            'kart-laminat-istismar-sinfi' => '32',
            'kart-asinma-sinfi' => 'ac4',
            'kart-panel-qalinligi' => '8',
            'kart-panel-olcusu' => ['length' => '1380', 'width' => '193'],
            'kart-isti-doseme' => 'beli',
            'kart-qablasdirma-sahesi' => '2,131',
            'kart-zemanet' => 'var',
        ], $overrides));
    }

    private function payload(string $class, array $card, array $extra = []): array
    {
        $sub = $this->class($class);

        return array_merge([
            'name' => 'Kart məhsulu',
            'section_id' => $this->section->id,
            'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id,
            'brand_id' => $this->brand->id,
            'origin_country' => 'DE',
            'availability' => 'in_stock',
            'price' => '18.50',
            'stock' => 40,
            'publish' => 1,
            'card' => $card,
            'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra);
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->seller)->post('/business/products', $payload, ['Accept' => 'application/json']);
    }

    // ── import ──────────────────────────────────────────────────────────────

    public function test_import_builds_the_twelve_cards_in_one_group_and_is_repeatable(): void
    {
        $group = Category::where('slug', 'doseme-ortukleri')->firstOrFail();
        $this->assertSame($this->section->id, $group->parent_id);
        $this->assertSame(12, SubCategory::where('category_id', $group->id)->where('card_enabled', true)->count());
        $this->assertSame(10, $this->class('laminat')->attributes()->count());

        $attributes = Attribute::count();
        app(CardImporter::class)->importFile('doseme');

        $this->assertSame($attributes, Attribute::count());
        // One shared warranty attribute serves every card.
        $this->assertSame(12, Attribute::where('slug', 'kart-zemanet')->firstOrFail()->subCategories()->count());
    }

    public function test_a_shared_attribute_may_not_be_defined_two_ways(): void
    {
        $field = ['slug' => 'kart-test', 'name' => ['az' => 'X'], 'type' => 'decimal'];

        $this->expectException(RuntimeException::class);

        app(CardImporter::class)->import([
            'section' => 'dosemeler',
            'group' => ['slug' => 'doseme-ortukleri', 'name' => ['az' => 'Döşəmə örtükləri']],
            'classes' => [
                'laminat' => ['name' => ['az' => 'Laminat'], 'fields' => [$field]],
                'linoleum' => ['name' => ['az' => 'Linoleum'], 'fields' => [['name' => ['az' => 'Y']] + $field]],
            ],
        ]);
    }

    public function test_the_class_form_endpoint_answers_with_the_card(): void
    {
        $this->actingAs($this->seller)
            ->getJson('/business/api/sub-categories/'.$this->class('laminat')->id.'/form')
            ->assertOk()
            ->assertJsonCount(10, 'card.fields')
            ->assertJsonPath('card.sale.type', 'area')
            ->assertJsonPath('fields', []);
    }

    // ── seller form ─────────────────────────────────────────────────────────

    public function test_publishing_a_complete_laminate_card_computes_the_pack(): void
    {
        $this->save($this->payload('laminat', $this->laminateCard([
            'kart-laminat-istismar-sinfi' => ['diger', '33+'],
        ]), ['collection' => 'Classic', 'old_price' => '22']))->assertOk()->assertJsonPath('success', true);

        $product = Product::firstOrFail();
        $this->assertSame('m2', $product->unit);
        $this->assertSame('pack', $product->sale_unit);
        $this->assertSame('2.1310', $product->package_size);
        $this->assertSame('DE', $product->origin_country);
        $this->assertSame('Classic', $product->collection);
        $this->assertSame(39.42, $product->salePrice());
        $this->assertSame(7, $product->attributeValues()->count());
        $this->assertSame('33+', $product->attributeValues()->whereNotNull('value_text')->value('value_text'));
    }

    public function test_publishing_requires_every_required_card_field(): void
    {
        $schema = $this->schema('laminat');

        $this->save($this->payload('laminat', [], ['origin_country' => '', 'brand_id' => null]))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'card.'.$this->field($schema, 'kart-panel-qalinligi')['id'],
                'card.'.$this->field($schema, 'kart-qablasdirma-sahesi')['id'],
                'origin_country',
                'brand_id',
            ]);

        $this->assertSame(0, Product::count());
    }

    public function test_a_draft_saves_an_incomplete_card_and_names_what_is_missing(): void
    {
        $response = $this->save($this->payload('laminat', [], ['publish' => 0, 'price' => '', 'images' => []]))->assertOk();

        $missing = $response->json('missing');
        $this->assertContains('Panelin qalınlığı', $missing);
        $this->assertContains('Şəkil', $missing);
        $this->assertFalse((bool) Product::firstOrFail()->is_visible);
    }

    public function test_option_text_is_required_when_the_option_asks_for_it(): void
    {
        $schema = $this->schema('laminat');

        $this->save($this->payload('laminat', $this->laminateCard(['kart-laminat-istismar-sinfi' => 'diger'])))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-laminat-istismar-sinfi')['id'].'.text']);
    }

    public function test_an_option_that_does_not_exist_for_the_answers_is_rejected(): void
    {
        // «İçiboş» exists only for WPC deck boards.
        $schema = $this->schema('terras-lovhesi-dpk');

        $this->save($this->payload('terras-lovhesi-dpk', $this->card($schema, [
            'kart-teras-material' => 'agac',
            'kart-teras-profil' => 'icibos',
            'kart-taxta-uzunlugu' => '3000',
            'kart-taxta-eni' => '145',
            'kart-umumi-qalinliq' => '25',
        ]), ['price_basis' => 'piece']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-teras-profil')['id']]);
    }

    public function test_floor_use_must_be_confirmed_and_square_tiles_must_be_square(): void
    {
        $schema = $this->schema('keramoqranit');
        $answers = [
            'kart-dosemede-istifade' => 'xeyr',
            'kart-forma' => 'kvadrat',
            'kart-memulat-olcusu' => ['length' => '600', 'width' => '1200'],
            'kart-umumi-qalinliq' => '9',
            'kart-qablasdirma-sahesi' => '1.44',
        ];

        $this->save($this->payload('keramoqranit', $this->card($schema, $answers)))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'card.'.$this->field($schema, 'kart-dosemede-istifade')['id'],
                'card.'.$this->field($schema, 'kart-memulat-olcusu')['id'],
            ]);
    }

    public function test_mixed_lengths_need_a_range_and_the_top_layer_stays_thinner(): void
    {
        $schema = $this->schema('parket-parket-lovhesi');

        $this->save($this->payload('parket-parket-lovhesi', $this->card($schema, [
            'kart-parket-konstruksiya' => 'ucqatli',
            'kart-umumi-qalinliq' => '14',
            'kart-ust-qat-qalinligi' => '15',
            'kart-uzunluq-formati' => 'qarisiq-uzunluqlar',
            'kart-memulat-olcusu' => ['length' => '1200', 'width' => '190'],
            'kart-qablasdirma-sahesi' => '2.2',
        ])))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'card.'.$this->field($schema, 'kart-memulat-olcusu')['id'],
                'card.'.$this->field($schema, 'kart-ust-qat-qalinligi')['id'],
            ]);
    }

    public function test_a_listed_field_takes_only_a_listed_value(): void
    {
        $schema = $this->schema('laminat');

        // 7.5 mm is not on the thickness list, 1000×100 not on the size list.
        $this->save($this->payload('laminat', $this->laminateCard([
            'kart-panel-qalinligi' => '7.5',
            'kart-panel-olcusu' => ['pair' => '1000×100'],
        ])))
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'card.'.$this->field($schema, 'kart-panel-qalinligi')['id'],
                'card.'.$this->field($schema, 'kart-panel-olcusu')['id'],
            ]);

        // The size arrives as one listed «L×W» choice.
        $this->save($this->payload('laminat', $this->laminateCard([
            'kart-panel-olcusu' => ['pair' => '1380×244'],
        ])))->assertOk();

        $row = Product::firstOrFail()->attributeValues()
            ->where('attribute_id', $this->field($schema, 'kart-panel-olcusu')['id'])->firstOrFail();
        $this->assertSame(1380.0, (float) $row->value_min);
        $this->assertSame(244.0, (float) $row->value_numeric);
    }

    public function test_not_specified_stands_alone_in_a_multiple_choice(): void
    {
        $schema = $this->schema('linoleum');

        $this->save($this->payload('linoleum', $this->card($schema, [
            'kart-linoleum-material' => 'heterogen-pvc',
            'kart-rulon-eni' => '3',
            'kart-umumi-qalinliq' => '2.5',
            'kart-istismar-sinifleri' => ['23', 'gosterilmeyib'],
        ])))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-istismar-sinifleri')['id']]);
    }

    public function test_a_roll_is_sold_by_the_running_metre(): void
    {
        $schema = $this->schema('linoleum');

        $this->save($this->payload('linoleum', $this->card($schema, [
            'kart-linoleum-material' => 'heterogen-pvc',
            'kart-rulon-eni' => '3',
            'kart-umumi-qalinliq' => '2.5',
        ]), ['price' => '12', 'is_unbranded' => 1, 'brand_id' => null]))->assertOk();

        $product = Product::firstOrFail();
        $this->assertSame('lm', $product->sale_unit);
        $this->assertSame(36.0, $product->salePrice());
        $this->assertTrue($product->is_unbranded);
        $this->assertNull($product->brand_id);
    }

    public function test_a_deck_board_is_priced_in_its_basis_and_sold_whole(): void
    {
        $schema = $this->schema('terras-lovhesi-dpk');

        $this->save($this->payload('terras-lovhesi-dpk', $this->card($schema, [
            'kart-teras-material' => 'wpc',
            'kart-teras-profil' => 'icibos',
            'kart-taxta-uzunlugu' => '3000',
            'kart-taxta-eni' => '150',
            'kart-umumi-qalinliq' => '25',
        ]), ['price' => '40', 'price_basis' => 'm2']))->assertOk();

        $product = Product::firstOrFail();
        $this->assertSame('m2', $product->unit);
        $this->assertSame('piece', $product->sale_unit);
        // 3 m × 0.15 m = 0.45 m² × 40 AZN
        $this->assertSame(18.0, $product->salePrice());
    }

    public function test_custom_size_stone_has_no_price_and_asks_for_a_quote(): void
    {
        $schema = $this->schema('tebii-das-mermer-qranit-sist-ve-s');

        $this->save($this->payload('tebii-das-mermer-qranit-sist-ve-s', $this->card($schema, [
            'kart-das-novu' => 'mermer',
            'kart-das-format' => 'ferdi-olcu',
            'kart-dosemede-istifade' => 'beli',
            'kart-umumi-qalinliq' => '20',
        ]), ['price' => '', 'availability' => 'on_order', 'lead_time_days' => 10]))->assertOk();

        $product = Product::firstOrFail();
        $this->assertTrue($product->quote_only);
        $this->assertSame(10, $product->lead_time_days);
        $this->assertFalse($product->isOrderable());

        $product->update(['is_approved' => true]);
        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('pdQuoteForm', false)
            ->assertSee('Hesablama tələb et');

        $this->postJson('/consultation-requests', [
            'full_name' => 'Alıcı', 'phone' => '+994501112233', 'source' => 'product',
            'product_ref' => $product->name.' — https://archi.az/product/'.$product->slug,
            'message' => '4 × 3 m',
        ])->assertCreated();

        $this->assertStringContainsString('Kart məhsulu', ConsultationRequest::firstOrFail()->message);
    }

    // ── buyer side ──────────────────────────────────────────────────────────

    public function test_product_page_shows_the_card_and_the_pack_price(): void
    {
        // Vinyl, not laminate: phpunit.xml keeps the old laminate card switched on.
        $schema = $this->schema('spc-lvt-kvars-vinil');
        $this->save($this->payload('spc-lvt-kvars-vinil', $this->card($schema, [
            'kart-vinil-novu' => 'spc',
            'kart-umumi-qalinliq' => '4',
            'kart-memulat-olcusu' => ['length' => '1220', 'width' => '180'],
            'kart-qablasdirma-sahesi' => '2.196',
        ]), ['price' => '25']))->assertOk();

        $product = Product::firstOrFail();
        $product->update(['is_approved' => true]);

        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('1220 × 180 mm')
            ->assertSee('Almaniya')
            ->assertSee('54.90 ₼')         // 25 × 2.196 m²
            ->assertSee('data-cart-price="54.90"', false);
    }

    public function test_the_order_charges_the_pack_and_made_to_order_ignores_stock(): void
    {
        $this->save($this->payload('laminat', $this->laminateCard(), ['availability' => 'on_order', 'lead_time_days' => 7]))->assertOk();
        $product = Product::firstOrFail();
        $product->update(['is_approved' => true]);
        $this->assertSame(0, (int) $product->stock);

        $this->actingAs(User::factory()->create(['role' => 'buyer', 'status' => 'active']))
            ->postJson('/api/orders', [
                'items' => [['product_id' => $product->id, 'qty' => 5]],
                'delivery_name' => 'Test Alıcı',
                'delivery_phone' => '+994501112233',
                'delivery_city' => 'baku',
                'delivery_address' => 'Test küç. 1',
            ])->assertOk();

        $this->assertSame('39.42', Order::firstOrFail()->items()->first()->unit_price);
    }

    // ── admin ───────────────────────────────────────────────────────────────

    public function test_admin_edits_a_card_product_with_the_same_rules(): void
    {
        $schema = $this->schema('spc-lvt-kvars-vinil');
        $this->save($this->payload('spc-lvt-kvars-vinil', $this->card($schema, [
            'kart-vinil-novu' => 'spc',
            'kart-umumi-qalinliq' => '4',
            'kart-memulat-olcusu' => ['length' => '1220', 'width' => '180'],
            'kart-qablasdirma-sahesi' => '2.196',
        ]), ['price' => '25']))->assertOk();
        $product = Product::firstOrFail();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        $thickness = $this->field($schema, 'kart-umumi-qalinliq')['id'];
        $pack = $this->field($schema, 'kart-qablasdirma-sahesi')['id'];

        // fillForm() replaces the whole `card` array, so it gets the full stored state.
        $state = CardForm::fillState($product)['card'];

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(["card.{$thickness}.value" => '4', 'origin_country' => 'DE'])
            ->fillForm(['card' => array_replace($state, [$pack => ['value' => '2']])])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(50.0, $product->fresh()->salePrice());
        $this->assertSame('2.0000', $product->fresh()->package_size);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['card' => array_replace($state, [$thickness => ['value' => null]])])
            ->call('save')
            ->assertHasFormErrors(["card.{$thickness}.value"]);
    }
}
