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
 * The Elektrik workbook (docs/product-cards.md): 43 cards in the workbook's 4 blocks —
 * quraşdırma, işıqlandırma, internet / rabitə / təhlükəsizlik, ağıllı ev — sold in the
 * seller's unit, with the shared voltage, smart-home, connectivity and supply blocks.
 */
class ElektrikCardTest extends TestCase
{
    use RefreshDatabase;

    private const SECTION = 'elektrik-isiqlandirma-zeif-cereyan-sistemleri-ve-agilli-ev';

    private User $seller;

    private Category $section;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seller = User::factory()->seller()->create();
        $this->brand = Brand::create(['name' => ['az' => 'Schneider'], 'slug' => 'schneider-test', 'is_active' => true]);
        $this->section = Category::create(['name' => ['az' => 'Elektrik, işıqlandırma, zəif cərəyan sistemləri və ağıllı ev'], 'slug' => self::SECTION, 'is_active' => true]);

        // The tree the workbook meets on prod: an old socket group and an old low-current group.
        $old = Category::create(['name' => ['az' => 'Rozetkalar və açarlar'], 'slug' => 'rozetkalar-ve-acarlar', 'parent_id' => $this->section->id, 'is_active' => true]);
        SubCategory::create(['name' => ['az' => 'Rozetkalar və açarlar'], 'slug' => 'rozetkalar-ve-acarlar', 'category_id' => $old->id, 'is_active' => true]);
        $low = Category::create(['name' => ['az' => 'Zəif cərəyan sistemləri və ağıllı ev'], 'slug' => 'zeif-cereyan-sistemleri-ve-agilli-ev', 'parent_id' => $this->section->id, 'is_active' => true]);
        SubCategory::create(['name' => ['az' => 'Videomüşahidə (CCTV)'], 'slug' => 'videomusahide-cctv', 'category_id' => $low->id, 'is_active' => true]);

        app(CardImporter::class)->importFile('elektrik');
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
                // A prompted option («Wi-Fi» → frequency) comes as ['options' => […], 'text' => '…'].
                'multiselect' => isset($answer['options'])
                    ? ['options' => array_map($option, $answer['options']), 'text' => $answer['text']]
                    : ['options' => array_map($option, (array) $answer)],
                default => is_array($answer) ? $answer : ['value' => $answer],
            };
        }

        return $card;
    }

    private function payload(string $class, array $card, array $extra = []): array
    {
        $sub = SubCategory::where('slug', $class)->firstOrFail();

        return array_merge([
            'name' => 'Elektrik məhsulu', 'section_id' => $this->section->id, 'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id, 'brand_id' => $this->brand->id, 'origin_country' => 'FR',
            'availability' => 'in_stock', 'price' => '18', 'stock' => 40, 'price_basis' => 'piece', 'publish' => 1, 'card' => $card,
            'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra);
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->seller)->post('/business/products', $payload, ['Accept' => 'application/json']);
    }

    private function error(array $schema, string $slug): string
    {
        return 'card.'.$this->field($schema, $slug)['id'];
    }

    /** A complete power socket (E01_01) card. */
    private function socket(array $override = []): array
    {
        return $this->card($this->schema('rozetkalar-ve-acarlar'), array_merge([
            'kart-e01-01-nov' => 'adi', 'kart-el-montaj-qurasdirma' => 'gizli', 'kart-e01-01-standart' => 'schuko',
            'kart-e01-01-yuva' => '1', 'kart-e01-01-torpaqlama' => 'var',
            'kart-el-gerginlik' => '230', 'kart-el-cereyan-novu' => 'ac', 'kart-el-cereyan' => '16',
            'kart-el-reng' => 'ag', 'kart-el-seth' => 'mat', 'kart-el-seriya' => 'Unica',
        ], $override));
    }

    public function test_import_builds_the_four_blocks_with_forty_three_cards(): void
    {
        $groups = Category::where('parent_id', $this->section->id)->where('is_active', true)->get();
        $cards = SubCategory::whereIn('category_id', $groups->pluck('id'))->where('card_enabled', true)->get();

        $this->assertSame(43, $cards->count());
        $this->assertSame(
            ['elektrik-qurasdirma-mehsullari', 'isiqlandirma', 'internet-rabite-ve-tehlukesizlik', 'agilli-ev-ve-avtomatlasdirma', 'guc-elektrik-sistemleri'],
            $groups->sortBy('sort_order')->pluck('slug')->all(),
        );

        // Old classes keep their slug and move into the workbook's blocks; their old groups close.
        $socket = $cards->firstWhere('slug', 'rozetkalar-ve-acarlar');
        $this->assertSame(['E01_01', 'elektrik-qurasdirma-mehsullari'], [$socket->card_settings['code'], $socket->category->slug]);
        $this->assertSame('E03_06', $cards->firstWhere('slug', 'videomusahide-cctv')->card_settings['code']);
        $this->assertSame(0, Category::whereIn('slug', ['rozetkalar-ve-acarlar', 'zeif-cereyan-sistemleri-ve-agilli-ev'])->where('is_active', true)->count());
        $this->assertSame('E04_07', $cards->firstWhere('slug', 'perde-avtomatikasi-destleri')->card_settings['code']);

        // Running it again changes nothing.
        app(CardImporter::class)->importFile('elektrik');
        $this->assertSame(43, SubCategory::where('card_enabled', true)->count());
    }

    public function test_a_socket_sells_by_the_piece_or_set_and_needs_its_series_colour_and_brand_country(): void
    {
        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket()))->assertOk();
        $this->assertSame(['piece', 18.0], [Product::latest('id')->firstOrFail()->sale_unit, Product::latest('id')->firstOrFail()->salePrice()]);

        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket(), ['price_basis' => 'set']))->assertOk();
        $this->assertSame('set', Product::latest('id')->firstOrFail()->sale_unit);

        $schema = $this->schema('rozetkalar-ve-acarlar');
        $card = $this->socket();
        unset($card[$this->field($schema, 'kart-el-seriya')['id']], $card[$this->field($schema, 'kart-el-reng')['id']]);
        $this->save($this->payload('rozetkalar-ve-acarlar', $card, ['origin_country' => '', 'price_basis' => 'lm']))
            ->assertStatus(422)
            ->assertJsonValidationErrors([$this->error($schema, 'kart-el-seriya'), $this->error($schema, 'kart-el-reng'), 'origin_country', 'price_basis']);
    }

    public function test_voltage_range_and_usb_details_follow_their_rules(): void
    {
        $schema = $this->schema('rozetkalar-ve-acarlar');

        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket(['kart-el-gerginlik' => '250', 'kart-el-gerginlik-maks' => '220'])))
            ->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-el-gerginlik')]);

        // USB power counts only when the socket has a USB output.
        $watts = $this->field($schema, 'kart-e01-01-usb-guc')['id'];
        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket(['kart-e01-01-usb' => 'yoxdur', 'kart-e01-01-usb-guc' => '20'])))->assertOk();
        $this->assertFalse(Product::latest('id')->firstOrFail()->attributeValues()->where('attribute_id', $watts)->exists());

        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket(['kart-e01-01-usb' => ['usb-a', 'usb-c'], 'kart-e01-01-usb-guc' => '20'])))->assertOk();
        $this->assertSame(20.0, (float) Product::latest('id')->firstOrFail()->attributeValues()->where('attribute_id', $watts)->value('value_numeric'));

        // «Yoxdur» does not mix with a USB type.
        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket(['kart-e01-01-usb' => ['yoxdur', 'usb-c']])))
            ->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-e01-01-usb')]);
    }

    public function test_a_smart_device_names_its_system_only_when_compatible(): void
    {
        $schema = $this->schema('rozetkalar-ve-acarlar');

        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket(['kart-el-agilli' => 'yoxdur'])))->assertOk();
        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket(['kart-el-agilli' => 'var'])))
            ->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-el-agilli-sistem')]);
        $this->save($this->payload('rozetkalar-ve-acarlar', $this->socket([
            'kart-el-agilli' => 'var', 'kart-el-agilli-sistem' => 'Wiser', 'kart-el-matter' => 'beli',
            'kart-el-rabite' => ['options' => ['wi-fi'], 'text' => '2,4 GHz'],
        ])))->assertOk();
    }

    public function test_a_light_with_a_replaceable_lamp_names_its_socket_and_lamp_count(): void
    {
        $schema = $this->schema('cilciraqlar-ve-asma-isiqlandiricilar');
        $base = [
            'kart-e02-01-nov' => 'cilciraq', 'kart-el-guc' => '60', 'kart-el-gerginlik' => '230', 'kart-el-cereyan-novu' => 'ac',
        ];

        $this->save($this->payload('cilciraqlar-ve-asma-isiqlandiricilar', $this->card($schema, $base + ['kart-el-isiq-menbeyi' => 'daxili-led'])))->assertOk();

        $this->save($this->payload('cilciraqlar-ve-asma-isiqlandiricilar', $this->card($schema, $base + ['kart-el-isiq-menbeyi' => 'lampa'])))
            ->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-el-soket'), $this->error($schema, 'kart-el-lampa-sayi')]);

        $this->save($this->payload('cilciraqlar-ve-asma-isiqlandiricilar', $this->card($schema, $base + [
            'kart-el-isiq-menbeyi' => 'lampa', 'kart-el-soket' => 'e14', 'kart-el-lampa-sayi' => '6', 'kart-el-k-min' => '4000', 'kart-el-k-maks' => '2700',
        ])))->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-el-k-min')]);
    }

    public function test_led_strip_and_bulbs_sell_in_their_own_units(): void
    {
        $strip = $this->card($this->schema('led-lentler'), [
            'kart-el-gerginlik' => '24', 'kart-el-cereyan-novu' => 'dc', 'kart-el-guc' => '14.4',
            'kart-e02-09-uzunluq' => '5', 'kart-e02-09-en' => '10', 'kart-e02-09-qalinliq' => '2', 'kart-e02-09-idareetme' => 'cct',
        ]);
        $this->save($this->payload('led-lentler', $strip))->assertStatus(422)->assertJsonValidationErrors(['price_basis']);
        $this->save($this->payload('led-lentler', $strip, ['price_basis' => 'roll']))->assertOk();
        $this->assertSame('roll', Product::latest('id')->firstOrFail()->sale_unit);
        $this->save($this->payload('led-lentler', $strip, ['price_basis' => 'lm']))->assertOk();
        $this->assertSame('lm', Product::latest('id')->firstOrFail()->sale_unit);

        // A bulb always names its socket.
        $schema = $this->schema('lampalar');
        $bulb = ['kart-e02-10-texnologiya' => 'led', 'kart-el-guc' => '9', 'kart-el-gerginlik' => '230', 'kart-el-cereyan-novu' => 'ac'];
        $this->save($this->payload('lampalar', $this->card($schema, $bulb), ['price_basis' => 'pack']))
            ->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-el-soket')]);
        $this->save($this->payload('lampalar', $this->card($schema, $bulb + ['kart-el-soket' => 'e27']), ['price_basis' => 'pack']))->assertOk();
        $this->assertSame('pack', Product::latest('id')->firstOrFail()->sale_unit);
        $this->save($this->payload('lampalar', $this->card($schema, $bulb + ['kart-el-soket' => 'e27']), ['price_basis' => 'set']))
            ->assertStatus(422)->assertJsonValidationErrors(['price_basis']);
    }

    public function test_a_battery_sensor_gives_the_battery_and_warranty_months_need_a_warranty(): void
    {
        $schema = $this->schema('muhafize-sensorlari');
        $base = ['kart-e03-14-nov' => 'hereket', 'kart-el-uygun-sistem' => 'Ajax Hub 2', 'kart-el-qidalanma' => 'batareya'];
        $months = $this->field($schema, 'kart-zemanet-muddeti')['id'];

        $this->save($this->payload('muhafize-sensorlari', $this->card($schema, $base)))
            ->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-el-batareya')]);

        $this->save($this->payload('muhafize-sensorlari', $this->card($schema, $base + [
            'kart-el-batareya' => 'CR123A', 'kart-zemanet' => 'var', 'kart-zemanet-muddeti' => '24',
        ])))->assertOk();
        $this->assertSame(24.0, (float) Product::latest('id')->firstOrFail()->attributeValues()->where('attribute_id', $months)->value('value_numeric'));

        // Included items and «Daxil deyil» do not mix.
        $this->save($this->payload('muhafize-sensorlari', $this->card($schema, $base + [
            'kart-el-batareya' => 'CR123A', 'kart-e03-14-komplekt' => ['sensor', 'daxil-deyil'],
        ])))->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-e03-14-komplekt')]);
    }

    public function test_a_surveillance_kit_sells_only_as_a_set_and_states_its_storage(): void
    {
        $schema = $this->schema('videomusahide-destleri');
        $base = [
            'kart-e03-08-sistem' => 'ip', 'kart-e03-08-kameralar' => '4 × 4 MP dome',
            'kart-e03-08-registrator' => 'NVR 8 kanal', 'kart-e03-08-yaddas' => 'daxildir',
        ];

        $this->save($this->payload('videomusahide-destleri', $this->card($schema, $base), ['price_basis' => 'set']))
            ->assertStatus(422)->assertJsonValidationErrors([$this->error($schema, 'kart-e03-08-yaddas-hecm')]);

        $card = $this->card($schema, $base + ['kart-e03-08-yaddas-hecm' => '1 TB']);
        $this->save($this->payload('videomusahide-destleri', $card))->assertStatus(422)->assertJsonValidationErrors(['price_basis']);
        $this->save($this->payload('videomusahide-destleri', $card, ['price_basis' => 'set']))->assertOk();
        $this->assertSame('set', Product::latest('id')->firstOrFail()->sale_unit);
    }
}
