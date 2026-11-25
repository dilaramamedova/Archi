<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\SubCategory;
use App\Services\ProductCard\CardImporter;
use App\Services\ProductCard\CardProduct;
use App\Services\ProductCard\CardSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * The Qapılar, pəncərələr və şüşələmə workbook (docs/product-cards.md): 31 cards
 * (D01_1–D12) in four blocks — doors, windows, glazing, hardware — attributes
 * prefixed kart-qp-* / kart-qdNN-*.
 */
class QapiCardTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::create(['name' => ['az' => 'Dierre'], 'slug' => 'dierre-test', 'is_active' => true]);
        $section = Category::create(['name' => ['az' => 'Qapılar, pəncərələr və şüşələmə'], 'slug' => 'qapilar-pencereler-ve-suseleme', 'is_active' => true]);
        // The prod tree before the import: a plain hidden-door class that folds into D01_1.
        $old = Category::create(['parent_id' => $section->id, 'name' => ['az' => 'Xüsusi qapı növləri'], 'slug' => 'xususi-qapi-novleri', 'is_active' => true]);
        SubCategory::create(['category_id' => $old->id, 'slug' => 'gizli-qapi-invisible', 'name' => ['az' => 'Gizli qapı (invisible)'], 'is_active' => true]);

        app(CardImporter::class)->importFile('qapi');
    }

    private function schema(string $slug): array
    {
        return CardSchema::for(SubCategory::where('slug', $slug)->firstOrFail()->id);
    }

    /** @param array<string, mixed> $answers dropdown → option slug, multiselect → list, size → [l, w], else value */
    private function card(array $schema, array $answers): array
    {
        $card = [];
        foreach ($answers as $slug => $answer) {
            $field = collect($schema['fields'])->firstWhere('slug', $slug) ?? throw new RuntimeException("no field {$slug}");
            $option = fn ($o) => collect($field['options'])->firstWhere('slug', $o)['id'] ?? throw new RuntimeException("no option {$o} in {$slug}");
            $card[$field['id']] = match ($field['type']) {
                'dropdown' => ['option' => $option($answer)],
                'multiselect' => ['options' => array_map($option, (array) $answer)],
                'size' => ['length' => $answer[0], 'width' => $answer[1]],
                default => ['value' => $answer],
            };
        }

        return $card;
    }

    private function check(array $schema, array $card, string $unit): array
    {
        return CardProduct::check($schema, [
            'brand_id' => $this->brand->id, 'origin_country' => 'TR', 'availability' => 'in_stock', 'price' => '250', 'price_basis' => $unit,
        ], $card, true);
    }

    public function test_import_builds_four_blocks_with_thirty_one_cards_and_folds_the_old_classes(): void
    {
        $section = Category::where('slug', 'qapilar-pencereler-ve-suseleme')->firstOrFail();
        $groups = Category::where('parent_id', $section->id)->where('is_active', true)->orderBy('sort_order')->pluck('slug')->all();
        $this->assertSame(['qapilar', 'pencere-bloklari', 'suseleme', 'komplektlesdiriciler-ve-furnitura'], $groups);
        $this->assertSame(31, SubCategory::where('card_enabled', true)->where('is_active', true)->count());

        $hidden = SubCategory::where('slug', 'gizli-qapi-invisible')->first();
        $this->assertFalse((bool) $hidden->is_active);
        $this->assertSame('otaqlararasi-qapi', $hidden->card_settings['merged_into']);
        $this->get('/catalog?class=gizli-qapi-invisible')->assertStatus(301)->assertRedirect(route('catalog', ['class' => 'otaqlararasi-qapi']));
        $this->get('/catalog?group=xususi-qapi-novleri')->assertStatus(301);

        // Material, colour and opening type are filters (Kateqoriyalar sheet).
        $door = SubCategory::where('slug', 'otaqlararasi-qapi')->first();
        foreach (['kart-qp-polotno-material', 'kart-qp-reng', 'kart-qp-acilma-usulu'] as $slug) {
            $this->assertTrue($door->attributes()->where('slug', $slug)->wherePivot('is_filterable', true)->exists(), $slug);
        }

        app(CardImporter::class)->importFile('qapi');
        $this->assertSame(31, SubCategory::where('card_enabled', true)->where('is_active', true)->count());
    }

    public function test_an_interior_door_sells_as_a_leaf_or_a_set_and_needs_its_size(): void
    {
        $schema = $this->schema('otaqlararasi-qapi');
        $door = $this->card($schema, [
            'kart-qp-polotno-material' => 'mdf', 'kart-qp-acilma-usulu' => 'acilan', 'kart-qp-acilma-istiqameti' => 'sol',
            'kart-qd01-1-qutu-novu' => 'gizli', 'kart-qp-reng' => 'ag', 'kart-qp-ortuk' => 'boya',
            'kart-qp-qanad-sayi' => '1', 'kart-qp-polotno-qalinligi' => '40', 'kart-qp-olculer' => [800, 2000],
        ]);

        $this->assertSame([], $this->check($schema, $door, 'piece')['errors']);
        $this->assertSame('set', $this->check($schema, $door, 'set')['columns']['sale_unit']);
        $this->assertArrayHasKey('price_basis', $this->check($schema, $door, 'm2')['errors']);

        $sizeId = collect($schema['fields'])->firstWhere('slug', 'kart-qp-olculer')['id'];
        $noSize = $door;
        unset($noSize[$sizeId]);
        $this->assertArrayHasKey('card.'.$sizeId, $this->check($schema, $noSize, 'piece')['errors']);

        // Two different sides: the second colour is then required.
        $twoSides = $door + $this->card($schema, ['kart-qp-terefler' => 'ferqli']);
        $colour2 = collect($schema['fields'])->firstWhere('slug', 'kart-qp-reng-2')['id'];
        $this->assertArrayHasKey('card.'.$colour2, $this->check($schema, $twoSides, 'piece')['errors']);
    }

    public function test_a_window_sells_by_the_m2_and_burglary_resistance_needs_its_class_and_document(): void
    {
        $schema = $this->schema('pencere-bloku');
        $window = $this->card($schema, [
            'kart-qp-profil' => 'pvc', 'kart-qp-pencere-acilma' => 'acilib-qatlanan', 'kart-qp-acilma-istiqameti' => 'sag',
            'kart-qp-reng' => 'ag', 'kart-qp-ortuk' => 'ortuksuz', 'kart-qp-suse' => 'susepaket',
            'kart-qp-profil-sistem' => 'Rehau Synego', 'kart-qp-qanad-sayi' => '2', 'kart-qp-acilan-qanad' => '1', 'kart-qp-profil-kamera' => '7',
            'kart-qp-susepaket-formulu' => '4-16-4-16-4', 'kart-qp-olculer' => [1200, 1400], 'kart-qp-tesdiq' => ['gosterilmeyib'],
        ]);

        $this->assertSame('m2', $this->check($schema, $window, 'm2')['columns']['sale_unit']);
        $this->assertSame([], $this->check($schema, $window, 'm2')['errors']);
        $this->assertArrayHasKey('price_basis', $this->check($schema, $window, 'set')['errors']);

        $burglary = array_replace($window, $this->card($schema, ['kart-qp-tesdiq' => ['ogurluq']]));
        $errors = $this->check($schema, $burglary, 'm2')['errors'];
        foreach (['kart-qp-rc', 'kart-qp-sened'] as $slug) {
            $this->assertArrayHasKey('card.'.collect($schema['fields'])->firstWhere('slug', $slug)['id'], $errors, $slug);
        }
    }

    public function test_hinges_sell_by_the_pair_and_name_the_model_they_fit(): void
    {
        $schema = $this->schema('qapi-menteseleri');
        $hinge = $this->card($schema, [
            'kart-qd10-2-nov' => 'gizli', 'kart-qp-furnitura-material' => 'metal-erintisi', 'kart-qp-furnitura-reng' => 'qara', 'kart-qp-seth' => 'mat',
            'kart-qp-acilma-istiqameti' => 'universal', 'kart-qp-uygun-model' => 'AGB Eclipse 3.0', 'kart-qp-qapi-qalinligi' => '40',
            'kart-qd10-2-oturma' => 'falssız', 'kart-qp-yuk-heddi' => '60', 'kart-qd10-2-olculer' => '120 × 24 × 30',
            'kart-qd10-2-qapi-materiali' => ['agac-mdf'],
        ]);

        $this->assertSame([], $this->check($schema, $hinge, 'pair')['errors']);
        $this->assertArrayHasKey('price_basis', $this->check($schema, $hinge, 'lm')['errors']);
    }
}
