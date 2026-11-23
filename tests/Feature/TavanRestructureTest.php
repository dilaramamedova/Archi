<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Attribute;
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
 * Tavanlar 38 → 20 (Tavanlar_kartlar_38-20_AZ.xlsx): accessory cards folded into one class
 * per group with a «Növ» field, old classes moved with their products and redirected,
 * kesson and cornice cards, the fire / acoustic fields and the type-bound sales units.
 */
class TavanRestructureTest extends TestCase
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
        $this->brand = Brand::create(['name' => ['az' => 'Knauf'], 'slug' => 'knauf-test', 'is_active' => true]);
        $this->section = Category::create(['name' => ['az' => 'Tavanlar'], 'slug' => 'tavanlar', 'is_active' => true]);
    }

    /** The tree before 23.11: an old accessory group with a class that holds a product. */
    private function oldCableClassWithProduct(): Product
    {
        $group = Category::create(['name' => ['az' => 'Akustik panellər üçün asqı dəstləri'], 'slug' => 'akustik-panel-asqi-destleri', 'parent_id' => $this->section->id, 'is_active' => true]);
        $class = SubCategory::create(['name' => ['az' => 'Asqı trosları'], 'slug' => 'asqi-troslari', 'category_id' => $group->id, 'is_active' => true]);

        return Product::create([
            'user_id' => $this->seller->id, 'category_id' => $group->id, 'sub_category_id' => $class->id,
            'name' => ['az' => 'Tros 1,5 mm'], 'description' => ['az' => ''], 'slug' => 'tros-'.uniqid(),
            'price' => 2, 'stock' => 10,
        ]);
    }

    private function import(): void
    {
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
                default => ['value' => $answer],
            };
        }

        return $card;
    }

    private function save(string $class, array $card, array $extra = [])
    {
        $sub = SubCategory::where('slug', $class)->firstOrFail();

        return $this->actingAs($this->seller)->post('/business/products', array_merge([
            'name' => 'Tavan məhsulu', 'section_id' => $this->section->id, 'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id, 'brand_id' => $this->brand->id, 'origin_country' => 'DE',
            'availability' => 'in_stock', 'price' => '9.80', 'stock' => 100, 'price_basis' => 'piece', 'publish' => 1,
            'card' => $card, 'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra), ['Accept' => 'application/json']);
    }

    public function test_twenty_classes_in_six_groups_and_class_42_closed(): void
    {
        SubCategory::create(['name' => ['az' => 'Divar kağızları (tavanlar üçün)'], 'slug' => 'divar-kagizlari-dekorativ-suvaq-boya-taxta-lambir-tavanlar-ucun',
            'category_id' => Category::create(['name' => ['az' => 'Tavan növləri'], 'slug' => 'tavan-novleri', 'parent_id' => $this->section->id])->id, 'is_active' => true]);
        $this->import();

        $groups = Category::where('parent_id', $this->section->id)->where('is_active', true)->orderBy('sort_order')->get();
        $this->assertSame(
            ['tavan-novleri', 'tavan-profilleri-ve-yonlendiriciler', 'tavan-asqilari-ve-berkidiciler', 'tavan-divarboyu-ve-dekorativ-elementler', 'tavan-isiq-ve-boru-montaj-hisseleri', 'tavan-dekoru'],
            $groups->pluck('slug')->all(),
        );
        $this->assertSame(['Karkas', 'Kənar profilləri', 'Montaj hissələri'], $groups->whereIn('slug', [
            'tavan-profilleri-ve-yonlendiriciler', 'tavan-divarboyu-ve-dekorativ-elementler', 'tavan-isiq-ve-boru-montaj-hisseleri',
        ])->map(fn ($g) => $g->getTranslation('name', 'az'))->values()->all());

        $classes = SubCategory::whereIn('category_id', $groups->pluck('id'))->where('is_active', true)->get();
        $this->assertSame(20, $classes->count());
        $this->assertSame(20, $classes->where('card_enabled', true)->count());
        $this->assertFalse(SubCategory::where('slug', 'divar-kagizlari-dekorativ-suvaq-boya-taxta-lambir-tavanlar-ucun')->value('is_active'));

        // Running it again changes nothing.
        $this->import();
        $this->assertSame(20, SubCategory::whereIn('category_id', $groups->pluck('id'))->where('is_active', true)->count());
    }

    public function test_a_folded_class_moves_its_products_with_their_type_and_its_links_redirect(): void
    {
        $product = $this->oldCableClassWithProduct();
        $this->import();

        $product->refresh();
        $hangers = SubCategory::where('slug', 'birbasa-asqilar')->firstOrFail();
        $this->assertSame([$hangers->id, $hangers->category_id], [$product->sub_category_id, $product->category_id]);
        $type = Attribute::where('slug', 'kart-k2-nov')->firstOrFail();
        $this->assertSame('tros', $product->attributeValues()->where('attribute_id', $type->id)->first()?->option?->slug);

        $this->get('/catalog?class=asqi-troslari&sort=new')->assertStatus(301)
            ->assertRedirect(route('catalog', ['sort' => 'new', 'class' => 'birbasa-asqilar']));
        $this->get('/catalog?group=akustik-panel-asqi-destleri')->assertStatus(301)
            ->assertRedirect(route('catalog', ['group' => 'tavan-asqilari-ve-berkidiciler']));
        $this->get('/catalog?class=birbasa-asqilar')->assertOk();
    }

    public function test_each_type_asks_only_its_own_fields_and_units(): void
    {
        $this->import();
        $schema = $this->schema('birbasa-asqilar');
        $cable = [
            'kart-k2-nov' => 'tros', 'kart-tavan-uygun-tavan' => ['akustik'], 'kart-k2-techizat' => 'ucluqlu-tros',
            'kart-k-tros-material' => 'paslanmayan-polad', 'kart-k-tros-diametr' => '1.5', 'kart-k72-uzunluq' => '3',
            'kart-k72-uc-1' => 'ilgek', 'kart-k72-uc-2' => 'serbest', 'kart-k72-ortuk' => 'yoxdur', 'kart-k72-tutucu' => 'Gripple Hang-Fast',
        ];

        // A cable needs none of the direct hanger's fields, and sells by the metre.
        $this->save('birbasa-asqilar', $this->card($schema, $cable), ['price_basis' => 'lm'])->assertOk();
        $this->assertSame('lm', Product::latest('id')->firstOrFail()->sale_unit);

        // A direct hanger must give its own fields, and cannot be sold by the metre.
        $this->save('birbasa-asqilar', $this->card($schema, ['kart-k2-nov' => 'birbasa', 'kart-tavan-uygun-tavan' => ['alcipan']]), ['price_basis' => 'lm'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['price_basis', 'card.'.$this->field($schema, 'kart-k21-acilmis')['id']])
            ->assertJsonMissingValidationErrors(['card.'.$this->field($schema, 'kart-k72-uzunluq')['id']]);

        // A supply-form option of the cable is not one a rod may pick.
        $rodOnly = $this->field($schema, 'kart-k2-techizat');
        $this->assertEqualsCanonicalizing(['attr' => 'kart-k2-nov', 'in' => ['tros']], collect($rodOnly['options'])->firstWhere('slug', 'rulon')['visible_when']);
    }

    public function test_a_field_with_its_own_condition_needs_both_the_type_and_the_answer(): void
    {
        $this->import();
        $schema = $this->schema('montaj-platformalari');
        $diameter = 'card.'.$this->field($schema, 'kart-tavan-diametr')['id'];
        $ring = [
            'kart-k5-nov' => 'termohalqa', 'kart-tavan-uygun-tavan' => ['dartma'], 'kart-k-forma' => 'dairevi',
            'kart-k52-funksiya' => ['kenar-guclendirme'], 'kart-k52-daxili' => '60', 'kart-k52-xarici' => '90',
            'kart-tavan-qalinliq' => '1', 'kart-k-uygun-ortuk' => ['pvc'], 'kart-tavan-reng' => 'ag', 'kart-k52-montaj' => 'yapisdirma',
        ];

        // A round heat ring: the diameter belongs to platforms and pipe passages only.
        $this->save('montaj-platformalari', $this->card($schema, $ring))->assertOk();

        $platform = [
            'kart-k5-nov' => 'platforma', 'kart-tavan-uygun-tavan' => ['dartma'], 'kart-k-forma' => 'dairevi',
            'kart-k51-teyinat' => 'noqtevi-isiq', 'kart-k51-novu' => 'sabit-olculu', 'kart-k51-qalinliq' => '10',
            'kart-k51-desik' => 'yox', 'kart-k51-esas' => 'metal-asqi',
        ];
        $this->save('montaj-platformalari', $this->card($schema, $platform))->assertStatus(422)->assertJsonValidationErrors([$diameter]);
        $this->save('montaj-platformalari', $this->card($schema, $platform + ['kart-tavan-diametr' => '90']))->assertOk();
    }

    public function test_kesson_cornice_and_the_new_ceiling_fields(): void
    {
        $this->import();

        $kesson = $this->schema('kesson-tavan');
        $this->save('kesson-tavan', $this->card($kesson, [
            'kart-t10-material' => 'gips', 'kart-t10-modul' => '600×600', 'kart-t10-derinlik' => '40', 'kart-tavan-yangin' => 'a1',
        ]), ['price_basis' => 'm2'])->assertOk();
        $this->assertSame('m2', Product::latest('id')->firstOrFail()->sale_unit);

        $cornice = $this->schema('tavan-plintusu-qaltel');
        $this->save('tavan-plintusu-qaltel', $this->card($cornice, [
            'kart-t11-material' => 'poliuretan', 'kart-tavan-hundurluk' => '80', 'kart-t11-cixinti' => '60', 'kart-tavan-uzunluq' => '2000',
        ]), ['price_basis' => 'lm'])->assertOk();

        // RH is a percentage, NRC a coefficient; both optional, both filters.
        $armstrong = $this->schema('asma-tavan-reykalikasetliarmstrongxanalipanelli');
        $this->assertFalse($this->field($armstrong, 'kart-tavan-yangin')['required']);
        $this->assertSame(['max' => 100], $this->field($armstrong, 'kart-tavan-rh')['rules']);
        $this->assertTrue((bool) SubCategory::where('slug', 'asma-tavan-reykalikasetliarmstrongxanalipanelli')->firstOrFail()
            ->attributes()->where('slug', 'kart-tavan-nrc')->first()->pivot->is_filterable);
        $this->assertContains('m2', CardSchema::for(SubCategory::where('slug', 'pvc-tavan-panelleri')->value('id'))['sale']['units']);
    }
}
