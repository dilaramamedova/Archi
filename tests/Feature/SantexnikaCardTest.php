<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\ProductCard\CardImporter;
use App\Services\ProductCard\CardSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * The Santexnika workbook (docs/product-cards.md): 46 cards in the workbook's 12
 * categories, every one sold in the seller's unit (ədəd / dəst), with the shared
 * «Rəng və səth», «Qabarit ölçülər», «Zəmanət + müddət» and «Komplektə daxildir» blocks.
 */
class SantexnikaCardTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Category $section;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seller = User::factory()->seller()->create();
        $this->brand = Brand::create(['name' => ['az' => 'Grohe'], 'slug' => 'grohe-test', 'is_active' => true]);
        $this->section = Category::create(['name' => ['az' => 'Santexnika, su təchizatı və kanalizasiya'], 'slug' => 'santexnika-su-techizati-ve-kanalizasiya', 'is_active' => true]);

        app(CardImporter::class)->importFile('santexnika');
    }

    private function schema(string $slug): array
    {
        return CardSchema::for(SubCategory::where('slug', $slug)->firstOrFail()->id);
    }

    private function field(array $schema, string $slug): array
    {
        return collect($schema['fields'])->firstWhere('slug', $slug) ?? throw new RuntimeException("no field {$slug}");
    }

    private function card(array $schema, array $answers): array
    {
        $card = [];
        foreach ($answers as $slug => $answer) {
            $field = $this->field($schema, $slug);
            $option = fn ($o) => collect($field['options'])->firstWhere('slug', $o)['id'] ?? throw new RuntimeException("no option {$o} in {$slug}");
            $card[$field['id']] = match ($field['type']) {
                'dropdown' => ['option' => $option($answer)],
                'multiselect' => ['options' => array_map($option, (array) $answer)],
                'size' => is_array($answer) ? $answer : ['pair' => $answer],
                default => is_array($answer) ? $answer : ['value' => $answer],
            };
        }

        return $card;
    }

    private function payload(string $class, array $card, array $extra = []): array
    {
        $sub = SubCategory::where('slug', $class)->firstOrFail();

        return array_merge([
            'name' => 'Santexnika məhsulu', 'section_id' => $this->section->id, 'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id, 'brand_id' => $this->brand->id, 'origin_country' => 'DE',
            'availability' => 'in_stock', 'price' => '320', 'stock' => 5, 'price_basis' => 'piece', 'publish' => 1, 'card' => $card,
            'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra);
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->seller)->post('/business/products', $payload, ['Accept' => 'application/json']);
    }

    /** A complete toilet (S01_1) card. */
    private function toilet(array $override = []): array
    {
        return $this->card($this->schema('unitazlar'), array_merge([
            'kart-san-montaj-dosemeustu-asma' => 'asma',
            'kart-s01-1-konstruksiya' => 'gizli-cen',
            'kart-san-material-keramika' => 'sanitar-keramika',
            'kart-san-en' => '360', 'kart-san-derinlik' => '530', 'kart-san-hundurluk' => '400',
            'kart-san-reng' => 'ag', 'kart-san-seth' => 'parlaq',
            'kart-san-kanalizasiya-cixisi' => 'ufuqi',
        ], $override));
    }

    public function test_import_builds_the_twelve_categories_with_forty_six_cards(): void
    {
        $groups = Category::where('parent_id', $this->section->id)->where('is_active', true)->get();
        $cards = SubCategory::whereIn('category_id', $groups->pluck('id'))->where('card_enabled', true)->get();

        $this->assertSame(46, $cards->count());
        $this->assertSame(12, $groups->filter(fn ($g) => $cards->contains('category_id', $g->id))->count());
        $this->assertSame('unitazlar-ve-bideler', $groups->sortBy('sort_order')->first()->slug);
        $this->assertSame(['S01_1', 'S12_13'], [
            $cards->firstWhere('slug', 'unitazlar')->card_settings['code'],
            $cards->firstWhere('slug', 'dus-kabinesi-ve-arakesme-hisseleri')->card_settings['code'],
        ]);

        // Running it again changes nothing.
        app(CardImporter::class)->importFile('santexnika');
        $this->assertSame(46, SubCategory::where('card_enabled', true)->count());
    }

    public function test_a_toilet_sells_by_the_piece_or_set_and_needs_its_size_colour_and_finish(): void
    {
        $this->save($this->payload('unitazlar', $this->toilet()))->assertOk();
        $product = Product::latest('id')->firstOrFail();
        $this->assertSame(['piece', 320.0], [$product->sale_unit, $product->salePrice()]);

        $this->save($this->payload('unitazlar', $this->toilet(), ['price_basis' => 'set']))->assertOk();
        $this->assertSame('set', Product::latest('id')->firstOrFail()->sale_unit);

        // «Qabarit ölçülər*» and «Rəng və səth*» are required.
        $schema = $this->schema('unitazlar');
        $card = $this->toilet();
        unset($card[$this->field($schema, 'kart-san-hundurluk')['id']], $card[$this->field($schema, 'kart-san-seth')['id']]);
        $this->save($this->payload('unitazlar', $card))->assertStatus(422)->assertJsonValidationErrors([
            'card.'.$this->field($schema, 'kart-san-hundurluk')['id'],
            'card.'.$this->field($schema, 'kart-san-seth')['id'],
        ]);
    }

    public function test_warranty_months_and_the_economy_flush_follow_their_rules(): void
    {
        $schema = $this->schema('unitazlar');
        $months = $this->field($schema, 'kart-zemanet-muddeti')['id'];

        // The months only count when a warranty is given.
        $this->save($this->payload('unitazlar', $this->toilet(['kart-zemanet' => 'var', 'kart-zemanet-muddeti' => '60'])))->assertOk();
        $this->assertSame(60.0, (float) Product::latest('id')->firstOrFail()->attributeValues()->where('attribute_id', $months)->value('value_numeric'));

        $this->save($this->payload('unitazlar', $this->toilet(['kart-zemanet' => 'verilmir', 'kart-zemanet-muddeti' => '60'])))->assertOk();
        $this->assertFalse(Product::latest('id')->firstOrFail()->attributeValues()->where('attribute_id', $months)->exists());

        // The economy flush can't exceed the full one.
        $this->save($this->payload('unitazlar', $this->toilet(['kart-san-buraxma-tam' => '4', 'kart-san-buraxma-qenaetli' => '6'])))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-san-buraxma-qenaetli')['id']]);
    }

    public function test_included_items_and_not_included_do_not_mix(): void
    {
        $schema = $this->schema('unitazlar');
        $kit = 'card.'.$this->field($schema, 'kart-s01-1-komplekt')['id'];

        $this->save($this->payload('unitazlar', $this->toilet(['kart-s01-1-komplekt' => ['cen', 'daxil-deyil']])))
            ->assertStatus(422)->assertJsonValidationErrors([$kit]);
        $this->save($this->payload('unitazlar', $this->toilet(['kart-s01-1-komplekt' => ['cen', 'oturacaq']])))->assertOk();
    }

    public function test_a_mixer_asks_for_the_concealed_body_code_and_a_sane_pressure_range(): void
    {
        $schema = $this->schema('dus-qarisdiricilari');
        $base = [
            'kart-san-idareetme' => 'termostatik', 'kart-s04-4-montaj' => 'gizli-divar', 'kart-san-material-metal' => 'pirinc',
            'kart-san-reng' => 'xrom', 'kart-san-seth' => 'parlaq', 'kart-san-gizli-govde' => 'ayrica',
        ];
        $code = 'card.'.$this->field($schema, 'kart-san-gizli-govde-kodu')['id'];

        $this->save($this->payload('dus-qarisdiricilari', $this->card($schema, $base)))->assertStatus(422)->assertJsonValidationErrors([$code]);

        $this->save($this->payload('dus-qarisdiricilari', $this->card($schema, $base + [
            'kart-san-gizli-govde-kodu' => '35600000', 'kart-san-tezyiq-min' => '5', 'kart-san-tezyiq-maks' => '1',
        ])))->assertStatus(422)->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-san-tezyiq-min')['id']]);

        $this->save($this->payload('dus-qarisdiricilari', $this->card($schema, $base + [
            'kart-san-gizli-govde-kodu' => '35600000', 'kart-san-tezyiq-min' => '0.5', 'kart-san-tezyiq-maks' => '5',
        ])))->assertOk();
    }

    public function test_an_electronic_bidet_toilet_gives_voltage_and_power_only_when_powered(): void
    {
        $schema = $this->schema('bide-funksiyali-unitazlar');
        $base = [
            'kart-san-montaj-dosemeustu-asma' => 'dosemeustu', 'kart-s01-3-idareetme' => 'elektron',
            'kart-san-en' => '400', 'kart-san-derinlik' => '600', 'kart-san-hundurluk' => '480',
            'kart-san-reng' => 'ag', 'kart-san-seth' => 'parlaq', 'kart-san-kanalizasiya-cixisi' => 'universal',
            'kart-s01-3-su' => 'soyuq',
        ];

        // «Elektrik qidalanması*» is required.
        $this->save($this->payload('bide-funksiyali-unitazlar', $this->card($schema, $base)))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-san-elektrik')['id']]);

        $this->save($this->payload('bide-funksiyali-unitazlar', $this->card($schema, $base + [
            'kart-san-elektrik' => 'teleb-olunur', 'kart-san-gerginlik' => '230', 'kart-san-guc' => '1200',
        ])))->assertOk();
        $values = Product::latest('id')->firstOrFail()->attributeValues()->pluck('value_numeric', 'attribute_id');
        $this->assertSame(230.0, (float) $values[$this->field($schema, 'kart-san-gerginlik')['id']]);
    }

    public function test_an_accessory_set_sells_only_as_a_set(): void
    {
        $schema = $this->schema('hamam-aksesuar-destleri');
        $card = $this->card($schema, [
            'kart-s11-6-say' => '4', 'kart-s11-6-terkib' => ['desmal', 'kagiz', 'qarmaq', 'ref'],
            'kart-san-reng' => 'qara', 'kart-san-seth' => 'mat', 'kart-san-aksesuar-montaj' => 'vintle',
        ]);

        $this->save($this->payload('hamam-aksesuar-destleri', $card))->assertStatus(422)->assertJsonValidationErrors(['price_basis']);
        $this->save($this->payload('hamam-aksesuar-destleri', $card, ['price_basis' => 'set']))->assertOk();
        $this->assertSame('set', Product::latest('id')->firstOrFail()->sale_unit);
    }

    public function test_a_separately_sold_channel_body_names_its_system(): void
    {
        $schema = $this->schema('xetti-dus-kanallari');
        $base = ['kart-s10-2-uzunluq' => '800', 'kart-san-reng' => 'xrom', 'kart-san-seth' => 'fircalanmis'];
        $system = 'card.'.$this->field($schema, 'kart-san-uygun-sistem')['id'];

        // A complete set does not need it; a body sold alone does.
        $this->save($this->payload('xetti-dus-kanallari', $this->card($schema, $base + ['kart-s10-2-techizat' => 'tam'])))->assertOk();
        $this->save($this->payload('xetti-dus-kanallari', $this->card($schema, $base + ['kart-s10-2-techizat' => 'govde'])))
            ->assertStatus(422)->assertJsonValidationErrors([$system]);
    }
}
