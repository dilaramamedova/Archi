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
 * The Mebel workbook (docs/product-cards.md): 64 cards in one section — furniture
 * M01_1–M15_2 (mebel.php, groups 0–14) and hardware F01_1–F12_4 (mebel_furnitura.php,
 * groups 15–26, explicit sort_order).
 */
class MebelCardTest extends TestCase
{
    use RefreshDatabase;

    private const SECTION = 'mebel-komplektlesdiriciler-furnitura-ve-mexanizmler';

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->brand = Brand::create(['name' => ['az' => 'Blum'], 'slug' => 'blum-test', 'is_active' => true]);
        $section = Category::create(['name' => ['az' => 'Mebel'], 'slug' => self::SECTION, 'is_active' => true]);
        // Two old prod classes: a bar stool that folds into chairs, a generic hardware class into hinges.
        $old = Category::create(['parent_id' => $section->id, 'name' => ['az' => 'Yemək qrupu'], 'slug' => 'yemek-qrupu', 'is_active' => true]);
        SubCategory::create(['category_id' => $old->id, 'slug' => 'bar-stulu', 'name' => ['az' => 'Bar stulu'], 'is_active' => true]);
        SubCategory::create(['category_id' => $old->id, 'slug' => 'mebel-furniturasi-ve-mexanizmleri-menteseler-istiqametlendiriciler-qaldiricilar-kargo-butulka-refleri-kunc-mexanizmleri', 'name' => ['az' => 'Mebel furniturası'], 'is_active' => true]);

        // Same order as the migration and ProductCardSeeder::FILES.
        app(CardImporter::class)->importFile('mebel_furnitura');
        app(CardImporter::class)->importFile('mebel');
    }

    private function schema(string $slug): array
    {
        return CardSchema::for(SubCategory::where('slug', $slug)->firstOrFail()->id);
    }

    private function card(array $schema, array $answers): array
    {
        $card = [];
        foreach ($answers as $slug => $answer) {
            $field = collect($schema['fields'])->firstWhere('slug', $slug) ?? throw new RuntimeException("no field {$slug}");
            $option = fn ($o) => collect($field['options'])->firstWhere('slug', $o)['id'] ?? throw new RuntimeException("no option {$o} in {$slug}");
            $card[$field['id']] = match ($field['type']) {
                'dropdown' => ['option' => $option($answer)],
                'multiselect' => ['options' => array_map($option, (array) $answer)],
                default => ['value' => $answer],
            };
        }

        return $card;
    }

    private function errors(array $schema, array $card, string $unit): array
    {
        return CardProduct::check($schema, [
            'brand_id' => $this->brand->id, 'origin_country' => 'AT', 'availability' => 'in_stock', 'price' => '120', 'price_basis' => $unit,
        ], $card, true)['errors'];
    }

    public function test_import_builds_twenty_seven_groups_in_workbook_order_with_sixty_four_cards(): void
    {
        $section = Category::where('slug', self::SECTION)->firstOrFail();
        $groups = Category::where('parent_id', $section->id)->where('is_active', true)->orderBy('sort_order')->get();
        $cards = SubCategory::whereIn('category_id', $groups->pluck('id'))->where('card_enabled', true)->where('is_active', true)->get();

        $this->assertSame(64, $cards->count());
        $this->assertSame(27, $groups->filter(fn ($g) => $cards->contains('category_id', $g->id))->count());
        // Furniture first (0–14), then hardware (15–26).
        $this->assertSame('divanlar', $groups->first()->slug);
        $this->assertSame(15, $groups->firstWhere('slug', 'mebel-tutacaqlari')->sort_order);
        $this->assertSame(26, $groups->firstWhere('slug', 'hazir-mebel-hisseleri')->sort_order);
        $this->assertSame(['M01_1', 'F12_4'], [
            $cards->firstWhere('slug', 'divan-yumsaq-mebel')->card_settings['code'],
            $cards->firstWhere('slug', 'mebel-sokollari-ve-tamamlayici-profilleri')->card_settings['code'],
        ]);

        // Old classes fold in and their links follow.
        $this->assertSame('stul', SubCategory::where('slug', 'bar-stulu')->first()->card_settings['merged_into']);
        $this->get('/catalog?class=bar-stulu')->assertStatus(301)->assertRedirect(route('catalog', ['class' => 'stul']));
        $this->assertFalse((bool) Category::where('slug', 'yemek-qrupu')->value('is_active'));

        // Re-import in the same order changes nothing.
        app(CardImporter::class)->importFile('mebel_furnitura');
        app(CardImporter::class)->importFile('mebel');
        $this->assertSame(64, SubCategory::where('card_enabled', true)->where('is_active', true)->count());
    }

    public function test_a_mattress_sells_by_the_piece_and_needs_its_firmness(): void
    {
        $schema = $this->schema('dosek');
        $mattress = $this->card($schema, [
            'kart-mb-istehsal-varianti' => 'hazir', 'kart-m05-2-uzunluq' => '2000', 'kart-m05-2-en' => '1600', 'kart-m05-2-hundurluk' => '240',
            'kart-m05-2-qurulus' => 'musteqil-yay', 'kart-m05-2-dolgu' => ['pu-kopuk', 'lateks'], 'kart-m05-2-sertlik' => 'orta',
            'kart-zemanet' => 'var', 'kart-zemanet-muddeti' => '60',
        ]);

        $this->assertSame([], $this->errors($schema, $mattress, 'piece'));
        $this->assertArrayHasKey('price_basis', $this->errors($schema, $mattress, 'set'));

        $firmness = collect($schema['fields'])->firstWhere('slug', 'kart-m05-2-sertlik')['id'];
        unset($mattress[$firmness]);
        $this->assertArrayHasKey('card.'.$firmness, $this->errors($schema, $mattress, 'piece'));
    }

    public function test_drawer_runners_sell_by_the_pair_and_a_load_names_what_it_is_for(): void
    {
        $schema = $this->schema('cekmece-relsleri');
        $answers = [
            'kart-f03-1-tip' => 'gizli', 'kart-f03-1-satis' => 'cut', 'kart-f03-1-acilma' => 'tam', 'kart-f03-1-montaj-yeri' => 'alt',
            'kart-mf-hereket-funksiyasi' => 'yumsaq-baglanma', 'kart-mf-nominal-uzunluq' => '450', 'kart-mf-uygun-model' => 'Blum Tandem 560H',
        ];
        $pair = $this->card($schema, $answers);

        $this->assertSame([], $this->errors($schema, $pair, 'pair'));
        $this->assertArrayHasKey('price_basis', $this->errors($schema, $pair, 'piece'));

        // A load scope without the kilograms is incomplete; with them it passes.
        $kg = collect($schema['fields'])->firstWhere('slug', 'kart-mf-yuk-heddi')['id'];
        $scoped = $this->card($schema, $answers + ['kart-mf-yuk-aiddir' => 'cut']);
        $this->assertArrayHasKey('card.'.$kg, $this->errors($schema, $scoped, 'pair'));
        $this->assertSame([], $this->errors($schema, $this->card($schema, $answers + ['kart-mf-yuk-aiddir' => 'cut', 'kart-mf-yuk-heddi' => '40']), 'pair'));
    }
}
