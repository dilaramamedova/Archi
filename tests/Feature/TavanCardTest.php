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
 * The Tavanlar workbook (docs/product-cards.md): several groups in one section, the
 * seller's sales unit, documents, a stretch-ceiling roll, whole numbers and the
 * optional country of the accessory cards.
 */
class TavanCardTest extends TestCase
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
        $this->brand = Brand::create(['name' => ['az' => 'Knauf'], 'slug' => 'knauf', 'is_active' => true]);
        $this->section = Category::create(['name' => ['az' => 'Tavanlar'], 'slug' => 'tavanlar', 'is_active' => true]);

        app(CardImporter::class)->importFile('tavan');
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
                'size' => ['pair' => $answer],
                default => is_array($answer) ? $answer : ['value' => $answer],
            };
        }

        return $card;
    }

    private function payload(string $class, array $card, array $extra = []): array
    {
        $sub = SubCategory::where('slug', $class)->firstOrFail();

        return array_merge([
            'name' => 'Tavan məhsulu', 'section_id' => $this->section->id, 'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id, 'brand_id' => $this->brand->id, 'origin_country' => 'DE',
            'availability' => 'in_stock', 'price' => '9.80', 'stock' => 100, 'publish' => 1, 'card' => $card,
            'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra);
    }

    private function save(array $payload)
    {
        return $this->actingAs($this->seller)->post('/business/products', $payload, ['Accept' => 'application/json']);
    }

    /** A complete plasterboard (T02) card; `$doc` is the document input. */
    private function plasterboard(array $doc): array
    {
        $schema = $this->schema('gipskarton-tavan-coxseviyyeli');

        return $this->card($schema, [
            'kart-t02-novu' => 'lovhe',
            'kart-t02-teyinat' => ['standart'],
            'kart-t02-olcu' => '2500×1200',
            'kart-t02-qalinliq' => '12.5',
            'kart-t02-kenar' => 'nazildilmis',
            'kart-t02-sened' => $doc,
            'kart-ortme-sahesi' => '3',
        ]);
    }

    public function test_import_builds_six_groups_and_twenty_cards(): void
    {
        $groups = Category::where('parent_id', $this->section->id)->where('is_active', true)->get();

        $this->assertCount(6, $groups);
        $this->assertSame('tavan-novleri', $groups->sortBy('sort_order')->first()->slug);
        $this->assertSame(20, SubCategory::whereIn('category_id', $groups->pluck('id'))->where('card_enabled', true)->count());
    }

    public function test_a_unit_class_sells_in_the_chosen_unit_and_shows_the_m2_price(): void
    {
        $this->save($this->payload('gipskarton-tavan-coxseviyyeli', $this->plasterboard(['url' => 'https://example.com/knauf.pdf']), ['price_basis' => 'piece']))
            ->assertOk();

        $product = Product::firstOrFail();
        $this->assertSame('piece', $product->unit);
        $this->assertSame('piece', $product->sale_unit);
        $this->assertSame(9.8, $product->salePrice());
        $this->assertSame('3.0000', $product->package_size);

        $product->update(['is_approved' => true]);
        $this->get('/product/'.$product->slug)
            ->assertOk()
            ->assertSee('3.27 ₼/m²')                                  // 9.80 / 3 m²
            ->assertSee('https://example.com/knauf.pdf', false);
    }

    public function test_the_sales_unit_must_be_one_the_class_allows(): void
    {
        $this->save($this->payload('gipskarton-tavan-coxseviyyeli', $this->plasterboard(['url' => 'https://example.com/a.pdf']), ['price_basis' => 'lm']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price_basis']);
    }

    public function test_the_document_is_optional_and_may_be_a_file_or_an_https_link(): void
    {
        $schema = $this->schema('gipskarton-tavan-coxseviyyeli');
        $doc = 'card.'.$this->field($schema, 'kart-t02-sened')['id'];

        // The owner's decision (23.11): no document, no block — the product saves.
        $this->save($this->payload('gipskarton-tavan-coxseviyyeli', $this->plasterboard([]), ['price_basis' => 'piece']))->assertOk();
        Product::query()->delete();

        $this->save($this->payload('gipskarton-tavan-coxseviyyeli', $this->plasterboard(['url' => 'http://example.com/a.pdf']), ['price_basis' => 'piece']))
            ->assertStatus(422)->assertJsonValidationErrors([$doc]);

        $payload = $this->payload('gipskarton-tavan-coxseviyyeli', $this->plasterboard([]), ['price_basis' => 'piece']);
        $payload['card'][$this->field($schema, 'kart-t02-sened')['id']] = ['file' => UploadedFile::fake()->create('pasport.pdf', 120, 'application/pdf')];
        $this->save($payload)->assertOk();

        $path = Product::firstOrFail()->attributeValues()->where('attribute_id', $this->field($schema, 'kart-t02-sened')['id'])->value('value_text');
        $this->assertStringStartsWith('product-docs/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_a_stretch_ceiling_roll_is_sold_whole_a_cut_by_the_m2_and_a_project_on_request(): void
    {
        $schema = $this->schema('dartma-tavan');
        $base = [
            'kart-t01-material' => 'pvc', 'kart-t01-terkib' => 'ortuk', 'kart-t01-seth' => 'mat',
            'kart-t01-en' => '3.2', 'kart-tavan-reng' => 'ag',
        ];

        $this->save($this->payload('dartma-tavan', $this->card($schema, $base + ['kart-t01-tedaruk' => 'rulon', 'kart-t01-rulon-uzunlugu' => '50']), ['price' => '4']))->assertOk();
        $roll = Product::latest('id')->firstOrFail();
        $this->assertSame('roll', $roll->sale_unit);
        $this->assertSame(640.0, $roll->salePrice());                 // 4 × 3.2 × 50

        $this->save($this->payload('dartma-tavan', $this->card($schema, $base + ['kart-t01-tedaruk' => 'kesim']), ['price' => '4']))->assertOk();
        $cut = Product::latest('id')->firstOrFail();
        $this->assertNull($cut->sale_unit);
        $this->assertSame('m2', $cut->unit);
        $this->assertSame(4.0, $cut->salePrice());

        $this->save($this->payload('dartma-tavan', $this->card($schema, ['kart-t01-terkib' => 'montajla', 'kart-t01-tedaruk' => 'kesim'] + $base), ['price' => '']))->assertOk();
        $this->assertTrue(Product::latest('id')->firstOrFail()->quote_only);
    }

    public function test_accessories_leave_the_country_optional_and_counts_must_be_whole(): void
    {
        // «Boru keçidi» is now a type of «Montaj hissələri».
        $schema = $this->schema('montaj-platformalari');
        $card = fn (string $count) => $this->card($schema, [
            'kart-k5-nov' => 'boru-kecidi', 'kart-tavan-uygun-tavan' => ['dartma'],
            'kart-k53-novu' => 'xarici-dekorativ-qapaq', 'kart-k53-say' => $count, 'kart-k53-boru' => '20, 20',
            'kart-k53-desik' => '21, 21', 'kart-k-forma' => 'dairevi', 'kart-tavan-diametr' => '90',
            'kart-tavan-qalinliq' => '2', 'kart-k-uygun-ortuk' => ['pvc'], 'kart-k53-montaj' => 'yapisdirma',
        ]);
        $extra = ['origin_country' => '', 'price_basis' => 'piece'];

        $this->save($this->payload('montaj-platformalari', $card('1.5'), $extra))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-k53-say')['id']])
            ->assertJsonMissingValidationErrors(['origin_country']);

        $this->save($this->payload('montaj-platformalari', $card('2'), $extra))->assertOk();
        $this->assertNull(Product::firstOrFail()->origin_country);
    }

    public function test_admin_edits_a_unit_class_product_with_a_document_link(): void
    {
        $this->save($this->payload('gipskarton-tavan-coxseviyyeli', $this->plasterboard(['url' => 'https://example.com/a.pdf']), ['price_basis' => 'pack']))
            ->assertOk();
        $product = Product::firstOrFail();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        $schema = $this->schema('gipskarton-tavan-coxseviyyeli');
        $doc = $this->field($schema, 'kart-t02-sened')['id'];
        $state = CardForm::fillState($product)['card'];
        $this->assertSame(['url' => 'https://example.com/a.pdf'], $state[$doc]);

        Livewire::test(EditProduct::class, ['record' => $product->getRouteKey()])
            ->assertFormSet(['price_basis' => 'pack'])
            ->fillForm(['price_basis' => 'piece', 'card' => array_replace($state, [$doc => ['url' => 'https://example.com/b.pdf']])])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('piece', $product->unit);
        $this->assertSame('https://example.com/b.pdf', $product->attributeValues()->where('attribute_id', $doc)->value('value_text'));
    }
}
