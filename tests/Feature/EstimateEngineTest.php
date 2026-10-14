<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Calculator\RequestStatus;
use App\Enums\Calculator\RequestType;
use App\Models\CalculatorRequest;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Calculator\EstimateBuilder;
use App\Support\VersionedCache;
use Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The minimum acceptance scenarios of the master specification (§23) plus the rules
 * its audit section calls out as the ones that create real disputes with clients:
 * waste versus contingency, a missing rate never becoming free work, and a snapshot
 * that does not move when a price does.
 */
class EstimateEngineTest extends TestCase
{
    use RefreshDatabase;

    private EstimateBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CalculatorSeeder::class);
        $this->builder = app(EstimateBuilder::class);
    }

    /** Scenario A — geometry is derived, never asked for twice. */
    public function test_room_geometry_is_derived_from_dimensions_and_openings(): void
    {
        $estimate = $this->builder->build($this->inputs());
        $room = $estimate['rooms'][0];

        $this->assertSame(20.0, $room['floorArea']);
        $this->assertSame(20.0, $room['ceilingArea']);
        $this->assertSame(18.0, $room['perimeter']);
        $this->assertSame(54.0, $room['grossWallArea']);
        // 54 − (0.9 × 2.1) − (1.5 × 1.4) = 50.01
        $this->assertSame(50.01, $room['netWallArea']);
        // Perimeter minus the door widths — the skirting does not cross a doorway.
        $this->assertSame(17.1, $room['skirtingLength']);
    }

    /** Scenario B — block wall to paint produces the whole technological chain. */
    public function test_the_dependency_engine_proposes_the_full_chain_for_a_bare_wall(): void
    {
        $estimate = $this->builder->build($this->inputs());
        $works = $this->lineIds($estimate);

        foreach (['WALL_PRIMER_BASE', 'WALL_PLASTER', 'WALL_PUTTY', 'WALL_SANDING', 'WALL_PRIMER_FINISH', 'WALL_PAINT'] as $workId) {
            $this->assertContains($workId, $works, "Missing chain step {$workId}");
        }
    }

    public function test_a_removed_work_disappears_from_the_estimate(): void
    {
        $inputs = $this->inputs();
        $inputs['works'] = ['WALL_PLASTER' => ['selected' => false]];

        $estimate = $this->builder->build($inputs);

        $this->assertNotContains('WALL_PLASTER', $this->lineIds($estimate));
        // The rest of the chain stays: removing a step is a warning, not a reset.
        $this->assertContains('WALL_PAINT', $this->lineIds($estimate));
    }

    /** Scenario C — electrics come from points, never from "area × rate". */
    public function test_electrical_total_is_driven_by_points_not_by_object_area(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['points'] = ['sockets' => 10, 'switches' => 2, 'lights' => 3];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame(10.0, $lines['ELEC_SOCKET_POINT']['quantity']);
        $this->assertSame(150.0, $lines['ELEC_SOCKET_POINT']['lineTotal']); // 10 × 15
        $this->assertSame(30.0, $lines['ELEC_SWITCH_POINT']['lineTotal']);  // 2 × 15
        $this->assertSame(54.0, $lines['ELEC_LIGHT_POINT']['lineTotal']);   // 3 × 18
    }

    /** Scenario D — 3 split units plus 7 m of extra route. */
    public function test_split_air_conditioning_is_counted_per_unit_and_per_extra_metre(): void
    {
        $inputs = $this->inputs();
        $inputs['modules']['hvac'] = ['systemType' => 'SPLIT', 'indoorUnits' => 3, 'extraRouteMeters' => 7];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame(450.0, $lines['HVAC_SPLIT_STD']['lineTotal']);          // 3 × 150
        $this->assertSame(105.0, $lines['HVAC_SPLIT_EXTRA_ROUTE']['lineTotal']);  // 7 × 15
    }

    /** The included standard route is not billed twice. */
    public function test_only_the_route_beyond_the_included_metres_is_charged(): void
    {
        $inputs = $this->inputs();
        // 3 units × 3 included metres = 9 included; 16 entered → 7 chargeable.
        $inputs['modules']['hvac'] = ['systemType' => 'SPLIT', 'indoorUnits' => 3, 'routeMeters' => 16];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame(7.0, $lines['HVAC_SPLIT_EXTRA_ROUTE']['quantity']);
    }

    /** Scenario G — a system without a project is honest about being an estimate. */
    public function test_vrf_is_marked_estimated_rather_than_presented_as_exact(): void
    {
        $inputs = $this->inputs();
        $inputs['modules']['hvac'] = ['systemType' => 'VRF', 'conditionedArea' => 80, 'zones' => 4];

        $estimate = $this->builder->build($inputs);
        $line = $this->linesById($estimate)['HVAC_VRF_ESTIMATE'];

        $this->assertSame('ESTIMATED', $line['precisionClass']);
        $this->assertTrue($estimate['hasEstimatedLines']);
        // 80 × 160 × 1.10 (multi-zone) = 14 080
        $this->assertSame(14080.0, $line['lineTotal']);
    }

    /** Scenario E — a real product is billed in whole packages. */
    public function test_a_catalog_product_is_billed_in_whole_packages(): void
    {
        $product = $this->product(['package_size' => 1.44, 'package_unit' => 'qutu', 'price' => 20]);

        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [[
            'surface' => 'FLOOR', 'area' => 30, 'finish' => 'FLOOR_TILE', 'currentState' => 'PLASTER_READY',
        ]];
        $inputs['materials'] = ['MAT_FLOOR_TILE' => ['mode' => 'CATALOG_PRODUCT', 'productId' => $product->id]];

        $lines = $this->linesById($this->estimateViaApi($inputs));
        $line = $lines['PRODUCT_'.$product->id];

        // 30 m² + 10% waste = 33 m²; CEIL(33 / 1.44) = 23 packs.
        $this->assertEquals(23.0, $line['quantity']);
        $this->assertEquals(460.0, $line['lineTotal']);
        $this->assertEquals('EXACT', $line['precisionClass']);
        $this->assertEquals('CATALOG_PRICE', $line['source']);
    }

    /** Without a product the category fallback keeps the estimate complete. */
    public function test_a_material_without_a_product_falls_back_to_the_segment_price(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [[
            'surface' => 'FLOOR', 'area' => 20, 'finish' => 'LAMINATE', 'currentState' => 'PLASTER_READY',
        ]];

        $line = $this->linesById($this->builder->build($inputs))['MAT_FLOOR_LAMINATE'];

        // 20 m² + 8% waste = 21.6 m² × 14 AZN (standard segment).
        $this->assertSame(21.6, $line['quantity']);
        $this->assertSame(302.4, $line['lineTotal']);
        $this->assertSame('FALLBACK_CATEGORY_PRICE', $line['source']);
        $this->assertSame('CALCULATED', $line['precisionClass']);
    }

    /** Scenario I — a new door in an opening creates its installation line. */
    public function test_an_opening_flagged_for_a_new_door_creates_the_installation_line(): void
    {
        $lines = $this->linesById($this->builder->build($this->inputs()));

        $this->assertArrayHasKey('DOOR_INSTALL_INTERIOR', $lines);
        $this->assertSame(1.0, $lines['DOOR_INSTALL_INTERIOR']['quantity']);
    }

    /** Scenario F — lifting is charged only above the free floors, and only by weight. */
    public function test_manual_lifting_needs_both_a_missing_lift_and_a_known_weight(): void
    {
        $product = $this->product(['package_size' => 1.44, 'price' => 20, 'weight_kg' => 25, 'volume_m3' => 0.02]);

        $inputs = $this->inputs();
        $inputs['object']['floor'] = 7;
        $inputs['object']['hasFreightElevator'] = false;
        $inputs['rooms'][0]['zones'] = [[
            'surface' => 'FLOOR', 'area' => 30, 'finish' => 'FLOOR_TILE', 'currentState' => 'PLASTER_READY',
        ]];
        $inputs['materials'] = ['MAT_FLOOR_TILE' => ['mode' => 'CATALOG_PRODUCT', 'productId' => $product->id]];

        $lines = $this->linesById($this->estimateViaApi($inputs));

        // 23 packs × 25 kg = 575 kg, floors 7 − 1 free = 6 → 3 450 kg×floor × 0.15.
        $this->assertEquals(3450.0, $lines['LOG_MANUAL_LIFT']['quantity']);
        $this->assertEquals(517.5, $lines['LOG_MANUAL_LIFT']['lineTotal']);
        $this->assertEquals(1.0, $lines['LOG_DELIVERY_TRIP']['quantity']);
    }

    public function test_a_freight_lift_removes_the_manual_lifting_line(): void
    {
        $product = $this->product(['package_size' => 1.44, 'price' => 20, 'weight_kg' => 25]);

        $inputs = $this->inputs();
        $inputs['object']['hasFreightElevator'] = true;
        $inputs['rooms'][0]['zones'] = [[
            'surface' => 'FLOOR', 'area' => 30, 'finish' => 'FLOOR_TILE', 'currentState' => 'PLASTER_READY',
        ]];
        $inputs['materials'] = ['MAT_FLOOR_TILE' => ['mode' => 'CATALOG_PRODUCT', 'productId' => $product->id]];

        $this->assertArrayNotHasKey('LOG_MANUAL_LIFT', $this->linesById($this->estimateViaApi($inputs)));
    }

    /**
     * Master spec §15 — the material waste is inside the material quantity and the
     * financial reserve is its own line. They must not compound.
     */
    public function test_contingency_is_a_separate_line_over_the_eligible_subtotal(): void
    {
        $estimate = $this->builder->build($this->inputs());
        $totals = $estimate['totals'];

        $this->assertSame(10.0, $totals['contingencyPercent']);
        $this->assertGreaterThan(0, $totals['contingency']);
        $this->assertSame(
            round($totals['baseSubtotal'] + $totals['contingency'], 2),
            $totals['grandTotal'],
        );
    }

    /**
     * The three switches on the object step. Each one has to move money — a toggle
     * the engine ignores is worse than no toggle, because the visitor believes it
     * did something.
     */
    public function test_the_demolition_switch_governs_the_demolition_block(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['demolitionRequired'] = true;
        $inputs['modules']['demolition'] = ['estimateDebris' => true, 'plumbingFixtures' => 3];

        $on = $this->linesById($this->builder->build($inputs));

        $this->assertArrayHasKey('DEM_PLUMB_FIXTURE', $on);
        $this->assertArrayHasKey('DEM_DEBRIS_LOADING', $on);
        $this->assertArrayHasKey('DEM_DEBRIS_DISPOSAL', $on);

        $inputs['object']['demolitionRequired'] = false;
        $off = $this->linesById($this->builder->build($inputs));

        $this->assertArrayNotHasKey('DEM_PLUMB_FIXTURE', $off);
        $this->assertArrayNotHasKey('DEM_DEBRIS_LOADING', $off);
    }

    /**
     * Stripping old wallpaper before painting belongs to the wall's technological
     * chain, not to the demolition block — switching demolition off must not leave
     * the painter working over the old finish (master spec §10.1).
     */
    public function test_surface_stripping_survives_the_demolition_switch(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['demolitionRequired'] = false;
        $inputs['rooms'][0]['zones'] = [[
            'surface' => 'WALL', 'area' => 50, 'finish' => 'PAINT', 'currentState' => 'OLD_FINISH',
        ]];

        $this->assertArrayHasKey('DEM_WALL_FINISH', $this->linesById($this->builder->build($inputs)));
    }

    /** A second-hand flat with an old renovation defaults to needing demolition. */
    public function test_demolition_defaults_from_the_object_condition(): void
    {
        $inputs = $this->inputs();
        unset($inputs['object']['demolitionRequired']);
        $inputs['object']['condition'] = 'OLD_RENOVATION';
        $inputs['modules']['demolition'] = ['estimateDebris' => true];

        $this->assertArrayHasKey('DEM_DEBRIS_LOADING', $this->linesById($this->builder->build($inputs)));
    }

    public function test_the_design_switch_adds_the_design_project_line(): void
    {
        $off = $this->linesById($this->builder->build($this->inputs()));
        $this->assertArrayNotHasKey('SERVICE_DESIGN', $off);

        $inputs = $this->inputs();
        $inputs['object']['designProject'] = true;
        $inputs['object']['area'] = 100;

        // 100 m² × 15 AZN, comfortably above the 300 AZN minimum for a design project.
        $this->assertSame(1500.0, $this->linesById($this->builder->build($inputs))['SERVICE_DESIGN']['lineTotal']);
    }

    /** External services stay outside the reserve base. */
    public function test_the_design_project_is_excluded_from_the_contingency_base(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['designProject'] = true;

        $estimate = $this->builder->build($inputs);
        $design = $this->linesById($estimate)['SERVICE_DESIGN'];

        $this->assertFalse($design['contingencyEligible']);
        $this->assertGreaterThan(0, $design['lineTotal']);
    }

    /** The minimum charge lifts a small job, but never invents one out of nothing. */
    public function test_the_minimum_charge_applies_only_when_there_is_a_quantity(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [[
            'surface' => 'WALL', 'area' => 4, 'finish' => 'PAINT', 'currentState' => 'SHELL',
        ]];

        $line = $this->linesById($this->builder->build($inputs))['WALL_PLASTER'];

        // 4 m² × 9 = 36 AZN, below the 150 AZN minimum for plastering.
        $this->assertSame(150.0, $line['lineTotal']);
    }

    /** Master spec §19 — a work without a rate is reported, never priced at zero. */
    public function test_a_work_without_a_rate_is_reported_instead_of_billed_as_free(): void
    {
        CalculatorWorkRate::where('work_id', 'WALL_PAINT')->update(['rate' => null]);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $estimate = app(EstimateBuilder::class)->build($this->inputs());
        $line = $this->linesById($estimate)['WALL_PAINT'];

        $this->assertSame('PRICE_MISSING', $line['source']);
        $this->assertNull($line['unitPrice']);
        $this->assertSame(0.0, $line['lineTotal']);
        $this->assertContains('WALL_PAINT', $estimate['priceMissing']);
    }

    /** Master spec §16.2 — the same lines, grouped by room. */
    public function test_the_estimate_can_be_read_by_room_as_well_as_by_category(): void
    {
        $estimate = $this->builder->build($this->inputs());

        $this->assertNotEmpty($estimate['groups']);
        $this->assertSame('Qonaq', $estimate['byRoom'][0]['name']);
        $this->assertGreaterThan(0, $estimate['byRoom'][0]['total']);
    }

    /** The city coefficient multiplies labour, and 1.00 leaves no trace on a line. */
    public function test_the_city_coefficient_scales_the_works(): void
    {
        $baku = $this->builder->build($this->inputs());

        $inputs = $this->inputs();
        $inputs['object']['cityKey'] = 'GANJA';
        $ganja = $this->builder->build($inputs);

        $this->assertSame(1.0, $baku['cityCoefficient']);
        $this->assertSame(1.1, $ganja['cityCoefficient']);
        $this->assertGreaterThan($baku['totals']['works'], $ganja['totals']['works']);
    }

    /** Master spec §19 — the same work in one room is one line, not five. */
    public function test_repeated_work_in_one_room_is_consolidated_into_a_single_line(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'WALL', 'area' => 25, 'finish' => 'PAINT', 'currentState' => 'SHELL'],
            ['surface' => 'WALL', 'area' => 25.01, 'finish' => 'PAINT', 'currentState' => 'SHELL'],
        ];

        $painting = array_values(array_filter(
            $this->builder->build($inputs)['lines'],
            fn (array $line) => $line['itemId'] === 'WALL_PAINT',
        ));

        $this->assertCount(1, $painting);
        $this->assertSame(50.01, $painting[0]['quantity']);
    }

    /** Ceiling height above 3 m costs more labour on walls and ceilings, not on floors. */
    public function test_the_height_coefficient_reaches_wall_labour_only(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['heightMm'] = 3400;

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame([['id' => 'K_HEIGHT_3_01_3_50', 'value' => 1.15]], $lines['WALL_PAINT']['coefficients']);
        $this->assertSame([], $lines['FLOOR_LAMINATE_INSTALL']['coefficients']);
    }

    /** A stated area far from the sum of the rooms is a warning, never a block. */
    public function test_an_area_mismatch_warns_but_still_produces_an_estimate(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = 200;

        $estimate = $this->builder->build($inputs);
        $codes = array_column($estimate['warnings'], 'code');

        $this->assertContains('AREA_MISMATCH', $codes);
        $this->assertGreaterThan(0, $estimate['totals']['grandTotal']);
    }

    /** Scenario H — a stored estimate does not move when a rate does. */
    public function test_a_snapshot_keeps_its_prices_after_a_rate_change(): void
    {
        $user = User::factory()->create();

        $create = $this->actingAs($user)->postJson('/api/calculator/sessions', $this->inputs());
        $create->assertCreated();

        $uuid = $create->json('uuid');
        $snapshot = $this->actingAs($user)->postJson("/api/calculator/sessions/{$uuid}/snapshot");
        $snapshot->assertCreated();

        $frozenTotal = $snapshot->json('totals.grandTotal');

        CalculatorWorkRate::where('work_id', 'WALL_PAINT')->update(['rate' => 40]);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $stored = $this->actingAs($user)->getJson('/api/calculator/snapshots/'.$snapshot->json('uuid'));
        $recalculated = $this->actingAs($user)->getJson("/api/calculator/sessions/{$uuid}");

        $this->assertSame($frozenTotal, $stored->json('totals.grandTotal'));
        $this->assertGreaterThan($frozenTotal, $recalculated->json('estimate.totals.grandTotal'));
    }

    /** The visitor's own cart joins the estimate, and can be left out of it. */
    public function test_cart_items_are_included_in_the_estimate_and_can_be_excluded(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['price' => 120]);
        $user->cartItems()->create(['product_id' => $product->id, 'quantity' => 2]);

        $withCart = $this->inputs();
        $withCart['basket'] = ['useCart' => true];

        $included = $this->actingAs($user)->postJson('/api/calculator/estimate', $withCart);
        $included->assertOk();

        $lines = collect($included->json('lines'))->keyBy('itemId');
        $this->assertEquals(240.0, $lines['CART_'.$product->id]['lineTotal']);

        $inputs = $this->inputs();
        $inputs['basket'] = ['useCart' => true, 'excluded' => [$product->id]];

        $excluded = $this->actingAs($user)->postJson('/api/calculator/estimate', $inputs);

        $this->assertNull(collect($excluded->json('lines'))->firstWhere('itemId', 'CART_'.$product->id));
    }

    /**
     * The storefront cart lives in the browser, so a guest's items reach the engine
     * as ids in the request. Prices must still come from the catalog — a tampered
     * localStorage price cannot change the estimate.
     */
    public function test_a_guest_cart_sent_with_the_request_is_priced_from_the_catalog(): void
    {
        $product = $this->product(['price' => 80]);

        $inputs = $this->inputs();
        $inputs['basket'] = [
            'useCart' => true,
            'excluded' => [],
            // The claimed quantity is honoured; a claimed price would simply not exist.
            'items' => [['productId' => $product->id, 'quantity' => 3]],
        ];

        $response = $this->postJson('/api/calculator/estimate', $inputs);
        $response->assertOk();

        $line = collect($response->json('lines'))->firstWhere('itemId', 'CART_'.$product->id);

        $this->assertNotNull($line);
        $this->assertEquals(3, $line['quantity']);
        $this->assertEquals(240.0, $line['lineTotal']); // 3 × catalog 80, not anything client-sent
    }

    /**
     * A product chosen as the material for a surface must not also be billed as a
     * cart row. The calculator worked out how much of it the job needs; the number
     * the visitor happened to have in their cart does not get added on top.
     */
    public function test_a_product_used_as_a_material_is_not_billed_twice_from_the_cart(): void
    {
        $product = $this->product(['package_size' => 1.44, 'price' => 20]);

        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [[
            'surface' => 'FLOOR', 'area' => 30, 'finish' => 'FLOOR_TILE', 'currentState' => 'PLASTER_READY',
        ]];
        $inputs['materials'] = ['MAT_FLOOR_TILE' => ['mode' => 'CATALOG_PRODUCT', 'productId' => $product->id]];
        $inputs['basket'] = ['useCart' => true, 'items' => [['productId' => $product->id, 'quantity' => 2]]];

        $lines = $this->linesById($this->estimateViaApi($inputs));

        // Priced once, as the material requirement: 23 packs, not 23 packs + 2 pieces.
        $this->assertArrayHasKey('PRODUCT_'.$product->id, $lines);
        $this->assertArrayNotHasKey('CART_'.$product->id, $lines);
        $this->assertEquals(23, $lines['PRODUCT_'.$product->id]['quantity']);
    }

    /** A hidden or unapproved product cannot be smuggled in through the browser cart. */
    public function test_an_unpublished_product_in_the_browser_cart_is_ignored(): void
    {
        $product = $this->product(['price' => 80, 'is_visible' => false]);

        $inputs = $this->inputs();
        $inputs['basket'] = ['useCart' => true, 'items' => [['productId' => $product->id, 'quantity' => 2]]];

        $response = $this->postJson('/api/calculator/estimate', $inputs);

        $this->assertNull(collect($response->json('lines'))->firstWhere('itemId', 'CART_'.$product->id));
    }

    /**
     * A turnkey order has to reach Archi with the sum the client saw frozen behind
     * it — a manager calling back cannot be quoting a recalculation.
     */
    public function test_a_turnkey_request_lands_in_the_admin_panel_with_a_frozen_total(): void
    {
        $user = User::factory()->create();

        $created = $this->actingAs($user)->postJson('/api/calculator/sessions', $this->inputs());
        $created->assertCreated();

        $uuid = $created->json('uuid');

        $response = $this->actingAs($user)->postJson("/api/calculator/sessions/{$uuid}/request", [
            'type' => 'TURNKEY',
            'name' => 'Rəşad Məmmədov',
            'phone' => '+994501234567',
        ]);

        $response->assertCreated();

        $request = CalculatorRequest::latest('id')->first();

        $this->assertSame(RequestType::Turnkey, $request->type);
        $this->assertSame(RequestStatus::New, $request->status);
        $this->assertSame('Rəşad Məmmədov', $request->name);
        $this->assertSame('+994501234567', $request->phone);
        $this->assertNotNull($request->calculator_snapshot_id);
        $this->assertEqualsWithDelta($created->json('estimate.totals.grandTotal'), (float) $request->total_amount, 0.01);

        // The frozen figure does not follow a later rate change.
        $frozen = (float) $request->total_amount;

        CalculatorWorkRate::where('work_id', 'WALL_PAINT')->update(['rate' => 99]);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $this->assertSame($frozen, (float) $request->fresh()->total_amount);
    }

    /** Saving and sharing are recorded too, so Archi sees which estimates went out. */
    public function test_saving_an_estimate_records_a_request(): void
    {
        $user = User::factory()->create();

        $uuid = $this->actingAs($user)
            ->postJson('/api/calculator/sessions', $this->inputs())
            ->json('uuid');

        $this->actingAs($user)
            ->postJson("/api/calculator/sessions/{$uuid}/request", ['type' => 'SAVED_ESTIMATE'])
            ->assertCreated();

        $this->assertSame(RequestType::SavedEstimate, CalculatorRequest::latest('id')->first()->type);
    }

    /** Somebody else's session cannot be turned into a request. */
    public function test_a_request_cannot_be_raised_on_another_visitors_session(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $uuid = $this->actingAs($owner)
            ->postJson('/api/calculator/sessions', $this->inputs())
            ->json('uuid');

        $this->actingAs($stranger)
            ->postJson("/api/calculator/sessions/{$uuid}/request", ['type' => 'TURNKEY'])
            ->assertForbidden();
    }

    /** Every configurable number really is configurable (master spec §21). */
    public function test_admin_settings_change_the_result_without_a_code_change(): void
    {
        $before = $this->builder->build($this->inputs())['totals'];

        CalculatorSetting::where('key', 'CONTINGENCY_PERCENT')->update(['value' => '0']);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $after = app(EstimateBuilder::class)->build($this->inputs())['totals'];

        $this->assertGreaterThan(0, $before['contingency']);
        $this->assertSame(0.0, $after['contingency']);
        $this->assertSame($after['baseSubtotal'], $after['grandTotal']);
    }

    /**
     * The sealed joint runs around the room, so it is billed once per room.
     *
     * It used to be a step of every tiled zone: a 2×2 m bathroom (8 m of perimeter)
     * with tiled walls AND a tiled floor produced two requests of 8 p.m each, which
     * consolidation then added up to 16 p.m — 32 ₼ of silicone nobody will apply.
     */
    public function test_tile_sealant_is_billed_once_per_room_not_once_per_tiled_surface(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = 4;
        $inputs['rooms'][0]['roomType'] = 'BATHROOM';
        $inputs['rooms'][0]['lengthMm'] = 2000;
        $inputs['rooms'][0]['widthMm'] = 2000;
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 4, 'finish' => 'FLOOR_TILE', 'currentState' => 'ROUGH_FLOOR', 'wetZone' => true],
            ['surface' => 'WALL', 'area' => 18, 'finish' => 'WALL_TILE', 'currentState' => 'PLASTER_READY', 'wetZone' => true],
        ];

        $estimate = $this->builder->build($inputs);
        $sealant = array_values(array_filter($estimate['lines'], fn (array $l) => $l['itemId'] === 'TILE_SILICONE'));

        $this->assertCount(1, $sealant);
        // The room's perimeter, 2 × (2 + 2) = 8 m — not 16.
        $this->assertSame(8.0, $sealant[0]['quantity']);
        $this->assertSame(32.0, $sealant[0]['lineTotal']);
        // Grout IS per square metre of tile, so both surfaces count: 4 + 18.
        $this->assertSame(22.0, $this->linesById($estimate)['TILE_GROUT']['quantity']);
    }

    /** A dry tiled room has no sealant line at all. */
    public function test_a_dry_tiled_room_gets_no_sealant(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 20, 'finish' => 'FLOOR_TILE', 'currentState' => 'ROUGH_FLOOR', 'wetZone' => false],
        ];

        $this->assertArrayNotHasKey('TILE_SILICONE', $this->linesById($this->builder->build($inputs)));
    }

    /**
     * "Minimum sifariş" is a minimum ORDER, so it applies once to the job.
     *
     * 9 m² of kitchen floor tile plus 4 m² of bathroom floor tile is one order of
     * 13 m²: at 15 ₼/m² that is 195 ₼, well above the 150 ₼ minimum. The engine used
     * to price the two rooms as separate lines of MAX(135, 150) and MAX(60, 150) and
     * charge 300 ₼ — 105 ₼ created purely by the estimate being broken down by room.
     */
    public function test_the_minimum_charge_applies_per_order_not_per_room(): void
    {
        $estimate = $this->builder->build($this->twoTiledRooms(9, 4));
        $lines = array_values(array_filter($estimate['lines'], fn (array $l) => $l['itemId'] === 'FLOOR_TILE_INSTALL'));

        $this->assertCount(2, $lines);
        $this->assertSame(195.0, round(array_sum(array_column($lines, 'lineTotal')), 2));
        // The admin's order-wide minimum stays visible on the line.
        $this->assertSame(150.0, $lines[0]['meta']['minimumChargeOrder']);
    }

    /** One small room on its own is still lifted to the whole minimum. */
    public function test_a_single_small_room_is_still_lifted_to_the_minimum(): void
    {
        $inputs = $this->twoTiledRooms(4, 0);
        unset($inputs['rooms'][1]);
        $inputs['rooms'] = array_values($inputs['rooms']);

        $line = $this->linesById($this->builder->build($inputs))['FLOOR_TILE_INSTALL'];

        // 4 m² × 15 = 60 ₼ raw, lifted to the 150 ₼ minimum order.
        $this->assertSame(4.0, $line['quantity']);
        $this->assertSame(150.0, $line['lineTotal']);
    }

    /** Several rooms that together stay under it sum to exactly the minimum, once. */
    public function test_rooms_under_the_minimum_together_sum_to_exactly_one_minimum(): void
    {
        $lines = array_filter(
            $this->builder->build($this->twoTiledRooms(4, 4))['lines'],
            fn (array $l) => $l['itemId'] === 'FLOOR_TILE_INSTALL',
        );

        // 8 m² × 15 = 120 ₼ raw → one 150 ₼ minimum, split 75/75 by room.
        $this->assertSame(150.0, round(array_sum(array_column($lines, 'lineTotal')), 2));
    }

    /**
     * Large-format wall tile has its own tariff row (18 ₼/m² against the standard
     * 15) — that difference IS the format surcharge. Multiplying it by K_TILE_LARGE
     * as well charged 22.50 ₼/m² for work the admin priced at 18, and the same code
     * path never applied the coefficient to mosaic, so the rule was not even
     * consistent with itself.
     */
    public function test_the_large_format_surcharge_is_charged_once_on_a_wall(): void
    {
        $line = $this->linesById($this->builder->build($this->tiledWall('LARGE')))['TILE_INSTALL_LARGE'];

        $this->assertSame([], $line['coefficients']);
        $this->assertSame(180.0, $line['lineTotal']); // 10 × 18, not 10 × 18 × 1.25
    }

    /**
     * The layout coefficient now reaches every format. A diagonal large-format wall
     * used to lose K_TILE_DIAGONAL entirely, because the LARGE branch returned
     * before the layout was ever looked at.
     */
    public function test_the_diagonal_layout_coefficient_reaches_every_tile_format(): void
    {
        foreach ([
            'STANDARD' => ['TILE_INSTALL_STANDARD', 172.5],   // 10 × 15 × 1.15
            'LARGE' => ['TILE_INSTALL_LARGE', 207.0],         // 10 × 18 × 1.15
            'MOSAIC' => ['TILE_INSTALL_MOSAIC', 322.0],       // 10 × 28 × 1.15
        ] as $format => [$workId, $expected]) {
            $line = $this->linesById($this->builder->build($this->tiledWall($format, 'DIAGONAL')))[$workId];

            $this->assertSame(
                [['id' => 'K_TILE_DIAGONAL', 'value' => 1.15]],
                $line['coefficients'],
                "Diagonal layout lost for format {$format}",
            );
            $this->assertSame($expected, $line['lineTotal']);
        }
    }

    /**
     * A floor has one tariff row for every format, so there the format DOES come
     * from the coefficient — the opposite of the wall above, and deliberately so.
     */
    public function test_a_floor_keeps_its_format_coefficient_because_it_has_one_rate(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 10, 'finish' => 'FLOOR_TILE', 'currentState' => 'PLASTER_READY',
                'options' => ['format' => 'LARGE']],
        ];

        $line = $this->linesById($this->builder->build($inputs))['FLOOR_TILE_INSTALL'];

        $this->assertSame([['id' => 'K_TILE_LARGE', 'value' => 1.25]], $line['coefficients']);
        $this->assertSame(187.5, $line['lineTotal']); // 10 × 15 × 1.25
    }

    /**
     * Old tile on a wall is the dependency engine's business — it derives the removal
     * from the zone's own state. The demolition block used to accept a wall-tile area
     * of its own, and because a module request is object-scoped while a zone request
     * is room-scoped, the two never consolidated: the same 20 m² of tile was removed
     * twice.
     */
    public function test_old_tile_removal_is_not_billed_twice_from_the_demolition_block(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['demolitionRequired'] = true;
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'WALL', 'area' => 20, 'finish' => 'WALL_TILE', 'currentState' => 'OLD_TILE'],
        ];
        // A frontend or CRM that still sends the old key must not double the line.
        $inputs['modules']['demolition'] = ['estimateDebris' => false, 'wallTileArea' => 20];

        $lines = array_filter(
            $this->builder->build($inputs)['lines'],
            fn (array $l) => $l['itemId'] === 'DEM_WALL_TILE',
        );

        $this->assertCount(1, $lines);
        $this->assertSame(20.0, reset($lines)['quantity']);
    }

    /**
     * The debris estimate and the final cleaning are both "per m² of the object", so
     * they must read the same area. They used to disagree: a flat stated as 61.5 m²
     * whose rooms added up to 20 had its cleaning billed on 61.5 and its debris
     * estimated on 20.
     */
    public function test_debris_and_cleaning_are_measured_against_the_same_object_area(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = 100;           // rooms add up to 20 m²
        $inputs['object']['demolitionRequired'] = true;
        $inputs['modules'] = ['demolition' => ['estimateDebris' => true], 'services' => ['finalCleaning' => true]];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame(100.0, $lines['LOG_FINAL_CLEANING']['quantity']);
        // 100 m² × 0.05 m³/m²
        $this->assertSame(5.0, $lines['DEM_DEBRIS_LOADING']['quantity']);
    }

    /** Two rooms with tiled floors of the given areas, in square metres. */
    private function twoTiledRooms(float $first, float $second): array
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = $first + $second;
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => $first, 'finish' => 'FLOOR_TILE', 'currentState' => 'PLASTER_READY'],
        ];
        $inputs['rooms'][1] = $inputs['rooms'][0];
        $inputs['rooms'][1]['id'] = 'r2';
        $inputs['rooms'][1]['name'] = 'Hamam';
        $inputs['rooms'][1]['zones'][0]['area'] = $second;

        return $inputs;
    }

    /** One 10 m² tiled wall zone with the given format and layout. */
    private function tiledWall(string $format, ?string $layout = null): array
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'WALL', 'area' => 10, 'finish' => 'WALL_TILE', 'currentState' => 'PLASTER_READY',
                'options' => array_filter(['format' => $format, 'layout' => $layout])],
        ];

        return $inputs;
    }

    /** Every line traces back to quantity, price and source (master spec §24). */
    public function test_every_line_carries_its_calculation_trace(): void
    {
        foreach ($this->builder->build($this->inputs())['lines'] as $line) {
            $this->assertArrayHasKey('quantity', $line);
            $this->assertArrayHasKey('unitPrice', $line);
            $this->assertArrayHasKey('coefficients', $line);
            $this->assertArrayHasKey('source', $line);
            $this->assertNotSame('', $line['trace']);
        }
    }

    /**
     * One 5×4×3 living room with a door and a window, bare walls, laminate floor and
     * a painted ceiling — the shape scenario A of the specification describes.
     */
    /**
     * Master spec §9: the chain starts from what is on the surface NOW. A floor
     * that carries an old covering cannot take a new one until it comes off — the
     * wall chain always stripped old finishes, the floor chain used to skip them.
     */
    public function test_an_old_floor_covering_is_removed_before_the_new_one(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['condition'] = 'OLD_RENOVATION';
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 20, 'finish' => 'LAMINATE', 'currentState' => 'OLD_FINISH'],
        ];

        $lines = $this->linesById($this->estimateViaApi($inputs));

        $this->assertArrayHasKey('DEM_FLOOR_LAMINATE', $lines);
        $this->assertEquals(20, $lines['DEM_FLOOR_LAMINATE']['quantity']);

        // An old tiled floor takes the tile-removal rate, not the soft-cover one.
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 20, 'finish' => 'FLOOR_TILE', 'currentState' => 'OLD_TILE'],
        ];

        $lines = $this->linesById($this->estimateViaApi($inputs));

        $this->assertArrayHasKey('DEM_FLOOR_TILE', $lines);
        $this->assertArrayNotHasKey('DEM_FLOOR_LAMINATE', $lines);
    }

    /**
     * A doorway that receives a new door in an old flat still holds the old one:
     * its removal is suggested automatically. A bare shell has no old doors, so
     * demolition being switched on there must not invent any.
     */
    public function test_old_doors_are_removed_when_a_new_door_goes_into_an_old_flat(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['condition'] = 'OLD_RENOVATION';
        $inputs['object']['demolitionRequired'] = true;

        $lines = $this->linesById($this->estimateViaApi($inputs));

        $this->assertArrayHasKey('DEM_DOOR', $lines);
        $this->assertEquals(1, $lines['DEM_DOOR']['quantity']);

        $shell = $this->inputs();
        $shell['object']['demolitionRequired'] = true; // condition stays SHELL

        $this->assertArrayNotHasKey('DEM_DOOR', $this->linesById($this->estimateViaApi($shell)));
    }

    private function inputs(): array
    {
        return [
            'object' => [
                'objectType' => 'APARTMENT',
                'area' => 20,
                'cityKey' => 'BAKU',
                'floor' => 7,
                'totalFloors' => 16,
                'condition' => 'SHELL',
                'segment' => 'STANDARD',
                'hasFreightElevator' => false,
            ],
            'rooms' => [[
                'id' => 'r1',
                'name' => 'Qonaq',
                'roomType' => 'LIVING',
                // Millimetres — see App\Services\Calculator\Units. 5 × 4 m, 3 m high.
                'lengthMm' => 5000,
                'widthMm' => 4000,
                'heightMm' => 3000,
                'points' => [],
                'openings' => [
                    ['type' => 'DOOR', 'widthMm' => 900, 'heightMm' => 2100, 'qty' => 1, 'subtype' => 'INTERIOR', 'installNewDoor' => true],
                    ['type' => 'WINDOW', 'widthMm' => 1500, 'heightMm' => 1400, 'qty' => 1],
                ],
                'zones' => [
                    ['surface' => 'WALL', 'area' => 50.01, 'finish' => 'PAINT', 'currentState' => 'SHELL'],
                    ['surface' => 'FLOOR', 'area' => 20, 'finish' => 'LAMINATE', 'currentState' => 'PLASTER_READY'],
                    ['surface' => 'CEILING', 'area' => 20, 'finish' => 'CEILING_PAINT', 'currentState' => 'SHELL'],
                ],
            ]],
            'works' => [],
            'materials' => [],
            'modules' => ['demolition' => ['estimateDebris' => false], 'services' => ['finalCleaning' => false]],
            'basket' => ['useCart' => false],
        ];
    }

    /**
     * Estimate through the HTTP endpoint. Catalog products and the visitor's cart
     * are loaded by the controller, so a test about real products has to go the same
     * way a browser does rather than calling the builder with an empty catalog.
     */
    private function estimateViaApi(array $inputs): array
    {
        $response = $this->postJson('/api/calculator/estimate', $inputs);
        $response->assertOk();

        return $response->json();
    }

    private function product(array $attributes): Product
    {
        return Product::create(array_merge([
            'user_id' => User::factory()->seller()->create()->id,
            'category_id' => Category::create([
                'name' => ['az' => 'Kateqoriya'],
                'slug' => 'cat-'.uniqid(),
                'is_active' => true,
            ])->id,
            'name' => ['az' => 'Kafel 30×60'],
            'description' => ['az' => ''],
            'slug' => 'p-'.uniqid(),
            'stock' => 500,
            'is_visible' => true,
            'is_approved' => true,
            'unit' => 'm²',
        ], $attributes));
    }

    /** @return array<int, string> */
    private function lineIds(array $estimate): array
    {
        return array_column($estimate['lines'], 'itemId');
    }

    /** @return array<string, array<string, mixed>> */
    private function linesById(array $estimate): array
    {
        $lines = [];

        foreach ($estimate['lines'] as $line) {
            $lines[$line['itemId']] = $line;
        }

        return $lines;
    }
}
