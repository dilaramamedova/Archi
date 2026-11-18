<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\CardForm;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Models\Brand;
use App\Models\Category;
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
 * The Divar workbook (docs/product-cards.md): 37 cards in 19 groups, a size picked
 * from a list or typed («Başqa ölçü»), ready-made vs custom-size units, a custom size
 * on request, whole wallpaper rolls and the workbook's min–max ranges.
 */
class DivarCardTest extends TestCase
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
        $this->brand = Brand::create(['name' => ['az' => 'Kerama'], 'slug' => 'kerama', 'is_active' => true]);
        $this->section = Category::create(['name' => ['az' => 'Divarlar və arakəsmələr'], 'slug' => 'divarlar-ve-arakesmeler', 'is_active' => true]);

        app(CardImporter::class)->importFile('divar');
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
            'name' => 'Divar məhsulu', 'section_id' => $this->section->id, 'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id, 'brand_id' => $this->brand->id, 'origin_country' => 'ES',
            'availability' => 'in_stock', 'price' => '20', 'stock' => 10, 'publish' => 1, 'card' => $card,
            'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra);
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->seller)->post('/business/products', $payload, ['Accept' => 'application/json']);
    }

    /** A complete wall tile (W04) card with the given size answer. */
    private function tile(string|array $size): array
    {
        return $this->card($this->schema('divar-keramik-plitesi'), [
            'kart-divar-olcu' => $size,
            'kart-divar-qalinliq' => '8',
            'kart-divar-reng' => 'ag',
            'kart-divar-qablasdirma-sahesi' => '1.44',
        ]);
    }

    /** A photo mural (W01_2): ready-made set or custom size. */
    private function mural(string $format, ?string $price = null): array
    {
        return $this->card($this->schema('foto-divar-kagizlari'), array_filter([
            'kart-w01-2-material' => 'flizelin',
            'kart-w01-2-format' => $format,
            'kart-divar-ferdi-qiymet' => $price,
            'kart-w01-2-olcu' => $format === 'hazir' ? '3000×2700' : null,
            'kart-divar-reng' => 'coxrengli',
        ]));
    }

    public function test_import_builds_nineteen_groups_and_thirty_seven_cards(): void
    {
        $groups = Category::where('parent_id', $this->section->id)->where('is_active', true)->get();
        $cards = SubCategory::whereIn('category_id', $groups->pluck('id'))->where('card_enabled', true)->get();

        $this->assertSame(37, $cards->count());
        $this->assertSame(19, $groups->filter(fn ($g) => $cards->contains('category_id', $g->id))->count());
        $this->assertSame('divar-kagizi-novleri', $groups->sortBy('sort_order')->first()->slug);
        $this->assertSame(['W01_1', 'WK08'], [$cards->firstWhere('slug', 'divar-kagizlari')->card_settings['code'], $cards->firstWhere('slug', 'plite-ara-mesafe-ve-seviyyeleme-elementleri')->card_settings['code']]);

        // Running it again changes nothing.
        app(CardImporter::class)->importFile('divar');
        $this->assertSame(37, SubCategory::where('card_enabled', true)->count());
    }

    public function test_a_tile_size_is_picked_from_the_list_or_typed_as_another_size(): void
    {
        $this->save($this->payload('divar-keramik-plitesi', $this->tile('300×600')))->assertOk();
        $listed = Product::latest('id')->firstOrFail();
        $this->assertSame(28.8, $listed->salePrice());                // 20 AZN/m² × 1.44 m²

        $this->save($this->payload('divar-keramik-plitesi', $this->tile(['pair' => CardSchema::OTHER_SIZE, 'length' => '333', 'width' => '777'])))->assertOk();
        $typed = Product::latest('id')->firstOrFail();
        $size = $this->field($this->schema('divar-keramik-plitesi'), 'kart-divar-olcu')['id'];
        $row = $typed->attributeValues()->where('attribute_id', $size)->firstOrFail();
        $this->assertEquals([333, 777], [(float) $row->value_min, (float) $row->value_numeric]);

        // «Başqa ölçü» with nothing typed is an empty required size.
        $this->save($this->payload('divar-keramik-plitesi', $this->tile(['pair' => CardSchema::OTHER_SIZE])))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$size]);
    }

    public function test_a_ready_mural_sells_by_the_piece_and_a_custom_one_by_the_m2_or_on_request(): void
    {
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('hazir'), ['price_basis' => 'piece', 'price' => '120']))->assertOk();
        $ready = Product::latest('id')->firstOrFail();
        $this->assertSame(['piece', 120.0], [$ready->sale_unit, $ready->salePrice()]);

        // The unit follows the format: a ready set is not sold by the m².
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('hazir'), ['price_basis' => 'm2']))
            ->assertStatus(422)->assertJsonValidationErrors(['price_basis']);

        // «Fərdi istehsalda mövcudluq “Sifarişlə”»: a custom size is never in stock.
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('ferdi', 'm2'), ['price_basis' => 'm2', 'price' => '35']))
            ->assertStatus(422)->assertJsonValidationErrors(['availability']);

        $onOrder = ['availability' => 'on_order', 'lead_time_days' => 10];
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('ferdi', 'm2'), ['price_basis' => 'm2', 'price' => '35'] + $onOrder))->assertOk();
        $custom = Product::latest('id')->firstOrFail();
        $this->assertSame(['m2', 'm2', false], [$custom->unit, $custom->sale_unit, (bool) $custom->quote_only]);
        $this->assertSame('35.00 ₼ / m²', $custom->priceLabel());

        // On request: no price and no unit to pick — the unit is recorded as m².
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('ferdi', 'sorgu'), ['price' => ''] + $onOrder))->assertOk();
        $quote = Product::latest('id')->firstOrFail();
        $this->assertTrue((bool) $quote->quote_only);
        $this->assertSame('m2', $quote->unit);
        $this->assertStringNotContainsString('0.00', $quote->priceLabel());

        // A custom size must say how it is priced.
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('ferdi'), ['price_basis' => 'm2']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card.'.$this->field($this->schema('foto-divar-kagizlari'), 'kart-divar-ferdi-qiymet')['id']]);
    }

    public function test_a_wallpaper_roll_is_sold_and_stocked_whole(): void
    {
        $schema = $this->schema('divar-kagizlari');
        $card = $this->card($schema, [
            'kart-w01-ust-qat' => 'vinil', 'kart-w01-rulon-eni' => '1.06', 'kart-w01-rulon-uzunlugu' => '10.05', 'kart-divar-reng' => 'bej',
        ]);

        $this->save($this->payload('divar-kagizlari', $card, ['price_basis' => 'roll', 'price' => '45']))->assertOk();
        $roll = Product::firstOrFail();
        $this->assertSame(['roll', 45.0], [$roll->sale_unit, $roll->salePrice()]);
    }

    public function test_a_range_keeps_min_below_max_and_a_kit_lists_its_contents(): void
    {
        $paint = $this->schema('interyer-boyasi');
        $card = fn (string $min, string $max) => $this->card($paint, [
            'kart-w02-novu' => 'akril', 'kart-divar-reng' => 'ag', 'kart-w02-hecm' => '9', 'kart-w02-hecm-vahidi' => 'l',
            'kart-divar-ortme-min' => $min, 'kart-divar-ortme-maks' => $max, 'kart-divar-ortme-vahidi' => 'm2-l',
        ]);

        $this->save($this->payload('interyer-boyasi', $card('12', '9'), ['price_basis' => 'pack']))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$this->field($paint, 'kart-divar-ortme-min')['id']]);
        $this->save($this->payload('interyer-boyasi', $card('9', '12'), ['price_basis' => 'pack']))->assertOk();

        // Microcement sold as a full system must list the kit.
        $micro = $this->schema('mikrosement');
        $base = ['kart-w03-2-forma' => 'iki', 'kart-w03-2-terkib' => 'sistem', 'kart-divar-reng' => 'boz', 'kart-w03-2-ceki' => '20'];
        $dest = 'card.'.$this->field($micro, 'kart-w03-2-dest')['id'];

        $this->save($this->payload('mikrosement', $this->card($micro, $base), ['price_basis' => 'pack']))
            ->assertStatus(422)->assertJsonValidationErrors([$dest]);
        $this->save($this->payload('mikrosement', $this->card($micro, $base + ['kart-w03-2-dest' => 'Baza 16 kq, finiş 4 kq, lak 1 l']), ['price_basis' => 'pack']))
            ->assertOk();
    }

    public function test_admin_shows_a_typed_size_as_another_size_and_saves_it(): void
    {
        $this->save($this->payload('divar-keramik-plitesi', $this->tile(['pair' => CardSchema::OTHER_SIZE, 'length' => '333', 'width' => '777'])))->assertOk();
        $product = Product::firstOrFail();
        $size = $this->field($this->schema('divar-keramik-plitesi'), 'kart-divar-olcu')['id'];

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        $state = CardForm::fillState($product)['card'];
        $this->assertSame(CardSchema::OTHER_SIZE, $state[$size]['pair']);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['card' => array_replace($state, [$size => ['pair' => CardSchema::OTHER_SIZE, 'length' => '330', 'width' => '770']])])
            ->call('save')
            ->assertHasNoFormErrors();

        $row = $product->attributeValues()->where('attribute_id', $size)->firstOrFail();
        $this->assertEquals([330, 770], [(float) $row->value_min, (float) $row->value_numeric]);

        // A listed size is a plain choice again.
        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm(['card' => array_replace($state, [$size => ['pair' => '300×600']])])
            ->call('save')
            ->assertHasNoFormErrors();
        $row = $product->attributeValues()->where('attribute_id', $size)->firstOrFail();
        $this->assertEquals([300, 600], [(float) $row->value_min, (float) $row->value_numeric]);
    }

    public function test_the_cart_page_charges_the_pack_and_badges_follow_availability(): void
    {
        $this->save($this->payload('divar-keramik-plitesi', $this->tile('300×600'), ['price' => '28', 'old_price' => '32']))->assertOk();
        $tile = Product::latest('id')->firstOrFail();
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('ferdi', 'sorgu'), ['price' => '', 'availability' => 'on_order', 'lead_time_days' => 7]))->assertOk();
        $quote = Product::latest('id')->firstOrFail();
        Product::query()->update(['is_approved' => true]);

        // The cart page reprices from the server: one pack, not one m² (28 × 1.44).
        $buyer = User::factory()->create();
        \App\Models\CartItem::create(['user_id' => $buyer->id, 'product_id' => $tile->id, 'quantity' => 2]);
        $html = $this->actingAs($buyer)->get('/cart')->assertOk()->getContent();
        preg_match('/data-server-cart="([^"]*)"/', $html, $m);
        $row = json_decode(html_entity_decode($m[1]), true)[0];
        $this->assertSame([40.32, 'paket'], [$row['price'], $row['unitLabel']]);

        // Catalog: the price carries its unit, a quote has no 0.00 ₼, made to order is no «out of stock».
        $catalog = $this->get('/catalog')->assertOk();
        $catalog->assertSee('28.00 ₼ / m²')->assertSee('-13%')->assertSee('Qiymət sorğu ilə')->assertDontSee('0.00 ₼');
        $this->get('/product/'.$quote->slug)->assertOk()->assertSee('Sorğu ilə');
        $this->assertSame('Sorğu ilə', $quote->stockBadge());

        $tile->update(['availability' => 'on_order']);
        $this->assertSame('Sifarişlə', $tile->fresh()->stockBadge());
    }

    public function test_a_hidden_answer_does_not_keep_its_dependants(): void
    {
        $schema = $this->schema('akustik-paneller');
        $note = $this->field($schema, 'kart-w11-sinaq-qeyd')['id'];
        $card = $this->card($schema, [
            'kart-w11-novu' => 'pet', 'kart-divar-olcu' => '2400×600', 'kart-divar-qalinliq' => '9', 'kart-divar-reng' => 'boz',
            'kart-divar-qablasdirmada-say' => '1',
            // «Göstərilməyib» hides «Sınaq montajı», so its leftover answer must not open the note.
            'kart-w11-gosterici' => 'gosterilmeyib', 'kart-w11-sinaq' => 'bosluq', 'kart-w11-sinaq-qeyd' => '40 mm boşluq',
        ]);

        $this->save($this->payload('akustik-paneller', $card, ['price_basis' => 'piece']))->assertOk();
        $this->assertFalse(Product::firstOrFail()->attributeValues()->where('attribute_id', $note)->exists());
    }

    public function test_admin_starts_a_new_card_with_empty_choices_and_saves_a_quote_without_a_price(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        // Natural stone (W07) asks «Uyğun montaj üsulu» as a multiple choice: on a new
        // product it starts as an empty list, so ticking one box ticks only that one.
        $stone = SubCategory::where('slug', 'divar-ucun-tebii-das')->firstOrFail();
        $mount = $this->field($this->schema('divar-ucun-tebii-das'), 'kart-w07-montaj')['id'];

        $form = Livewire::test(\App\Filament\Resources\ProductResource\Pages\CreateProduct::class)
            ->fillForm(['category_id' => $stone->category_id])
            ->fillForm(['sub_category_id' => $stone->id]);
        $this->assertSame([], $form->get('data.card.'.$mount.'.options'));

        $schema = $this->schema('foto-divar-kagizlari');

        // An existing custom mural switched to «on request» saves with the price left empty.
        $this->save($this->payload('foto-divar-kagizlari', $this->mural('ferdi', 'm2'), ['price_basis' => 'm2', 'price' => '35', 'availability' => 'on_order']))->assertOk();
        $product = Product::firstOrFail();
        $state = CardForm::fillState($product)['card'];
        $mode = $this->field($schema, 'kart-divar-ferdi-qiymet');

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->fillForm([
                'price' => null,
                'card' => array_replace($state, [$mode['id'] => ['option' => collect($mode['options'])->firstWhere('slug', 'sorgu')['id']]]),
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue((bool) $product->fresh()->quote_only);
    }
}
