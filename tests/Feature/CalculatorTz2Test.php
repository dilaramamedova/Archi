<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Calculator\RequestType;
use App\Filament\Resources\CalculatorRequestResource;
use App\Models\CalculatorMaterialFallback;
use App\Models\CalculatorRequest;
use App\Models\CalculatorSnapshot;
use App\Models\CalculatorWorkRate;
use App\Models\User;
use App\Services\Calculator\EstimateBuilder;
use App\Services\Calculator\Units;
use App\Support\VersionedCache;
use Database\Seeders\CalculatorSeeder;
use Database\Seeders\CalculatorTz2Seeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The calculator TZ №2 of 19.11.2026 («Ətraflı kalkulyator», items 39–47), held to its
 * acceptance list on Archi's second test object «Leyla Loft»
 * (tests/Fixtures/calculator/leyla_loft.php) and, as a regression, «Sea Breeze».
 */
class CalculatorTz2Test extends TestCase
{
    use RefreshDatabase;

    private EstimateBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CalculatorSeeder::class);
        $this->builder = app(EstimateBuilder::class);
    }

    // ---- §43 lighting type and the track norms ------------------------------------------

    /** Leyla Loft, TRACK in four rooms (60.7 m²): ≈ 44 track lights on ≈ 20 m of rail. */
    public function test_simple_mode_counts_the_track_system_from_the_norms(): void
    {
        $estimate = $this->leylaLoft();
        $points = $this->sumRooms($estimate);

        $this->assertEqualsWithDelta(44, $points['lights_track'], 1);          // 22 + 9 + 8 + 6 = 45
        $this->assertEqualsWithDelta(20.0, $points['track_rail_m'], 0.5);      // 9.9 + 4.0 + 3.5 + 2.6
        $this->assertSame(9, $points['track_feed']);                           // CEIL(rail / 3) per room

        $living = $this->room($estimate, 'living');
        $this->assertSame('TRACK', $living['lightingType']);
        $this->assertSame(22, $living['points']['lights_track']);
        $this->assertEqualsWithDelta(9.9, $living['points']['track_rail_m'], 0.001);
        $this->assertSame(4, $living['points']['track_feed']);
        // Chandeliers, pendants and wall lights still follow the room type.
        $this->assertSame(1, $living['points']['lights_chandelier']);
        $this->assertSame(2, $living['points']['wall_lights']);
        $this->assertArrayNotHasKey('lights_spot', $living['points']);

        // A painted ceiling without a lighting type of its own: chandelier, as before.
        $this->assertSame('CHANDELIER', $this->room($estimate, 'bath')['lightingType']);
    }

    public function test_track_works_and_materials_are_priced_by_stage(): void
    {
        $lines = $this->byId($this->leylaLoft()['lines']);

        $this->assertSame('WHITE_BOX', $lines['ELEC_TRACK_FEED_POINT']['stage']);
        $this->assertSame('FINISH', $lines['LIGHT_TRACK_RAIL_INSTALL']['stage']);
        $this->assertSame('FINISH', $lines['LIGHT_TRACK_INSTALL']['stage']);
        $this->assertSame('FINISH', $lines['MAT_TRACK_RAIL']['stage']);
        $this->assertSame('FINISH', $lines['MAT_LIGHT_TRACK']['stage']);

        $this->assertEqualsWithDelta(20.0, $this->quantity($this->leylaLoft()['lines'], 'MAT_TRACK_RAIL'), 0.5);
        $this->assertSame(45.0, $this->quantity($this->leylaLoft()['lines'], 'MAT_LIGHT_TRACK'));
    }

    /** «Trek işığı spotdan bahalıdır» — to install and to buy. */
    public function test_a_track_light_costs_more_than_a_spot(): void
    {
        $track = CalculatorWorkRate::where('work_id', 'LIGHT_TRACK_INSTALL')->value('rate');
        $spot = CalculatorWorkRate::where('work_id', 'LIGHT_SPOT_INSTALL')->value('rate');
        $this->assertGreaterThan((float) $spot, (float) $track);

        $trackMaterial = CalculatorMaterialFallback::where('material_category_id', 'MAT_LIGHT_TRACK')->first();
        $spotMaterial = CalculatorMaterialFallback::where('material_category_id', 'MAT_LIGHT_SPOT')->first();

        foreach (['economy_price', 'standard_price', 'premium_price'] as $column) {
            $this->assertGreaterThan((float) $spotMaterial->{$column}, (float) $trackMaterial->{$column}, $column);
        }
    }

    /** The feed points are electrical points: the cable norm counts them. */
    public function test_track_feed_points_add_cable(): void
    {
        $estimate = $this->leylaLoft();
        $lines = $estimate['lines'];
        $electrical = 0;

        foreach ($estimate['rooms'] as $room) {
            foreach (['sockets', 'switches', 'lights_spot', 'lights_pendant', 'lights_chandelier', 'wall_lights', 'tv', 'data',
                'intercom', 'ac_power', 'appliance', 'track_feed', 'led_output'] as $key) {
                $electrical += $room['points'][$key] ?? 0;
            }
        }

        $this->assertEqualsWithDelta($electrical * 7, $this->quantity($lines, 'ELEC_CABLE'), 0.01);
        $this->assertSame(9.0, $this->quantity($lines, 'ELEC_TRACK_FEED_POINT'));
        // A track light hangs on the rail: it is not an ELEC_LIGHT_POINT of its own.
        $lightPoints = 0;
        foreach ($estimate['rooms'] as $room) {
            $lightPoints += ($room['points']['lights_spot'] ?? 0) + ($room['points']['lights_pendant'] ?? 0) + ($room['points']['lights_chandelier'] ?? 0);
        }
        $this->assertSame((float) $lightPoints, $this->quantity($lines, 'ELEC_LIGHT_POINT'));
    }

    /** The object's choice reaches every room without its own; a room's explicit AUTO wins. */
    public function test_the_object_lighting_type_applies_to_rooms_without_their_own(): void
    {
        $inputs = $this->leylaLoftInputs();
        $inputs['object']['lightingType'] = 'SPOT';
        $inputs['rooms'][6]['lightingType'] = 'AUTO';          // the bathroom: «by the ceiling»

        $estimate = $this->builder->build($inputs);

        $this->assertSame('SPOT', $this->room($estimate, 'wardrobe')['lightingType']);
        $this->assertSame('TRACK', $this->room($estimate, 'living')['lightingType']);
        $this->assertSame('CHANDELIER', $this->room($estimate, 'bath')['lightingType']);
        // 2.4 m² wardrobe under SPOT: never fewer than its own norm of 2.
        $this->assertSame(2, $this->room($estimate, 'wardrobe')['points']['lights_spot']);
    }

    // ---- §44 / §45 detailed mode ------------------------------------------------------

    public function test_led_outputs_count_only_in_detailed_mode(): void
    {
        $simple = $this->leylaLoft();
        $detailed = $this->leylaLoft(detailed: true);

        $this->assertSame(0.0, $this->quantity($simple['lines'], 'ELEC_LED_OUTPUT_POINT'));
        $this->assertSame(9.0, $this->quantity($detailed['lines'], 'ELEC_LED_OUTPUT_POINT'));
        $this->assertSame('WHITE_BOX', $this->byId($detailed['lines'])['ELEC_LED_OUTPUT_POINT']['stage']);

        // No face part: the same flat without its LED outputs buys exactly the same
        // materials (only the cable to the nine points differs).
        $inputs = $this->leylaLoftInputs('FULL', true);
        foreach ($inputs['rooms'] as $i => $room) {
            $inputs['rooms'][$i]['points']['led_output'] = 0;
        }
        $without = $this->builder->build($inputs);

        $materials = fn (array $estimate) => collect($estimate['lines'])
            ->where('lineType', 'MATERIAL')
            ->reject(fn ($l) => in_array($l['itemId'], ['MAT_CABLE', 'MAT_CONDUIT'], true))
            ->mapWithKeys(fn ($l) => [$l['itemId'].'@'.($l['roomId'] ?? '') => $l['quantity']])
            ->sortKeys()
            ->all();

        $this->assertSame($materials($without), $materials($detailed));
        $this->assertGreaterThan($this->quantity($without['lines'], 'ELEC_CABLE'), $this->quantity($detailed['lines'], 'ELEC_CABLE'));
    }

    public function test_detailed_mode_keeps_the_visitors_track_counts(): void
    {
        $estimate = $this->leylaLoft(detailed: true);
        $points = $this->sumRooms($estimate);

        $this->assertSame(44, $points['lights_track']);
        $this->assertEqualsWithDelta(20.0, $points['track_rail_m'], 0.001);
        $this->assertSame(9, $points['lights_spot']);
        $this->assertSame(3, $points['lights_pendant']);
        $this->assertSame(0, $points['lights_chandelier']);   // the visitor counted the ceiling lights
        $this->assertSame(41, $points['sockets']);
        $this->assertSame(15, $points['switches']);
        $this->assertSame(6, $points['water_points']);
        $this->assertSame(6, $points['sewer_points']);
    }

    /** «Sadə» must not be a different estimate: within 10 % of «Ətraflı» (TZ №2 acceptance). */
    public function test_leyla_loft_simple_and_detailed_agree_within_ten_percent(): void
    {
        foreach (['FULL', 'WHITE_BOX'] as $level) {
            $simple = $this->leylaLoft($level)['totals']['grandTotal'];
            $detailed = $this->leylaLoft($level, detailed: true)['totals']['grandTotal'];

            $this->assertLessThan(0.10, abs($simple - $detailed) / $detailed, "{$level}: {$simple} vs {$detailed}");
        }
    }

    // ---- §46 BALCONY and WARDROBE ------------------------------------------------------

    public function test_balcony_and_wardrobe_have_their_own_norms(): void
    {
        $estimate = $this->leylaLoft();

        $balcony = $this->room($estimate, 'balcony')['points'];
        $this->assertSame([1, 1, 3], [$balcony['sockets'], $balcony['switches'], $balcony['lights_spot']]);

        $wardrobe = $this->room($estimate, 'wardrobe')['points'];
        $this->assertSame([1, 1, 2], [$wardrobe['sockets'], $wardrobe['switches'], $wardrobe['lights_spot']]);

        foreach ([$balcony, $wardrobe] as $points) {
            $this->assertArrayNotHasKey('ac_power', $points);
            $this->assertArrayNotHasKey('tv', $points);
            $this->assertArrayNotHasKey('data', $points);
        }
    }

    public function test_the_config_publishes_the_new_types_and_norms(): void
    {
        $config = $this->getJson('/api/calculator/config')->assertOk()->json();

        $this->assertSame(['SPOT', 'TRACK', 'CHANDELIER'], array_column($config['enums']['lightingTypes'], 'value'));
        $types = collect($config['enums']['roomTypes'])->keyBy('value');
        $this->assertTrue($types->has('BALCONY'));
        $this->assertTrue($types->has('WARDROBE'));
        $this->assertSame(['DOOR' => 0, 'WINDOW' => 1], $types['BALCONY']['defaultOpenings']);
        $this->assertSame(['DOOR' => 1, 'WINDOW' => 0], $types['WARDROBE']['defaultOpenings']);
        $this->assertContains('lights_track', $types['LIVING']['pointKeys']);
        $this->assertContains('led_output', $types['LIVING']['pointKeys']);

        $apartment = collect($config['enums']['objectTypes'])->firstWhere('value', 'APARTMENT');
        $this->assertSame(['BALCONY', 'WARDROBE'], $apartment['optionalRoomTypes']);

        $this->assertSame(1.4, (float) $config['settings']['trackLightAreaM2']);
        $this->assertSame(0.33, (float) $config['settings']['trackRailMPerM2']);
        $this->assertSame(3.0, (float) $config['settings']['trackRailFeedM']);
    }

    // ---- regression: Sea Breeze, old payloads, keep-mode data -----------------------------

    /** Plasterboard keeps SPOT by default: the 17.11 result does not move. */
    public function test_sea_breeze_is_unchanged_by_the_lighting_type(): void
    {
        $make = require base_path('tests/Fixtures/calculator/sea_breeze.php');
        $v3 = $make('FULL', false);                                   // a v3 payload, no lightingType
        $estimate = $this->builder->build(Units::upgradePayload($v3));

        $this->assertSame(['SPOT'], array_values(array_unique(array_column($estimate['rooms'], 'lightingType'))));
        $this->assertSame(28, array_sum(array_map(fn ($r) => $r['points']['lights_spot'] ?? 0, $estimate['rooms'])));
        $this->assertSame(0.0, $this->quantity($estimate['lines'], 'LIGHT_TRACK_INSTALL'));

        $explicit = $v3;
        $explicit['object']['lightingType'] = 'AUTO';
        $this->assertSame($estimate['totals']['grandTotal'], $this->builder->build(Units::upgradePayload($explicit))['totals']['grandTotal']);
    }

    public function test_a_v3_payload_is_upgraded_with_the_lighting_default(): void
    {
        $upgraded = Units::upgradePayload(['v' => 3, 'object' => ['area' => 50], 'rooms' => []]);

        $this->assertSame(Units::PAYLOAD_VERSION, $upgraded['v']);
        $this->assertSame('AUTO', $upgraded['object']['lightingType']);
        $this->assertSame($upgraded, Units::upgradePayload($upgraded));
    }

    /** The production migration never overwrites Archi's price — and the order «track > spot» holds on Archi's prices. */
    public function test_the_data_migration_keeps_archis_prices(): void
    {
        CalculatorWorkRate::where('work_id', 'LIGHT_TRACK_INSTALL')->update(['rate' => 33]);
        (new CalculatorTz2Seeder)->apply(fresh: false);
        $this->assertSame(33.0, (float) CalculatorWorkRate::where('work_id', 'LIGHT_TRACK_INSTALL')->value('rate'));

        // A fresh row next to a spot Archi priced above the draft: never at or below it.
        CalculatorWorkRate::where('work_id', 'LIGHT_TRACK_INSTALL')->delete();
        CalculatorWorkRate::where('work_id', 'LIGHT_SPOT_INSTALL')->update(['rate' => 40]);
        (new CalculatorTz2Seeder)->apply(fresh: false);
        $this->assertGreaterThan(40.0, (float) CalculatorWorkRate::where('work_id', 'LIGHT_TRACK_INSTALL')->value('rate'));
    }

    // ---- §41 one request per estimate ----------------------------------------------------

    public function test_pdf_and_whatsapp_for_the_same_estimate_are_one_request(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $first = $this->raise('PDF');
        $second = $this->raise('WHATSAPP');

        $this->assertSame(1, CalculatorRequest::count());
        $this->assertSame($first['id'], $second['id']);
        $this->assertSame($first['snapshotUuid'], $second['snapshotUuid']);
        $this->assertSame(1, CalculatorSnapshot::count());

        $request = CalculatorRequest::first();
        $this->assertSame(['PDF', 'WHATSAPP'], array_column($request->events, 'type'));
        $this->assertSame('PDF + WhatsApp', $request->actionsLabel());

        // A turnkey order on top promotes the request to a call-back lead.
        $this->raise('TURNKEY', ['name' => 'Leyla', 'phone' => '+994501112233']);
        $request->refresh();
        $this->assertSame(RequestType::Turnkey, $request->type);
        $this->assertSame('+994501112233', $request->phone);
        $this->assertSame(1, CalculatorRequest::count());
    }

    public function test_a_different_estimate_or_a_later_press_is_a_new_request(): void
    {
        $this->actingAs(User::factory()->create());

        $this->raise('PDF');
        $this->raise('PDF', [], fn (array $inputs) => array_replace_recursive($inputs, ['object' => ['segment' => 'PREMIUM']]));
        $this->assertSame(2, CalculatorRequest::count());

        $this->travel(31)->minutes();
        $this->raise('PDF');
        $this->assertSame(3, CalculatorRequest::count());
    }

    // ---- §39 the PDF ---------------------------------------------------------------------

    public function test_the_snapshot_pdf_is_rendered_once_and_then_served_from_disk(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $uuid = $this->raise('PDF')['snapshotUuid'];

        $first = $this->get("/api/calculator/snapshots/{$uuid}/pdf")->assertOk();
        $this->assertStringStartsWith('%PDF-', $first->getContent());
        $this->assertStringContainsString('attachment', (string) $first->headers->get('content-disposition'));
        $this->assertCount(1, Storage::disk('local')->files('calculator-pdf'));

        $this->get("/api/calculator/snapshots/{$uuid}/pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertCount(1, Storage::disk('local')->files('calculator-pdf'));
    }

    /** While another request renders the file, the page is told to come back — not given an error. */
    public function test_a_pdf_being_rendered_answers_202_with_retry_after(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $uuid = $this->raise('PDF')['snapshotUuid'];

        $lock = Cache::lock('calculator-pdf:snapshot-'.$uuid.'-'.app()->getLocale(), 60);
        $this->assertTrue($lock->get());

        $this->get("/api/calculator/snapshots/{$uuid}/pdf")
            ->assertStatus(202)
            ->assertHeader('Retry-After', '2')
            ->assertJson(['status' => 'PENDING']);

        $lock->release();
        $this->get("/api/calculator/snapshots/{$uuid}/pdf")->assertOk();
    }

    public function test_the_quick_calculator_pdf_is_a_real_file(): void
    {
        Storage::fake('local');

        $response = $this->get('/api/calculator/quick-pdf?area=80&renovationType=FULL_RENOVATION&segment=STANDARD')->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $this->get('/api/calculator/quick-pdf?area=0')->assertStatus(422);
    }

    // ---- §40 Baku time ---------------------------------------------------------------------

    public function test_the_pdf_and_the_validity_date_are_in_baku_time(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-19 12:17:00', 'UTC'));
        $this->actingAs(User::factory()->create());

        $snapshot = CalculatorSnapshot::where('uuid', $this->raise('PDF')['snapshotUuid'])->firstOrFail();
        $html = view('pdf.calculator-estimate', ['snapshot' => $snapshot])->render();

        $this->assertStringContainsString('19.11.2026 16:17', $html);   // 12:17 UTC = 16:17 Baku
        // Valid to the end of a Baku day, stored as UTC.
        $this->assertSame('19:59:59', $snapshot->expires_at->format('H:i:s'));
        $this->assertSame('23:59:59', $snapshot->expires_at->copy()->timezone('Asia/Baku')->format('H:i:s'));
    }

    public function test_the_admin_list_shows_baku_time_and_the_merged_actions(): void
    {
        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-19 12:17:00', 'UTC'));
        $this->actingAs(User::factory()->create());
        $this->raise('PDF');
        $this->raise('WHATSAPP');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        $this->get(CalculatorRequestResource::getUrl('index'))
            ->assertOk()
            ->assertSee('19.11.2026 16:17')
            ->assertSee('PDF + WhatsApp');

        $this->get(CalculatorRequestResource::getUrl('edit', ['record' => CalculatorRequest::first()]))
            ->assertOk()
            ->assertSee('19.11.2026 16:17');
    }

    // ---- QA of TT №2 ------------------------------------------------------------------------

    /**
     * The frozen estimate is the one on the screen: a guest's browser cart travels with the
     * saved answers, so the button's own request (which carries no basket) does not drop it.
     */
    public function test_the_snapshot_keeps_the_browser_cart_lines(): void
    {
        $product = \App\Models\Product::create([
            'user_id' => User::factory()->seller()->create()->id,
            'category_id' => \App\Models\Category::create(['name' => ['az' => 'Kateqoriya'], 'slug' => 'cat-'.uniqid(), 'is_active' => true])->id,
            'name' => ['az' => 'Laminat'],
            'description' => ['az' => ''],
            'slug' => 'p-'.uniqid(),
            'stock' => 500,
            'price' => 18.5,
            'is_visible' => true,
            'is_approved' => true,
            'unit' => 'm²',
        ]);

        $make = require base_path('tests/Fixtures/calculator/leyla_loft.php');
        $inputs = $make('FULL', false);
        $inputs['basket'] = ['useCart' => true, 'excluded' => [], 'items' => [['productId' => $product->id, 'quantity' => 1]]];

        // Authenticated so the session is authorised across test requests; the account
        // cart is empty — the product is only in the browser basket, like a guest's.
        $this->actingAs(User::factory()->create());

        $live = $this->postJson('/api/calculator/estimate', $inputs)->assertOk()->json();
        $this->assertNotNull(collect($live['lines'])->firstWhere('itemId', 'CART_'.$product->id));

        $uuid = $this->postJson('/api/calculator/sessions', $inputs)->assertCreated()->json('uuid');
        // The press itself sends no basket — exactly what the page does.
        $raised = $this->postJson("/api/calculator/sessions/{$uuid}/request", ['type' => 'PDF'])->assertSuccessful()->json();

        $snapshot = CalculatorSnapshot::where('uuid', $raised['snapshotUuid'])->firstOrFail();
        $this->assertNotNull(collect($snapshot->lines)->firstWhere('itemId', 'CART_'.$product->id));
        $this->assertEqualsWithDelta($live['totals']['grandTotal'], $snapshot->totals['grandTotal'], 0.01);
        $this->assertEqualsWithDelta($live['totals']['grandTotal'], (float) CalculatorRequest::first()->total_amount, 0.01);
    }

    /** The quick PDF is rendered for every download: its time is the download's, not the first one's. */
    public function test_the_quick_pdf_is_never_cached(): void
    {
        Storage::fake('local');
        $url = '/api/calculator/quick-pdf?area=80&renovationType=WHITE_BOX&segment=STANDARD';

        $this->travelTo(\Illuminate\Support\Carbon::parse('2026-11-19 14:18:00', 'UTC'));
        $this->get($url)->assertOk();
        $this->assertSame([], Storage::disk('local')->allFiles());

        // The view itself prints the Baku time it is given.
        $html = view('pdf.calculator-quick', [
            'result' => ['area' => 80, 'pricePerM2' => 450, 'total' => 36000],
            'renovationType' => \App\Enums\Calculator\RenovationType::WhiteBox,
            'segment' => \App\Enums\Calculator\PriceSegment::Standard,
            'generatedAt' => now('Asia/Baku')->addMinutes(23),
            'showFromPrefix' => true,
        ])->render();
        $this->assertStringContainsString('19.11.2026 18:41', $html);
    }

    /** A partial warm floor is priced by its own area, clamped to the floor. */
    public function test_the_warm_floor_area_is_the_heated_part_only(): void
    {
        $inputs = $this->leylaLoftInputs();
        $kitchen = fn (array $estimate) => $this->room($estimate, 'kitchen')['warmFloorArea'];

        $this->assertEqualsWithDelta(6.1, $kitchen($this->builder->build($inputs)), 0.001);

        $inputs['rooms'][4]['warmFloor'] = ['type' => 'ELECTRIC', 'area' => null];
        $this->assertEqualsWithDelta(10.6, $kitchen($this->builder->build($inputs)), 0.01);   // empty = the whole floor

        $inputs['rooms'][4]['warmFloor'] = ['type' => 'ELECTRIC', 'area' => 99];
        $this->assertEqualsWithDelta(10.6, $kitchen($this->builder->build($inputs)), 0.01);   // clamped
    }

    /** A balcony starts on tile (the page reads defaultFloorFamily); an unticked LAST row has a real stage. */
    public function test_the_config_carries_the_balcony_floor_and_the_cleaning_stage(): void
    {
        $config = $this->getJson('/api/calculator/config')->assertOk()->json();

        $types = collect($config['enums']['roomTypes'])->keyBy('value');
        $this->assertSame('TILE', $types['BALCONY']['defaultFloorFamily']);
        $this->assertNull($types['WARDROBE']['defaultFloorFamily']);

        // The works step resolves LAST to the last stage ordered; the priced line already is.
        $this->assertSame('LAST', collect($config['works'])->firstWhere('workId', 'LOG_FINAL_CLEANING')['stage']);
        $this->assertSame('FINISH', $this->byId($this->leylaLoft()['lines'])['LOG_FINAL_CLEANING']['stage']);
        $this->assertSame('WHITE_BOX', $this->byId($this->leylaLoft('WHITE_BOX')['lines'])['LOG_FINAL_CLEANING']['stage']);

        $js = file_get_contents(resource_path('js/pages/calculator-detailed.js'));
        $this->assertStringContainsString("raw === 'LAST'", $js);
    }

    // ---- §42 / §47 pages ---------------------------------------------------------------------

    public function test_the_quick_calculator_carries_the_design_note_and_the_pdf_endpoint(): void
    {
        $this->get('/calculator')
            ->assertOk()
            ->assertSee('id="qcDesignNote"', false)
            ->assertSee(route('api.calculator.quick-pdf'), false)
            ->assertSee('Dizayn üzlükləri');
    }

    // ---- helpers ---------------------------------------------------------------------------

    /** Store the Leyla Loft answers as a session and press a button on it. */
    private function raise(string $type, array $extra = [], ?callable $mutate = null): array
    {
        $make = require base_path('tests/Fixtures/calculator/leyla_loft.php');
        $inputs = $make('FULL', false);
        $inputs = $mutate ? $mutate($inputs) : $inputs;

        $uuid = $this->postJson('/api/calculator/sessions', $inputs)->assertCreated()->json('uuid');

        return $this->postJson("/api/calculator/sessions/{$uuid}/request", ['type' => $type] + $extra)
            ->assertSuccessful()
            ->json();
    }

    private function leylaLoftInputs(string $level = 'FULL', bool $detailed = false): array
    {
        $make = require base_path('tests/Fixtures/calculator/leyla_loft.php');

        return Units::upgradePayload($make($level, $detailed));
    }

    private function leylaLoft(string $level = 'FULL', bool $detailed = false): array
    {
        VersionedCache::bump(VersionedCache::CALCULATOR);

        return $this->builder->build($this->leylaLoftInputs($level, $detailed));
    }

    /** @return array<string, int|float> */
    private function sumRooms(array $estimate): array
    {
        $sum = array_fill_keys(['lights_track', 'track_rail_m', 'track_feed', 'lights_spot', 'lights_pendant', 'lights_chandelier', 'led_output'], 0);

        foreach ($estimate['rooms'] as $room) {
            foreach ($room['points'] as $key => $value) {
                $sum[$key] = ($sum[$key] ?? 0) + $value;
            }
        }

        $sum['track_rail_m'] = round((float) $sum['track_rail_m'], 1);

        return $sum;
    }

    private function room(array $estimate, string $id): array
    {
        return collect($estimate['rooms'])->firstWhere('id', $id);
    }

    private function linesOf(array $lines, string $itemId): array
    {
        return array_values(array_filter($lines, fn ($l) => $l['itemId'] === $itemId));
    }

    private function quantity(array $lines, string $itemId): float
    {
        return round(array_sum(array_column($this->linesOf($lines, $itemId), 'quantity')), 4);
    }

    private function byId(array $lines): array
    {
        $byId = [];

        foreach ($lines as $line) {
            $byId[$line['itemId']] ??= $line;
        }

        return $byId;
    }
}
