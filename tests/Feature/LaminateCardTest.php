<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DictionaryRequestStatus;
use App\Models\AuditEvent;
use App\Models\Brand;
use App\Models\Category;
use App\Models\DictionaryRequest;
use App\Models\LaminateSpec;
use App\Models\LaminateSpecDocument;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Laminate\LaminateCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The laminate card against the acceptance list of the TZ «Ламинат» (§10).
 * Each test names the acceptance item it covers. Server rules are checked through
 * HTTP requests, not only through the form (§10: «Серверные зависимости и права
 * проверять запросами API»).
 */
final class LaminateCardTest extends TestCase
{
    use RefreshDatabase;

    private SubCategory $class;

    private Category $group;

    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('local');

        $section = Category::create(['name' => ['az' => 'Döşəmələr'], 'slug' => 'dosemeler', 'is_active' => true]);
        $this->group = Category::create(['name' => ['az' => 'Polimer'], 'slug' => 'polimer', 'parent_id' => $section->id, 'is_active' => true]);
        $this->class = SubCategory::create([
            'name' => ['az' => 'Laminat'], 'slug' => LaminateCatalog::CLASS_SLUG,
            'category_id' => $this->group->id, 'is_active' => true,
        ]);
        $this->brand = Brand::create(['name' => ['az' => 'Kronospan'], 'slug' => 'kronospan', 'is_active' => true]);

        // The dictionaries come from a migration; a truncating test that ran earlier
        // in the suite (CommitsToDatabase) wipes them. Also forgets memoised ids.
        LaminateCatalog::ensureDictionaries();
    }

    // ── 1. Choosing laminate opens the twelve parameters, no colour/rooms ──────

    public function test_1_laminate_form_has_the_twelve_parameters_and_no_colour_or_rooms(): void
    {
        $schema = $this->actingAs($this->seller())
            ->getJson("/business/api/sub-categories/{$this->class->id}/form")
            ->assertOk()
            ->json();

        $this->assertSame([], $schema['fields'], 'no generic class fields for laminate');
        $this->assertSame([], $schema['applications'], 'no "where to use" tags');
        $this->assertContains('color', $schema['laminate']['hide_generic']);
        $this->assertContains('applications', $schema['laminate']['hide_generic']);
        $this->assertSame(['31', '32', '33', '34'], array_column($schema['laminate']['dictionaries']['usage_class'], 'code'));
        $this->assertSame(['ac3', 'ac4', 'ac5', 'ac6'], array_column($schema['laminate']['dictionaries']['abrasion'], 'code'));
        $this->assertSame(['click', 'glued_tongue_groove'], array_column($schema['laminate']['dictionaries']['connection'], 'code'));
        $this->assertSame(['6', '7', '8', '9', '10', '12'], $schema['laminate']['picks']['thickness_mm']);
        $this->assertCount(249, $schema['laminate']['countries']);
    }

    // ── 2. An existing article is found to copy from ───────────────────────────

    public function test_2_an_approved_card_is_found_by_brand_and_article(): void
    {
        $product = $this->publishedCard($this->seller('first@example.com'), ['manufacturer_sku' => 'KR-8031']);

        $matches = $this->actingAs($this->seller('second@example.com'))
            ->getJson('/business/api/laminate/lookup?brand_id='.$this->brand->id.'&sku='.urlencode('  kr-8031 '))
            ->assertOk()
            ->json('matches');

        $this->assertCount(1, $matches);
        $this->assertSame($product->id, $matches[0]['product_id']);
        $this->assertSame('8', $matches[0]['values']['thickness_mm']);
        $this->assertArrayNotHasKey('documents', $matches[0]['values'], 'another seller\'s files are never copied');
    }

    // ── 3. A new brand is a pending request, not a brand ───────────────────────

    public function test_3_a_new_brand_is_a_pending_request_and_blocks_publishing(): void
    {
        $seller = $this->seller();

        $this->postLaminate($seller, $this->complete(), ['brand_id' => '', 'new_brand' => 'Egger'], publish: false)->assertOk();

        $this->assertSame(1, Brand::count(), 'the public brand list is not polluted');
        $this->assertDatabaseHas('dictionary_requests', [
            'field_code' => 'brand', 'proposed_label' => 'Egger', 'status' => DictionaryRequestStatus::Pending->value,
        ]);
        $this->assertNull(Product::first()->brand_id);

        $this->postLaminate($seller, $this->complete(), ['brand_id' => '', 'new_brand' => 'Egger'], publish: true)
            ->assertStatus(422)
            ->assertJsonPath('codes.brand_id', 'PUBLISH_REQUIRED');
    }

    // ── 4. 1,835 and 1.835 are the same exact value ────────────────────────────

    public function test_4_comma_and_point_store_the_same_exact_pack_area(): void
    {
        $seller = $this->seller();

        foreach (['1,835', '1.835'] as $i => $typed) {
            $this->postLaminate($seller, ['pack_area_m2' => $typed], ['name' => "L$i"])->assertOk();
        }

        $this->assertSame(['1.8350', '1.8350'], LaminateSpec::orderBy('id')->pluck('pack_area_m2')->all());
        // The calculator's pack size follows the card.
        $this->assertSame(['1.8350', '1.8350'], Product::orderBy('id')->pluck('package_size')->map(fn ($v) => (string) $v)->all());
    }

    // ── 5. 0, negative or text in a size is rejected at that field ─────────────

    public function test_5_zero_negative_text_and_overprecise_sizes_are_rejected_at_the_field(): void
    {
        $seller = $this->seller();

        foreach (['0' => 'POSITIVE_REQUIRED', '-5' => 'NUMBER_FORMAT', '8 мм' => 'NUMBER_FORMAT', 'Infinity' => 'NUMBER_FORMAT', '1.23456' => 'PRECISION_EXCEEDED'] as $typed => $code) {
            $this->postLaminate($seller, ['length_mm' => (string) $typed])
                ->assertStatus(422)
                ->assertJsonPath('codes', ['laminate.length_mm' => $code])
                ->assertJsonValidationErrors('laminate.length_mm');
        }

        $this->assertSame(0, Product::count(), 'nothing is written when a value is rejected');
    }

    // ── 6. An incomplete draft saves; publish lists what is missing ────────────

    public function test_6_incomplete_draft_saves_and_publish_lists_the_missing_fields(): void
    {
        $seller = $this->seller();

        $this->postLaminate($seller, ['model_name' => 'Classic'], images: 0)
            ->assertOk()
            ->assertJsonPath('missing_fields', [
                'laminate.manufacturer_sku', 'laminate.thickness_mm', 'laminate.length_mm',
                'laminate.width_mm', 'laminate.pack_area_m2', 'images',
            ]);

        $this->postLaminate($seller, ['model_name' => 'Classic'], publish: true, images: 0)
            ->assertStatus(422)
            ->assertJsonPath('codes', fn ($codes) => ($codes['laminate.thickness_mm'] ?? null) === 'PUBLISH_REQUIRED')
            ->assertJsonPath('codes.images', 'PUBLISH_REQUIRED');
    }

    // ── 7, 8, 9: positive filters never match "not stated" ─────────────────────

    public function test_7_unknown_water_protection_is_not_in_the_water_filter(): void
    {
        $unknown = $this->publishedCard($this->seller('a@example.com'), ['water_protection' => 'unknown']);
        $yes = $this->publishedCard($this->seller('b@example.com'), ['water_protection' => 'yes', 'water_claim' => 'unknown']);

        $ids = $this->filtered('lam_water=any');

        $this->assertContains($yes->id, $ids);
        $this->assertNotContains($unknown->id, $ids);
    }

    public function test_8_moisture_resistant_with_hours_is_not_water_resistant(): void
    {
        $moisture = $this->publishedCard($this->seller('a@example.com'), ['water_protection' => 'yes', 'water_claim' => 'moisture_resistant', 'water_hours' => '72']);
        $water = $this->publishedCard($this->seller('b@example.com'), ['water_protection' => 'yes', 'water_claim' => 'water_resistant']);

        $this->assertSame([$water->id], $this->filtered('lam_water=water_resistant'));
        $this->assertSame([$moisture->id], $this->filtered('lam_water=moisture_resistant'));
    }

    public function test_9_heating_with_unstated_systems_matches_general_but_no_specific_system(): void
    {
        $unstated = $this->publishedCard($this->seller('a@example.com'), ['floor_heating' => 'yes', 'heating_types_known' => '0']);
        $water = $this->publishedCard($this->seller('b@example.com'), ['floor_heating' => 'yes', 'heating_types_known' => '1', 'heating_types' => ['water']]);

        $this->assertEqualsCanonicalizing([$unstated->id, $water->id], $this->filtered('lam_heating=any'));
        $this->assertSame([$water->id], $this->filtered('lam_heating=water'));
        $this->assertSame([], $this->filtered('lam_heating=electric_film'));
    }

    // ── 10. "Not stated" + a ticked type is rejected; duplicates collapse ──────

    public function test_10_unstated_systems_with_a_ticked_type_is_rejected_and_duplicate_types_collapse(): void
    {
        $seller = $this->seller();

        $this->postLaminate($seller, ['floor_heating' => 'yes', 'heating_types_known' => '0', 'heating_types' => ['water']])
            ->assertStatus(422)
            ->assertJsonPath('codes', fn ($codes) => ($codes['laminate.heating_types'] ?? null) === 'HEATING_TYPES_UNKNOWN');

        $this->postLaminate($seller, ['floor_heating' => 'yes', 'heating_types_known' => '1', 'heating_types' => ['water', 'water', 'electric_cable']])
            ->assertOk();

        $this->assertSame(['electric_cable', 'water'], LaminateSpec::first()->heatingTypes()->orderBy('heating_type_code')->pluck('heating_type_code')->all());
    }

    // ── 11. yes → no through the API clears the dependants; history stays ──────

    public function test_11_switching_yes_to_no_clears_dependants_drops_them_from_filters_and_keeps_history(): void
    {
        $seller = $this->seller();
        $product = $this->publishedCard($seller, ['water_protection' => 'yes', 'water_claim' => 'water_resistant', 'water_hours' => '48']);
        $spec = $product->laminateSpec;

        // Only the parent is sent — the server clears the rest by itself (TZ §4).
        $this->putLaminate($seller, $product, ['water_protection' => 'no', 'expected_version' => (string) $spec->version])->assertOk();

        $spec->refresh();
        $this->assertSame('no', $spec->water_protection->value);
        $this->assertSame('unknown', $spec->water_claim->value);
        $this->assertNull($spec->water_hours);

        // Re-approved and listed — so an empty filter result below proves the filter,
        // not a hidden product.
        Product::whereKey($product->id)->update(['is_approved' => true]);
        $this->assertSame([$product->id], $this->filtered(''));
        $this->assertSame([], $this->filtered('lam_water=water_resistant'), 'the cleared claim is gone from the filter');
        $this->assertSame([], $this->filtered('lam_water=any'), 'water "no" is not in the water filter');

        $history = AuditEvent::where('entity_type', 'laminate_spec')->where('entity_id', $spec->id)->latest('id')->first();
        $this->assertSame(['water_resistant', 'unknown'], $history->changes['water_claim']);
        $this->assertSame([48, null], $history->changes['water_hours']);
    }

    // ── 12. no + a dependant together is DEPENDENCY_CONFLICT, nothing written ──

    public function test_12_no_together_with_a_dependant_is_a_conflict_with_no_partial_write(): void
    {
        $seller = $this->seller();
        $product = $this->publishedCard($seller, ['model_name' => 'Before']);
        $version = $product->laminateSpec->version;

        $this->putLaminate($seller, $product, ['model_name' => 'After', 'water_protection' => 'no', 'water_hours' => '24'])
            ->assertStatus(422)
            ->assertJsonPath('codes', fn ($codes) => ($codes['laminate.water_hours'] ?? null) === 'DEPENDENCY_CONFLICT');

        $spec = $product->laminateSpec()->first();
        $this->assertSame('Before', $spec->model_name, 'no partial write');
        $this->assertSame($version, $spec->version);
    }

    // ── 13. An underlay as thick as the panel is inconsistent ──────────────────

    public function test_13_underlay_not_thinner_than_the_panel_is_rejected_and_unknown_invents_nothing(): void
    {
        $seller = $this->seller();

        $this->postLaminate($seller, [
            'thickness_mm' => '2', 'integrated_underlay' => 'yes', 'underlay_thickness_mm' => '2', 'thickness_includes_underlay' => 'yes',
        ])->assertStatus(422)->assertJsonPath('codes', fn ($codes) => ($codes['laminate.underlay_thickness_mm'] ?? null) === 'UNDERLAY_THICKER_THAN_PANEL');

        $this->postLaminate($seller, ['integrated_underlay' => 'yes', 'thickness_includes_underlay' => 'yes'])->assertOk();
        $this->assertNull(LaminateSpec::first()->underlay_thickness_mm, 'an unknown underlay thickness stays unknown');
    }

    // ── 14. Residential and commercial warranties are separate records ─────────

    public function test_14_two_scopes_store_two_records_duplicate_scope_rejected_lifetime_is_not_a_number(): void
    {
        $seller = $this->seller();

        $this->postLaminate($seller, ['warranty' => 'yes', 'warranties' => [
            ['scope' => 'residential', 'term_kind' => 'years', 'years' => '20'],
            ['scope' => 'commercial', 'term_kind' => 'lifetime'],
        ]])->assertOk();

        $rows = LaminateSpec::first()->warranties()->get()->keyBy(fn ($w) => $w->scope->value);
        $this->assertSame(20, $rows['residential']->years);
        $this->assertSame('lifetime', $rows['commercial']->term_kind->value);
        $this->assertNull($rows['commercial']->years, 'lifetime is never stored as a number');

        $this->postLaminate($seller, ['warranty' => 'yes', 'warranties' => [
            ['scope' => 'residential', 'term_kind' => 'years', 'years' => '5'],
            ['scope' => 'residential', 'term_kind' => 'unknown'],
        ]])->assertStatus(422)->assertJsonPath('codes', fn ($codes) => ($codes['laminate.warranties.1.scope'] ?? null) === 'DUPLICATE_SCOPE');

        $this->postLaminate($seller, ['warranty' => 'yes', 'warranties' => [
            ['scope' => 'residential', 'term_kind' => 'lifetime', 'years' => '999'],
        ]])->assertStatus(422)->assertJsonPath('codes', fn ($codes) => ($codes['laminate.warranties.0.years'] ?? null) === 'WARRANTY_YEARS_FORBIDDEN');
    }

    // ── 15. A seller cannot change another seller's card ───────────────────────

    public function test_15_a_seller_cannot_edit_another_sellers_laminate(): void
    {
        $owner = $this->seller('owner@example.com');
        $product = $this->publishedCard($owner, ['model_name' => 'Mine']);

        $this->putLaminate($this->seller('intruder@example.com'), $product, ['model_name' => 'Stolen'])->assertForbidden();

        $this->assertSame('Mine', $product->laminateSpec()->first()->model_name);
    }

    // ── 16. Stale version → 409; a repeated create does not duplicate ──────────

    public function test_16_a_stale_version_is_409_and_an_idempotency_key_prevents_a_second_product(): void
    {
        $seller = $this->seller();
        $product = $this->publishedCard($seller);
        $stale = (string) $product->laminateSpec->version;

        $this->putLaminate($seller, $product, ['model_name' => 'First', 'expected_version' => $stale])->assertOk();
        $this->putLaminate($seller, $product, ['model_name' => 'Second', 'expected_version' => $stale])
            ->assertStatus(409)
            ->assertJsonPath('codes', fn ($codes) => ($codes['laminate.expected_version'] ?? null) === 'VERSION_CONFLICT');
        $this->assertSame('First', $product->laminateSpec()->first()->model_name, 'the other edit is not overwritten');

        $before = Product::count();
        $headers = ['Idempotency-Key' => 'abc-123'];
        $this->postLaminate($seller, ['model_name' => 'Once'], [], headers: $headers)->assertOk();
        $this->postLaminate($seller, ['model_name' => 'Once'], [], headers: $headers)->assertOk()->assertHeader('Idempotent-Replayed', 'true');
        $this->assertSame($before + 1, Product::count());

        $this->postLaminate($seller, ['model_name' => 'Different'], [], headers: $headers)
            ->assertStatus(409)
            ->assertJsonPath('codes.idempotency', 'IDEMPOTENCY_KEY_REUSED');
    }

    // ── 17. Same article, different size: two cards, not silently merged ───────

    public function test_17_the_same_article_with_a_different_size_stays_a_separate_card(): void
    {
        $a = $this->publishedCard($this->seller('a@example.com'), ['manufacturer_sku' => 'X-1', 'length_mm' => '1380', 'width_mm' => '193']);
        $b = $this->publishedCard($this->seller('b@example.com'), ['manufacturer_sku' => 'X-1', 'length_mm' => '1285', 'width_mm' => '192']);

        $this->assertNotSame($a->laminateSpec->id, $b->laminateSpec->id);
        $this->assertSame('1380.0000', $a->laminateSpec()->first()->length_mm);
        $this->assertSame('1285.0000', $b->laminateSpec()->first()->length_mm);
    }

    // ── 19. A changed pack area moves only the derived price ───────────────────

    public function test_19_a_changed_pack_area_moves_only_the_derived_price(): void
    {
        $seller = $this->seller();
        $product = $this->publishedCard($seller, ['pack_area_m2' => '2'], ['price' => '10', 'unit' => 'm2']);

        $this->get('/product/'.$product->slug)->assertOk()->assertViewHas('laminateCard', fn ($card) => $card['prices']['per_pack'] === '20.00');

        $this->putLaminate($seller, $product, ['pack_area_m2' => '2,5'], ['price' => '10', 'unit' => 'm2'])->assertOk();
        Product::whereKey($product->id)->update(['is_approved' => true]);

        $this->assertSame('10.00', (string) $product->fresh()->price, 'the source price is never rewritten');
        $this->get('/product/'.$product->slug)->assertOk()->assertViewHas('laminateCard', fn ($card) => $card['prices']['per_pack'] === '25.00');
    }

    // ── 20. Several warranties do not count a product twice ────────────────────

    public function test_20_a_card_with_two_warranties_and_two_systems_is_counted_once(): void
    {
        $product = $this->publishedCard($this->seller(), [
            'floor_heating' => 'yes', 'heating_types_known' => '1', 'heating_types' => ['water', 'electric_cable'],
            'warranty' => 'yes', 'warranties' => [
                ['scope' => 'residential', 'term_kind' => 'years', 'years' => '20'],
                ['scope' => 'commercial', 'term_kind' => 'years', 'years' => '5'],
            ],
        ]);

        $this->assertSame([$product->id], $this->filtered('lam_warranty=residential,commercial&lam_heating=water,electric_cable'));
    }

    // ── TZ §6 rules that are not numbered acceptance items ──────────────────────

    public function test_an_option_of_the_wrong_dictionary_is_rejected(): void
    {
        $ac = LaminateCatalog::optionIds(LaminateCatalog::ATTR_ABRASION)['ac4'];

        $this->postLaminate($this->seller(), ['usage_class_option_id' => (string) $ac])
            ->assertStatus(422)
            ->assertJsonPath('codes', fn ($codes) => ($codes['laminate.usage_class_option_id'] ?? null) === 'INVALID_OPTION');
    }

    public function test_an_unknown_payload_key_is_rejected(): void
    {
        $this->postLaminate($this->seller(), ['colour' => 'oak'])
            ->assertStatus(422)
            ->assertJsonPath('codes', fn ($codes) => ($codes['laminate.colour'] ?? null) === 'UNKNOWN_FIELD');
    }

    public function test_a_proposed_class_is_a_pending_request_and_stays_off_the_card(): void
    {
        $this->postLaminate($this->seller(), ['usage_class_proposed' => '35'])->assertOk();

        $this->assertNull(LaminateSpec::first()->usage_class_option_id);
        $this->assertDatabaseHas('dictionary_requests', ['field_code' => 'usage_class', 'proposed_label' => '35', 'status' => 'pending']);
    }

    public function test_laminate_stores_no_colour_free_rows_or_rooms_and_prices_only_per_m2_or_pack(): void
    {
        $seller = $this->seller();

        $this->postLaminate($seller, [], ['color' => 'Palıd', 'attributes' => [['key' => 'Faska', 'value' => '4V']]])->assertOk();
        $specs = Product::first()->specifications ?? [];
        $this->assertArrayNotHasKey('color', $specs);
        $this->assertArrayNotHasKey('Faska', $specs);

        $this->postLaminate($seller, [], ['unit' => 'kg'])
            ->assertStatus(422)
            ->assertJsonPath('codes.unit', 'PRICE_UNIT');
    }

    public function test_a_draft_document_is_private_and_an_approved_one_is_public(): void
    {
        $seller = $this->seller();
        $pdf = $this->realUpload('passport.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");

        $this->postLaminate($seller, [], [
            'laminate_documents' => [
                ['kind' => 'technical_passport', 'file' => $pdf],
                ['kind' => 'installation', 'url' => 'https://example.com/guide.pdf'],
            ],
        ])->assertOk();

        $docs = LaminateSpecDocument::orderBy('id')->get();
        $this->assertCount(2, $docs);
        Storage::disk('local')->assertExists($docs[0]->storage_key);
        Storage::disk('public')->assertMissing($docs[0]->storage_key);

        // Draft: open to the owner, hidden from everybody else.
        $this->actingAs($seller)->get(route('laminate.documents.show', $docs[0]))->assertOk();
        $this->actingAs($this->seller('stranger@example.com'))->get(route('laminate.documents.show', $docs[0]))->assertNotFound();
        $this->asGuest()->get(route('laminate.documents.show', $docs[0]))->assertNotFound();

        // Approved and visible: public.
        Product::first()->update(['is_visible' => true, 'is_approved' => true]);
        $this->asGuest()->get(route('laminate.documents.show', $docs[0]))->assertOk();
        $this->asGuest()->get(route('laminate.documents.show', $docs[1]))->assertRedirect('https://example.com/guide.pdf');
    }

    public function test_documents_reject_a_disguised_file_an_http_link_and_a_water_document_without_water(): void
    {
        $seller = $this->seller();
        $fake = $this->realUpload('passport.pdf', 'MZ this is not a pdf');

        $this->postLaminate($seller, [], ['laminate_documents' => [['kind' => 'technical_passport', 'file' => $fake]]])
            ->assertStatus(422)->assertJsonPath('codes', fn ($codes) => ($codes['laminate_documents.0.file'] ?? null) === 'INVALID_FILE');

        $this->postLaminate($seller, [], ['laminate_documents' => [['kind' => 'installation', 'url' => 'http://example.com/x']]])
            ->assertStatus(422)->assertJsonPath('codes', fn ($codes) => ($codes['laminate_documents.0.url'] ?? null) === 'INVALID_URL');

        $this->postLaminate($seller, ['water_protection' => 'unknown'], ['laminate_documents' => [['kind' => 'water_conditions', 'url' => 'https://example.com/w']]])
            ->assertStatus(422)->assertJsonPath('codes', fn ($codes) => ($codes['laminate_documents.0.kind'] ?? null) === 'DEPENDENCY_CONFLICT');
    }

    public function test_a_rejected_payload_is_not_logged_as_an_error(): void
    {
        Log::spy();

        $this->postLaminate($this->seller(), ['thickness_mm' => '8 мм'])->assertStatus(422);

        Log::shouldNotHaveReceived('error');
    }

    public function test_deleting_a_laminate_product_removes_its_document_files(): void
    {
        $seller = $this->seller();
        $this->postLaminate($seller, [], [
            'laminate_documents' => [['kind' => 'technical_passport', 'file' => $this->realUpload('p.pdf', "%PDF-1.4\n%%EOF")]],
        ])->assertOk();

        $key = LaminateSpecDocument::firstOrFail()->storage_key;
        Storage::disk('local')->assertExists($key);

        $this->actingAs($seller)->deleteJson('/business/products/'.Product::firstOrFail()->id)->assertOk();

        Storage::disk('local')->assertMissing($key);
        $this->assertSame(0, LaminateSpecDocument::count());
    }

    public function test_other_classes_keep_their_existing_behaviour(): void
    {
        // A non-laminate product still creates a brand on the fly and keeps colour.
        $this->actingAs($this->seller())->postJson('/business/products', [
            'name' => 'Kafel',
            'category_id' => $this->group->id,
            'price' => 10,
            'stock' => 5,
            'new_brand' => 'Kale',
            'color' => 'Ağ',
        ])->assertOk();

        $this->assertTrue(Brand::where('slug', 'kale')->exists());
        $this->assertSame('Ağ', Product::where('name->az', 'Kafel')->first()->specifications['color']);
        $this->assertSame(0, LaminateSpec::count());
        $this->assertSame(0, DictionaryRequest::count());
    }

    // ── helpers ─────────────────────────────────────────────────────────────

    /**
     * A real UploadedFile over a temp file. Laravel's fake files report their MIME
     * type from the NAME, which would let a disguised file pass; the server reads the
     * content, so the test has to give it real content to read.
     */
    private function realUpload(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'lam');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, null, null, true);
    }

    /** Drop any signed-in user
 — actingAs() otherwise sticks for the rest of the test. */
    private function asGuest(): static
    {
        $this->app['auth']->forgetGuards();

        return $this;
    }

    private function seller(string $email = 'seller@example.com'): User
    {
        return User::where('email', $email)->first() ?? User::factory()->seller()->create(['email' => $email]);
    }

    /** A card that satisfies the publication rule. */
    private function complete(array $overrides = []): array
    {
        return array_merge([
            'model_name' => 'Classic', 'manufacturer_sku' => 'KR-1',
            'thickness_mm' => '8', 'length_mm' => '1380', 'width_mm' => '193', 'pack_area_m2' => '2.131',
        ], $overrides);
    }

    private function postLaminate(User $seller, array $laminate, array $extra = [], bool $publish = false, int $images = 1, array $headers = []): TestResponse
    {
        return $this->actingAs($seller)->post('/business/products', array_merge([
            'name' => 'Laminat',
            'category_id' => $this->group->id,
            'sub_category_id' => $this->class->id,
            'brand_id' => $this->brand->id,
            'price' => '16.90',
            'unit' => 'm2',
            'stock' => 10,
            'publish' => $publish ? '1' : '0',
            // range(1, 0) counts DOWN to [1, 0] — zero images must mean none.
            'images' => $images > 0 ? array_map(fn ($i) => UploadedFile::fake()->image("p$i.jpg"), range(1, $images)) : [],
            'laminate' => $laminate,
        ], $extra), ['Accept' => 'application/json'] + $headers);
    }

    private function putLaminate(User $seller, Product $product, array $laminate, array $extra = []): TestResponse
    {
        return $this->actingAs($seller)->put('/business/products/'.$product->id, array_merge([
            'name' => 'Laminat',
            'category_id' => $this->group->id,
            'sub_category_id' => $this->class->id,
            'brand_id' => $this->brand->id,
            'price' => '16.90',
            'unit' => 'm2',
            'stock' => 10,
            'laminate' => $laminate,
        ], $extra), ['Accept' => 'application/json']);
    }

    /** Publish through the real endpoint, then approve as a moderator would. */
    private function publishedCard(User $seller, array $laminate = [], array $extra = []): Product
    {
        $this->postLaminate($seller, $this->complete($laminate), array_merge(['name' => 'L'.uniqid()], $extra), publish: true)->assertOk();

        $product = Product::where('user_id', $seller->id)->latest('id')->first();
        Product::whereKey($product->id)->update(['is_approved' => true]);

        return $product->fresh('laminateSpec');
    }

    /** @return list<int> */
    private function filtered(string $query): array
    {
        return $this->get('/catalog?class='.LaminateCatalog::CLASS_SLUG.'&'.$query)
            ->assertOk()
            ->viewData('products')
            ->pluck('id')
            ->sort()
            ->values()
            ->all();
    }
}
