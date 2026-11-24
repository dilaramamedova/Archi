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
 * The İqlim workbook (docs/product-cards.md): 37 cards (H01–H15_5) in 4 blocks — heating,
 * air-conditioning, ventilation, controls and accessories — with the cooling, heat,
 * airflow and supply blocks and units tied to the card's answers.
 */
class IqlimCardTest extends TestCase
{
    use RefreshDatabase;

    private User $seller;

    private Brand $brand;

    private Category $section;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->seller = User::factory()->seller()->create();
        $this->brand = Brand::create(['name' => ['az' => 'Fujitsu'], 'slug' => 'fujitsu-test', 'is_active' => true]);
        $this->section = Category::create(['name' => ['az' => 'İstilik, ventilyasiya və kondisionerləşdirmə'], 'slug' => 'istilik-ventilyasiya-ve-kondisionerlesdirme', 'is_active' => true]);

        app(CardImporter::class)->importFile('iqlim');
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
                default => ['value' => $answer],
            };
        }

        return $card;
    }

    private function save(string $class, array $card, array $extra = [])
    {
        $sub = SubCategory::where('slug', $class)->firstOrFail();

        return $this->actingAs($this->seller)->post('/business/products', array_merge([
            'name' => 'İqlim məhsulu', 'section_id' => $this->section->id, 'category_id' => $sub->category_id,
            'sub_category_id' => $sub->id, 'brand_id' => $this->brand->id, 'origin_country' => 'CN',
            'availability' => 'in_stock', 'price' => '1450', 'stock' => 3, 'price_basis' => 'set', 'publish' => 1,
            'card' => $card, 'images' => [UploadedFile::fake()->image('p.jpg')],
        ], $extra), ['Accept' => 'application/json']);
    }

    /** A complete wall split (H07_1). */
    private function split(array $override = []): array
    {
        return array_merge([
            'kart-iq-isitme-rejimi' => 'var', 'kart-iq-kompressor' => 'inverter', 'kart-iq-soyuducu-agent' => 'r32', 'kart-iq-cereyan-novu' => 'ac',
            'kart-iq-faza' => '1', 'kart-iq-enerji-sinfi' => 'a2', 'kart-iq-ses-novu' => 'tezyiq', 'kart-iq-idareetme' => ['simsiz-pult'],
            'kart-iq-daxili-model' => 'AS-09UW4', 'kart-iq-xarici-model' => 'AU-09UW4', 'kart-iq-soyutma-gucu' => '2.6', 'kart-iq-isitme-gucu' => '2.8',
            'kart-iq-giris-soyutma' => '0.75', 'kart-iq-giris-isitme' => '0.72', 'kart-iq-gerginlik' => '220', 'kart-iq-tezlik' => '50',
            'kart-iq-en' => '805', 'kart-iq-derinlik' => '200', 'kart-iq-hundurluk' => '285', 'kart-iq-xarici-blok-olculeri' => '720 × 270 × 495',
            'kart-iq-ses' => '24', 'kart-iq-ses-mesafe' => '1',
            'kart-iq-xarici-soyutma-min' => '−15', 'kart-iq-xarici-soyutma-maks' => '48',
            'kart-iq-xarici-isitme-min' => '-25', 'kart-iq-xarici-isitme-maks' => '24',
        ], $override);
    }

    public function test_outdoor_temperatures_may_be_negative_within_the_card_limits(): void
    {
        $schema = $this->schema('kondisioner-split-sistem-multi-split-vrf');
        $min = $this->field($schema, 'kart-iq-xarici-isitme-min')['id'];

        // «−15» with a typographic minus and «-25» both store as negatives.
        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $this->split()))->assertOk();
        $this->assertSame(-25.0, (float) Product::latest('id')->firstOrFail()->attributeValues()->where('attribute_id', $min)->value('value_numeric'));

        // Below the card's floor (−60), or a minimum above the maximum, is refused.
        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $this->split(['kart-iq-xarici-isitme-min' => '-70'])))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$min]);
        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $this->split(['kart-iq-xarici-isitme-min' => '30'])))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$min]);

        // A plain measure stays positive.
        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $this->split(['kart-iq-en' => '-805'])))
            ->assertStatus(422)->assertJsonValidationErrors(['card.'.$this->field($schema, 'kart-iq-en')['id']]);
    }

    public function test_import_builds_four_blocks_with_thirty_seven_cards(): void
    {
        $groups = Category::where('parent_id', $this->section->id)->where('is_active', true)->get();
        $cards = SubCategory::whereIn('category_id', $groups->pluck('id'))->where('card_enabled', true)->get();

        $this->assertSame(37, $cards->count());
        $this->assertSame(4, $cards->pluck('category_id')->unique()->count());
        $this->assertSame('H07_1', $cards->firstWhere('slug', 'kondisioner-split-sistem-multi-split-vrf')->card_settings['code']);

        app(CardImporter::class)->importFile('iqlim');
        $this->assertSame(37, SubCategory::where('card_enabled', true)->count());
    }

    public function test_a_split_sells_as_a_set_and_asks_heating_only_when_it_heats(): void
    {
        $schema = $this->schema('kondisioner-split-sistem-multi-split-vrf');

        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $this->split()))->assertOk();
        $this->assertSame('set', Product::latest('id')->firstOrFail()->sale_unit);

        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $this->split()), ['price_basis' => 'piece'])
            ->assertStatus(422)->assertJsonValidationErrors(['price_basis']);

        $missing = $this->split();
        unset($missing['kart-iq-soyuducu-agent'], $missing['kart-iq-isitme-gucu']);
        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $missing))->assertStatus(422)->assertJsonValidationErrors([
            'card.'.$this->field($schema, 'kart-iq-soyuducu-agent')['id'],
            'card.'.$this->field($schema, 'kart-iq-isitme-gucu')['id'],
        ]);

        // Cooling only: no heating capacity asked.
        $cool = $this->split(['kart-iq-isitme-rejimi' => 'yoxdur']);
        unset($cool['kart-iq-isitme-gucu'], $cool['kart-iq-giris-isitme']);
        $this->save('kondisioner-split-sistem-multi-split-vrf', $this->card($schema, $cool))->assertOk();
    }

    public function test_a_heating_cable_sold_by_the_metre_follows_its_sales_form(): void
    {
        $schema = $this->schema('isti-doseme-isitme-kabelleri');
        $cable = $this->card($schema, [
            'kart-h06-2-satis' => 'metr', 'kart-h06-2-nov' => 'ikidamarli', 'kart-iq-cereyan-novu' => 'ac', 'kart-iq-faza' => '1',
            'kart-iq-xususi-guc' => '18', 'kart-iq-gerginlik' => '230', 'kart-iq-doseme-montaj' => ['sap'],
        ]);

        $this->save('isti-doseme-isitme-kabelleri', $cable, ['price_basis' => 'lm', 'price' => '9'])->assertOk();
        $this->assertSame('lm', Product::latest('id')->firstOrFail()->sale_unit);
        $this->save('isti-doseme-isitme-kabelleri', $cable)->assertStatus(422)->assertJsonValidationErrors(['price_basis']);
    }

    public function test_the_towel_warmer_and_duct_fans_fold_into_their_cards(): void
    {
        $old = Category::create(['name' => ['az' => 'Santexnika'], 'slug' => 'santexnika-test', 'is_active' => true]);
        $warmer = SubCategory::create(['name' => ['az' => 'Dəsmal quruducu'], 'slug' => 'desmal-qurudan', 'category_id' => $old->id, 'is_active' => true]);
        app(CardImporter::class)->importFile('iqlim');

        $this->assertFalse($warmer->fresh()->is_active);
        $this->assertSame('desmalqurudanlar', $warmer->fresh()->card_settings['merged_into']);
        $this->assertSame('H03', SubCategory::where('slug', 'desmalqurudanlar')->firstOrFail()->card_settings['code']);
    }
}
