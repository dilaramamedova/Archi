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
 * The Tekstil (39 cards, 3 blocks) and Landşaft (48 cards, 12 groups) workbooks
 * (docs/product-cards.md): the tree, a complete card each, conditional fields and
 * the sales units each card allows.
 */
class TekstilLandsaftCardTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Brand $brand;

    /** @var array<string, Category> */
    private array $sections = [];

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seller = User::factory()->seller()->create();
        $this->brand = Brand::create(['name' => ['az' => 'Brend'], 'slug' => 'brend-test', 'is_active' => true]);
        foreach ([
            'tekstil' => 'tekstil-perdeler-gunesden-qorunma-ve-interyer-dekoru',
            'landsaft' => 'landsaft-ve-abadlasdirma',
        ] as $file => $slug) {
            $this->sections[$file] = Category::create(['name' => ['az' => $file], 'slug' => $slug, 'is_active' => true]);
            app(CardImporter::class)->importFile($file);
        }
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
                default => is_array($answer) ? $answer : ['value' => $answer],
            };
        }

        return $card;
    }

    private function save(string $file, string $class, array $card, array $extra = [])
    {
        $sub = SubCategory::where('slug', $class)->firstOrFail();

        return $this->actingAs($this->seller)->post('/business/products', array_merge([
            'name' => 'Məhsul', 'section_id' => $this->sections[$file]->id, 'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id, 'brand_id' => $this->brand->id, 'origin_country' => 'TR',
            'availability' => 'in_stock', 'price' => '25', 'stock' => 10, 'price_basis' => 'piece', 'publish' => 1,
            'card' => $card, 'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra), ['Accept' => 'application/json']);
    }

    private function cardClasses(string $file): \Illuminate\Support\Collection
    {
        $groups = Category::where('parent_id', $this->sections[$file]->id)->where('is_active', true)->pluck('id');

        return SubCategory::whereIn('category_id', $groups)->where('card_enabled', true)->get();
    }

    public function test_both_workbooks_build_their_trees(): void
    {
        $this->assertSame(39, $this->cardClasses('tekstil')->count());
        $this->assertSame(48, $this->cardClasses('landsaft')->count());

        $textileGroups = Category::where('parent_id', $this->sections['tekstil']->id)->where('is_active', true)->orderBy('sort_order')->pluck('slug')->all();
        $this->assertSame(['perdeler-ve-gunesden-muhafize', 'ev-tekstili', 'interyer-dekoru'], $textileGroups);
        $this->assertSame(12, $this->cardClasses('landsaft')->pluck('category_id')->unique()->count());

        // Re-running changes nothing.
        app(CardImporter::class)->importFile('tekstil');
        app(CardImporter::class)->importFile('landsaft');
        $this->assertSame(87, $this->cardClasses('tekstil')->count() + $this->cardClasses('landsaft')->count());
    }

    public function test_a_towel_card_and_its_care_fields(): void
    {
        $schema = $this->schema('desmal');
        $towel = [
            'kart-tx12-2-teyinat' => 'hamam', 'kart-tx-reng' => 'ag', 'kart-tx-naxis' => 'tek-reng', 'kart-tx-yuma' => 'masin',
            'kart-tx-parca-terkibi' => ['pambiq'], 'kart-tx-terkib-faiz' => '100% pambıq',
            'kart-tx-olcu' => ['length' => '140', 'width' => '70'], 'kart-tx-yuma-temp' => '60',
        ];

        $this->save('tekstil', 'desmal', $this->card($schema, $towel), ['price_basis' => 'set'])->assertOk();
        $this->assertSame('set', Product::latest('id')->firstOrFail()->sale_unit);

        unset($towel['kart-tx-reng']);
        $this->save('tekstil', 'desmal', $this->card($schema, $towel))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-tx-reng')['id']]);

        // The wash temperature belongs to washable items only; fabric sells by the metre, not the piece.
        $this->assertEqualsCanonicalizing(['masin', 'zerif', 'elle'], $this->field($schema, 'kart-tx-yuma-temp')['visible_when']['in']);
        $this->save('tekstil', 'perdelik-uzluk-parca', [], ['price_basis' => 'piece'])->assertStatus(422)->assertJsonValidationErrors(['price_basis']);
    }

    public function test_a_tree_sells_by_the_piece_and_a_dining_set_lists_its_contents(): void
    {
        $tree = $this->schema('bitkiler-ve-qazon');
        $card = $this->card($tree, [
            'kart-ls-kok' => 'konteyner', 'kart-ls-isiq-teleb' => 'gunes', 'kart-l05-4-qrup' => 'agac',
            'kart-ls-bitki' => 'Qara şam — Pinus nigra', 'kart-ls-satis-hundurluk' => '150',
        ]);
        $this->save('landsaft', 'bitkiler-ve-qazon', $card)->assertOk();
        $this->save('landsaft', 'bitkiler-ve-qazon', $card, ['price_basis' => 'set'])->assertStatus(422)->assertJsonValidationErrors(['price_basis']);

        $dining = $this->schema('bag-masalari-ve-stullari');
        $set = [
            'kart-l08-1-tip' => 'dest', 'kart-ls-karkas' => 'aluminium', 'kart-ls-seth-material' => 'suni-rattan', 'kart-ls-reng' => 'antrasit',
            'kart-ls-seth' => 'mat', 'kart-ls-uzunluq' => '1600', 'kart-ls-en' => '900', 'kart-ls-hundurluk' => '750',
        ];
        $this->save('landsaft', 'bag-masalari-ve-stullari', $this->card($dining, $set), ['price_basis' => 'set'])
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$this->field($dining, 'kart-ls-dest-terkibi')['id']]);
        $this->save('landsaft', 'bag-masalari-ve-stullari', $this->card($dining, $set + ['kart-ls-dest-terkibi' => '1 masa 160×90; 6 stul']), ['price_basis' => 'set'])
            ->assertOk();
    }

    public function test_old_duplicate_classes_and_groups_are_folded(): void
    {
        // Lamps moved to the Elektrik section; drainage duplicates folded into one group.
        $this->assertTrue(Category::where('slug', 'interyer-dekoru')->value('is_active'));
        $this->assertNull(Category::where('slug', 'drenaj-ve-geosintetika')->where('is_active', true)->first());
        $this->assertSame('L01_1', SubCategory::where('slug', 'seki-dasi-seki-plitesi')->firstOrFail()->card_settings['code']);
    }
}
