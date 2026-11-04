<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Calculator\RequestStatus;
use App\Enums\Calculator\RequestType;
use App\Models\CalculatorFinish;
use App\Models\CalculatorRequest;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Calculator\EstimateBuilder;
use App\Services\Calculator\Units;
use App\Support\VersionedCache;
use Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The simplified engine: finishes are admin rows, the technological chain is keyed
 * by the finish family, three surface states and three object conditions, and the
 * engineering modules are derived from the rooms. Plus the rules that create real
 * disputes with clients: waste versus contingency, a missing rate never becoming
 * free work, a minimum charged per order, and a snapshot that does not move when a
 * price does.
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

    // ---- geometry ----------------------------------------------------------------

    /** Geometry is derived, never asked for twice. */
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

    // ---- the chain, by family ----------------------------------------------------

    /** A bare wall to be painted gets the whole smoothing chain, then the paint. */
    public function test_a_bare_wall_under_a_coat_finish_gets_the_full_chain(): void
    {
        $works = $this->lineIds($this->builder->build($this->inputs()));

        foreach (['WALL_PRIMER_BASE', 'WALL_PLASTER', 'WALL_PUTTY', 'WALL_SANDING', 'WALL_PRIMER_FINISH', 'PAINT'] as $workId) {
            $this->assertContains($workId, $works, "Missing chain step {$workId}");
        }
    }

    /** A prepared wall skips the plaster; an old one is stripped first. */
    public function test_the_wall_state_decides_how_much_of_the_chain_applies(): void
    {
        $prepared = $this->lineIds($this->builder->build($this->withWall('PAINT', 'PREPARED')));
        $this->assertNotContains('WALL_PLASTER', $prepared);
        $this->assertNotContains('DEM_WALL_FINISH', $prepared);
        $this->assertContains('WALL_PUTTY', $prepared);
        $this->assertContains('PAINT', $prepared);

        $old = $this->lineIds($this->builder->build($this->withWall('PAINT', 'OLD')));
        $this->assertContains('DEM_WALL_FINISH', $old);
        $this->assertNotContains('WALL_PLASTER', $old);
        $this->assertContains('WALL_PUTTY', $old);
    }

    /** Every coat-type finish shares the chain — that is what makes a finish a row. */
    public function test_every_coat_finish_shares_the_same_preparation(): void
    {
        foreach (['PAINT', 'WALLPAPER', 'DECOR_PLASTER'] as $finish) {
            $works = $this->lineIds($this->builder->build($this->withWall($finish, 'BARE')));

            $this->assertContains('WALL_PUTTY', $works, $finish);
            $this->assertContains('WALL_PRIMER_FINISH', $works, $finish);
            $this->assertContains($finish, $works, $finish);
        }
    }

    /** A tiled wet wall: plaster when bare, tile prep, waterproofing, tile, grout, adhesive. */
    public function test_a_tiled_wet_wall_gets_the_tile_chain(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['roomType'] = 'BATHROOM';
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'WALL', 'area' => 20, 'finish' => 'WALL_TILE', 'currentState' => 'BARE'],
        ];

        $lines = $this->linesById($this->builder->build($inputs));

        foreach (['WALL_PRIMER_BASE', 'WALL_PLASTER', 'TILE_SURFACE_PREP', 'TILE_WATERPROOF_WALL', 'WALL_TILE', 'TILE_GROUT', 'MAT_TILE_ADHESIVE', 'MAT_GROUT'] as $id) {
            $this->assertArrayHasKey($id, $lines, "Missing {$id}");
        }

        // Nothing from the coat chain.
        $this->assertArrayNotHasKey('WALL_PUTTY', $lines);
        // 20 m² × 4.5 kg of adhesive.
        $this->assertSame(90.0, $lines['MAT_TILE_ADHESIVE']['quantity']);
    }

    /** A bare floor is screeded whatever goes on it; a wet one is tanked; floating floors get skirting. */
    public function test_the_floor_chain_follows_state_wetness_and_family(): void
    {
        $bareLaminate = $this->linesById($this->builder->build($this->withFloor('LAMINATE', 'BARE')));
        $this->assertArrayHasKey('FLOOR_SCREED', $bareLaminate);
        $this->assertArrayHasKey('SKIRTING_INSTALL', $bareLaminate);
        $this->assertArrayHasKey('MAT_SKIRTING', $bareLaminate);
        $this->assertArrayNotHasKey('FLOOR_WATERPROOF', $bareLaminate);
        $this->assertSame(17.1, $bareLaminate['SKIRTING_INSTALL']['quantity']);

        $inputs = $this->withFloor('FLOOR_TILE', 'BARE');
        $inputs['rooms'][0]['roomType'] = 'BATHROOM';
        $wetTile = $this->linesById($this->builder->build($inputs));
        $this->assertArrayHasKey('FLOOR_SCREED', $wetTile);
        $this->assertArrayHasKey('FLOOR_WATERPROOF', $wetTile);
        $this->assertArrayHasKey('TILE_SURFACE_PREP', $wetTile);
        $this->assertArrayHasKey('TILE_GROUT', $wetTile);
        $this->assertArrayNotHasKey('SKIRTING_INSTALL', $wetTile);
    }

    /** A suspended ceiling hides the slab, so it skips the smoothing chain a painted one needs. */
    public function test_a_suspended_ceiling_skips_the_smoothing_chain(): void
    {
        $painted = $this->lineIds($this->builder->build($this->withCeiling('CEILING_PAINT', 'BARE')));
        foreach (['CEIL_PRIMER_BASE', 'CEIL_PLASTER', 'CEIL_PUTTY', 'CEIL_SANDING', 'CEILING_PAINT'] as $id) {
            $this->assertContains($id, $painted);
        }

        $stretch = $this->lineIds($this->builder->build($this->withCeiling('STRETCH_CEILING', 'BARE')));
        $this->assertContains('STRETCH_CEILING', $stretch);
        $this->assertNotContains('CEIL_PLASTER', $stretch);
        $this->assertNotContains('CEIL_PUTTY', $stretch);
    }

    /**
     * The whole point of the finish table: a finish the admin adds tonight is priced
     * tomorrow with the right chain and no code change.
     */
    public function test_a_finish_added_in_the_admin_panel_is_priced_without_a_code_change(): void
    {
        CalculatorFinish::create([
            'finish_id' => 'MICROCEMENT',
            'surface' => 'WALL',
            'family' => 'COAT',
            'name' => ['az' => 'Mikrosement'],
            'unit' => 'm²',
            'labour_rate' => 25,
            'material_economy' => 20,
            'material_standard' => 30,
            'material_premium' => 45,
            'waste_percent' => 5,
            'is_active' => true,
        ]);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $lines = $this->linesById(app(EstimateBuilder::class)->build($this->withWall('MICROCEMENT', 'PREPARED', 10)));

        // The coat chain came with the family…
        $this->assertArrayHasKey('WALL_PUTTY', $lines);
        $this->assertArrayHasKey('WALL_PRIMER_FINISH', $lines);
        // …and both halves of the price came from the row.
        $this->assertSame(250.0, $lines['MICROCEMENT']['lineTotal']);              // 10 × 25
        $this->assertSame('Mikrosement', $lines['MICROCEMENT']['name']);
        $this->assertSame(10.5, $lines['MICROCEMENT_MATERIAL']['quantity']);       // +5% waste
        $this->assertSame(315.0, $lines['MICROCEMENT_MATERIAL']['lineTotal']);     // 10.5 × 30
        $this->assertSame('MICROCEMENT', $lines['MICROCEMENT_MATERIAL']['meta']['materialId']);
    }

    /** A format surcharge is its own row with its own rate — never a multiplier on top. */
    public function test_large_format_tile_is_its_own_finish_row(): void
    {
        $line = $this->linesById($this->builder->build($this->withWall('WALL_TILE_LARGE', 'PREPARED', 10)))['WALL_TILE_LARGE'];

        $this->assertSame([], $line['coefficients']);
        $this->assertSame(180.0, $line['lineTotal']); // 10 × 18
    }

    /** A finish that belongs to another surface is dropped, not priced per the wrong m². */
    public function test_a_finish_of_the_wrong_surface_is_ignored(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'WALL', 'area' => 50, 'finish' => 'LAMINATE', 'currentState' => 'BARE'],
        ];

        $this->assertArrayNotHasKey('LAMINATE', $this->linesById($this->builder->build($inputs)));
    }

    public function test_a_removed_work_disappears_from_the_estimate(): void
    {
        $inputs = $this->inputs();
        $inputs['works'] = ['WALL_PLASTER' => ['selected' => false]];

        $estimate = $this->builder->build($inputs);

        $this->assertNotContains('WALL_PLASTER', $this->lineIds($estimate));
        // The rest of the chain stays: removing a step is a warning, not a reset.
        $this->assertContains('PAINT', $this->lineIds($estimate));
    }

    // ---- modules derived from the rooms -------------------------------------------

    /** Electrics come from points, never from "area × rate". */
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

    /** A rewired object gets a panel and cable per point; a prepared base is not rewired. */
    public function test_rewiring_adds_the_panel_and_cable_per_point(): void
    {
        // A living room's default points: 8 + 2 + 3 + 2 + 1 + 1 + 1 = 18.
        $shell = $this->linesById($this->builder->build($this->inputs()));

        $this->assertSame(1.0, $shell['ELEC_PANEL_SMALL']['quantity']);
        $this->assertSame(126.0, $shell['ELEC_CABLE']['quantity']);          // 18 × 7 m
        $this->assertSame(378.0, $shell['ELEC_CABLE']['lineTotal']);         // 126 × 3
        $this->assertSame(126.0, $shell['MAT_CABLE']['quantity']);

        $inputs = $this->inputs();
        $inputs['object']['condition'] = 'PREPARED';
        $prepared = $this->linesById($this->builder->build($inputs));

        $this->assertArrayNotHasKey('ELEC_PANEL_SMALL', $prepared);
        $this->assertArrayNotHasKey('ELEC_CABLE', $prepared);
        $this->assertArrayHasKey('ELEC_SOCKET_POINT', $prepared);

        // The visitor's own answer wins over the condition's default.
        $inputs['object']['rewireElectrics'] = true;
        $this->assertArrayHasKey('ELEC_CABLE', $this->linesById($this->builder->build($inputs)));
    }

    /** One split unit per air-conditioner point: the room already says how many. */
    public function test_air_conditioning_is_counted_from_the_room_points(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['points'] = ['ac_power' => 2];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame(2.0, $lines['HVAC_SPLIT_STD']['quantity']);
        $this->assertSame(300.0, $lines['HVAC_SPLIT_STD']['lineTotal']); // 2 × 150
        $this->assertSame(2.0, $lines['ELEC_AC_POINT']['quantity']);

        $inputs['rooms'][0]['points'] = ['ac_power' => 0];
        $this->assertArrayNotHasKey('HVAC_SPLIT_STD', $this->linesById($this->builder->build($inputs)));
    }

    /** A fitting hangs on every light point, ceiling and wall. */
    public function test_light_fittings_follow_the_light_points(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['points'] = ['lights' => 3, 'wall_lights' => 2];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame(5.0, $lines['LIGHT_FIXTURE_INSTALL']['quantity']);
        $this->assertSame(60.0, $lines['LIGHT_FIXTURE_INSTALL']['lineTotal']); // 5 × 12
        $this->assertSame(5.0, $lines['MAT_LIGHTING_BASIC']['quantity']);
    }

    /** A bathroom's fixtures default from its type, are installed, and pull their pipes. */
    public function test_a_wet_room_gets_its_fixtures_and_pipe_runs(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['roomType'] = 'BATHROOM';

        $lines = $this->linesById($this->builder->build($inputs));

        // Bathroom defaults: toilet 1, basin 1, shower 1, mixer 2; water 4, sewer 3.
        $this->assertSame(1.0, $lines['PLUMB_TOILET_INSTALL']['quantity']);
        $this->assertSame(1.0, $lines['PLUMB_SHOWER_INSTALL']['quantity']);
        $this->assertSame(2.0, $lines['PLUMB_MIXER_INSTALL']['quantity']);
        $this->assertArrayNotHasKey('PLUMB_BATHTUB_INSTALL', $lines);
        $this->assertSame(5.0, $lines['MAT_SANITARY_BASIC']['quantity']);
        $this->assertSame(12.0, $lines['PLUMB_PIPE_WATER']['quantity']);   // 4 points × 3 m
        $this->assertSame(9.0, $lines['PLUMB_PIPE_SEWER']['quantity']);    // 3 points × 3 m

        // Edited counts win, one key at a time.
        $inputs['rooms'][0]['fixtures'] = ['bathtub' => 1, 'shower' => 0];
        $edited = $this->linesById($this->builder->build($inputs));

        $this->assertSame(1.0, $edited['PLUMB_BATHTUB_INSTALL']['quantity']);
        $this->assertArrayNotHasKey('PLUMB_SHOWER_INSTALL', $edited);
        $this->assertSame(1.0, $edited['PLUMB_TOILET_INSTALL']['quantity']);
    }

    /** A new door in an opening creates its installation line and its material. */
    public function test_an_opening_flagged_for_a_new_door_creates_the_installation_line(): void
    {
        $lines = $this->linesById($this->builder->build($this->inputs()));

        $this->assertSame(1.0, $lines['DOOR_INSTALL_INTERIOR']['quantity']);
        $this->assertSame(1.0, $lines['MAT_DOOR_INTERIOR']['quantity']);
    }

    // ---- materials ----------------------------------------------------------------

    /** A real product is billed in whole packages. */
    public function test_a_catalog_product_is_billed_in_whole_packages(): void
    {
        $product = $this->product(['package_size' => 1.44, 'package_unit' => 'qutu', 'price' => 20]);

        $inputs = $this->withFloor('FLOOR_TILE', 'PREPARED', 30);
        $inputs['materials'] = ['FLOOR_TILE' => ['mode' => 'CATALOG_PRODUCT', 'productId' => $product->id]];

        $lines = $this->linesById($this->estimateViaApi($inputs));
        $line = $lines['PRODUCT_'.$product->id];

        // 30 m² + 10% waste = 33 m²; CEIL(33 / 1.44) = 23 packs.
        $this->assertEquals(23.0, $line['quantity']);
        $this->assertEquals(460.0, $line['lineTotal']);
        $this->assertEquals('EXACT', $line['precisionClass']);
        $this->assertEquals('CATALOG_PRICE', $line['source']);
        $this->assertArrayNotHasKey('FLOOR_TILE_MATERIAL', $lines);
    }

    /** Without a product the finish's own segment price keeps the estimate complete. */
    public function test_a_material_without_a_product_falls_back_to_the_finish_segment_price(): void
    {
        $line = $this->linesById($this->builder->build($this->withFloor('LAMINATE', 'PREPARED')))['LAMINATE_MATERIAL'];

        // 20 m² + 8% waste = 21.6 m² × 14 AZN (standard segment).
        $this->assertSame(21.6, $line['quantity']);
        $this->assertSame(302.4, $line['lineTotal']);
        $this->assertSame('FALLBACK_CATEGORY_PRICE', $line['source']);
        $this->assertSame('CALCULATED', $line['precisionClass']);
        $this->assertSame('LAMINATE', $line['meta']['materialId']);

        $inputs = $this->withFloor('LAMINATE', 'PREPARED');
        $inputs['object']['segment'] = 'PREMIUM';
        $this->assertSame(475.2, $this->linesById($this->builder->build($inputs))['LAMINATE_MATERIAL']['lineTotal']); // 21.6 × 22
    }

    /** "I will buy it myself" keeps the row as a shopping list but takes no money. */
    public function test_an_excluded_material_stays_listed_at_zero(): void
    {
        $inputs = $this->withFloor('LAMINATE', 'PREPARED');
        $inputs['materials'] = ['LAMINATE' => ['mode' => 'EXCLUDED']];

        $line = $this->linesById($this->builder->build($inputs))['LAMINATE_MATERIAL'];

        $this->assertSame(21.6, $line['quantity']);
        $this->assertSame(0.0, $line['lineTotal']);
        $this->assertSame('MANUAL_QUOTE', $line['source']);
    }

    /** Paint is required per m² but bought per tin: a litre product converts through the coverage norm. */
    public function test_a_product_sold_by_the_litre_converts_the_painted_area_to_litres(): void
    {
        $product = $this->product(['unit' => 'litre', 'price' => 30, 'package_size' => null]);

        $inputs = $this->withWall('PAINT', 'PREPARED', 50);
        $inputs['materials'] = ['PAINT' => ['mode' => 'CATALOG_PRODUCT', 'productId' => $product->id]];

        $line = $this->linesById($this->estimateViaApi($inputs))['PRODUCT_'.$product->id];

        // 50 m² × 2 coats / 10 m² per litre = 10 L.
        $this->assertEquals(10.0, $line['quantity']);
        $this->assertEquals(300.0, $line['lineTotal']);
        $this->assertEquals(50.0, $line['meta']['paintedArea']);
    }

    // ---- logistics ----------------------------------------------------------------

    /** Delivery is a norm per area; lifting is charged above the free floors by the estimated weight. */
    public function test_delivery_and_lifting_are_estimated_from_the_object(): void
    {
        $lines = $this->linesById($this->builder->build($this->inputs()));

        // 20 m² → 1 trip of 60 ₼; 20 m² × 25 kg = 500 kg × (7 − 1 free) floors × 0.15.
        $this->assertSame(1.0, $lines['LOG_DELIVERY_TRIP']['quantity']);
        $this->assertSame(60.0, $lines['LOG_DELIVERY_TRIP']['lineTotal']);
        $this->assertSame(3000.0, $lines['LOG_MANUAL_LIFT']['quantity']);
        $this->assertSame(450.0, $lines['LOG_MANUAL_LIFT']['lineTotal']);

        $inputs = $this->inputs();
        $inputs['object']['area'] = 100;
        $this->assertSame(3.0, $this->linesById($this->builder->build($inputs))['LOG_DELIVERY_TRIP']['quantity']);
    }

    public function test_a_freight_lift_removes_the_manual_lifting_line(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['hasFreightElevator'] = true;

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertArrayNotHasKey('LOG_MANUAL_LIFT', $lines);
        $this->assertArrayHasKey('LOG_DELIVERY_TRIP', $lines);
    }

    /** Nothing is delivered when the visitor buys every material themselves. */
    public function test_no_delivery_when_every_material_is_excluded(): void
    {
        $inputs = $this->withFloor('LAMINATE', 'PREPARED');
        $inputs['rooms'][0]['points'] = ['sockets' => 0, 'switches' => 0, 'lights' => 0, 'wall_lights' => 0, 'tv' => 0, 'data' => 0, 'ac_power' => 0];
        $inputs['rooms'][0]['openings'] = [];
        $inputs['materials'] = ['LAMINATE' => ['mode' => 'EXCLUDED'], 'MAT_SKIRTING' => ['mode' => 'EXCLUDED']];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertArrayNotHasKey('LOG_DELIVERY_TRIP', $lines);
        $this->assertArrayNotHasKey('LOG_MANUAL_LIFT', $lines);
    }

    // ---- money rules --------------------------------------------------------------

    /** Master spec §15 — material waste is inside the quantity; the reserve is its own line. */
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

    /** The minimum charge lifts a small job, but never invents one out of nothing. */
    public function test_the_minimum_charge_applies_only_when_there_is_a_quantity(): void
    {
        $line = $this->linesById($this->builder->build($this->withWall('PAINT', 'BARE', 4)))['WALL_PLASTER'];

        // 4 m² × 9 = 36 AZN, below the 150 AZN minimum for plastering.
        $this->assertSame(150.0, $line['lineTotal']);
    }

    /** Master spec §19 — a work or a finish without a rate is reported, never priced at zero. */
    public function test_a_missing_rate_is_reported_instead_of_billed_as_free(): void
    {
        CalculatorWorkRate::where('work_id', 'WALL_PUTTY')->update(['rate' => null]);
        CalculatorFinish::where('finish_id', 'PAINT')->update(['labour_rate' => null, 'material_standard' => null]);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $estimate = app(EstimateBuilder::class)->build($this->inputs());
        $lines = $this->linesById($estimate);

        foreach (['WALL_PUTTY', 'PAINT', 'PAINT_MATERIAL'] as $id) {
            $this->assertSame('PRICE_MISSING', $lines[$id]['source'], $id);
            $this->assertNull($lines[$id]['unitPrice'], $id);
            $this->assertSame(0.0, $lines[$id]['lineTotal'], $id);
            $this->assertContains($id, $estimate['priceMissing']);
        }
    }

    /**
     * "Minimum sifariş" is a minimum ORDER, so it applies once to the job: 9 m² of
     * kitchen tile plus 4 m² of bathroom tile is one order of 13 m² = 195 ₼, not two
     * lines lifted to 150 ₼ each.
     */
    public function test_the_minimum_charge_applies_per_order_not_per_room(): void
    {
        $estimate = $this->builder->build($this->twoTiledRooms(9, 4));
        $lines = array_values(array_filter($estimate['lines'], fn (array $l) => $l['itemId'] === 'FLOOR_TILE'));

        $this->assertCount(2, $lines);
        $this->assertSame(195.0, round(array_sum(array_column($lines, 'lineTotal')), 2));
        $this->assertSame(150.0, $lines[0]['meta']['minimumChargeOrder']);
    }

    public function test_a_single_small_room_is_still_lifted_to_the_minimum(): void
    {
        $inputs = $this->twoTiledRooms(4, 0);
        unset($inputs['rooms'][1]);
        $inputs['rooms'] = array_values($inputs['rooms']);

        $line = $this->linesById($this->builder->build($inputs))['FLOOR_TILE'];

        $this->assertSame(4.0, $line['quantity']);
        $this->assertSame(150.0, $line['lineTotal']); // 60 ₼ raw, lifted
    }

    public function test_rooms_under_the_minimum_together_sum_to_exactly_one_minimum(): void
    {
        $lines = array_filter(
            $this->builder->build($this->twoTiledRooms(4, 4))['lines'],
            fn (array $l) => $l['itemId'] === 'FLOOR_TILE',
        );

        $this->assertSame(150.0, round(array_sum(array_column($lines, 'lineTotal')), 2));
    }

    /** The same work in one room is one line, not five (master spec §19). */
    public function test_repeated_work_in_one_room_is_consolidated_into_a_single_line(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'WALL', 'area' => 25, 'finish' => 'PAINT', 'currentState' => 'BARE'],
            ['surface' => 'WALL', 'area' => 25.01, 'finish' => 'PAINT', 'currentState' => 'BARE'],
        ];

        $painting = array_values(array_filter(
            $this->builder->build($inputs)['lines'],
            fn (array $line) => $line['itemId'] === 'PAINT',
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

        $this->assertSame([['id' => 'K_HEIGHT_3_01_3_50', 'value' => 1.15]], $lines['PAINT']['coefficients']);
        $this->assertSame([['id' => 'K_HEIGHT_3_01_3_50', 'value' => 1.15]], $lines['WALL_PLASTER']['coefficients']);
        $this->assertSame([], $lines['LAMINATE']['coefficients']);
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

    /** Every configurable number really is configurable (master spec §21). */
    public function test_admin_settings_change_the_result_without_a_code_change(): void
    {
        $before = $this->builder->build($this->inputs())['totals'];

        CalculatorSetting::where('key', 'CONTINGENCY_PERCENT')->update(['value' => '0']);
        CalculatorSetting::where('key', 'CABLE_M_PER_POINT')->update(['value' => '10']);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $estimate = app(EstimateBuilder::class)->build($this->inputs());
        $after = $estimate['totals'];

        $this->assertGreaterThan(0, $before['contingency']);
        $this->assertSame(0.0, $after['contingency']);
        $this->assertSame($after['baseSubtotal'], $after['grandTotal']);
        $this->assertSame(180.0, $this->linesById($estimate)['ELEC_CABLE']['quantity']); // 18 points × 10 m
    }

    // ---- demolition ----------------------------------------------------------------

    /** The demolition switch governs the whole block; old items exist only in an old flat. */
    public function test_the_demolition_switch_governs_the_demolition_block(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['condition'] = 'OLD_RENOVATION';
        $inputs['object']['demolitionRequired'] = true;
        $inputs['rooms'][0]['roomType'] = 'BATHROOM';

        $on = $this->linesById($this->builder->build($inputs));

        $this->assertSame(1.0, $on['DEM_DOOR']['quantity']);
        $this->assertSame(5.0, $on['DEM_PLUMB_FIXTURE']['quantity']);      // the bathroom's fixtures
        $this->assertArrayHasKey('DEM_ELECTRIC_POINT', $on);               // rewired by default
        $this->assertSame(1.0, $on['DEM_DEBRIS_LOADING']['quantity']);     // 20 m² × 0.05 m³
        $this->assertSame(1.0, $on['DEM_DEBRIS_DISPOSAL']['quantity']);

        $inputs['object']['demolitionRequired'] = false;
        $off = $this->linesById($this->builder->build($inputs));

        foreach (['DEM_DOOR', 'DEM_PLUMB_FIXTURE', 'DEM_ELECTRIC_POINT', 'DEM_DEBRIS_LOADING', 'DEM_DEBRIS_DISPOSAL'] as $id) {
            $this->assertArrayNotHasKey($id, $off, $id);
        }
    }

    /** A bare shell someone demolishes in has no old doors or fixtures to remove — only debris. */
    public function test_a_shell_has_nothing_old_to_remove_even_with_demolition_on(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['demolitionRequired'] = true; // condition stays SHELL

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertArrayNotHasKey('DEM_DOOR', $lines);
        $this->assertArrayNotHasKey('DEM_ELECTRIC_POINT', $lines);
        $this->assertArrayHasKey('DEM_DEBRIS_LOADING', $lines);
    }

    /** Stripping old wallpaper belongs to the wall's chain, not to the demolition block. */
    public function test_surface_stripping_survives_the_demolition_switch(): void
    {
        $inputs = $this->withWall('PAINT', 'OLD', 50);
        $inputs['object']['demolitionRequired'] = false;

        $this->assertArrayHasKey('DEM_WALL_FINISH', $this->linesById($this->builder->build($inputs)));
    }

    /** An old flat defaults to needing demolition. */
    public function test_demolition_defaults_from_the_object_condition(): void
    {
        $inputs = $this->inputs();
        unset($inputs['object']['demolitionRequired']);
        $inputs['object']['condition'] = 'OLD_RENOVATION';

        $this->assertArrayHasKey('DEM_DEBRIS_LOADING', $this->linesById($this->builder->build($inputs)));
    }

    /** A wet room's old surfaces are assumed tiled; a dry room's are not. */
    public function test_old_coverings_are_removed_at_the_rate_the_room_implies(): void
    {
        $dry = $this->linesById($this->builder->build($this->withFloor('LAMINATE', 'OLD')));
        $this->assertSame(20.0, $dry['DEM_FLOOR_LAMINATE']['quantity']);
        $this->assertArrayNotHasKey('DEM_FLOOR_TILE', $dry);

        $inputs = $this->inputs();
        $inputs['rooms'][0]['roomType'] = 'BATHROOM';
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 20, 'finish' => 'FLOOR_TILE', 'currentState' => 'OLD'],
            ['surface' => 'WALL', 'area' => 20, 'finish' => 'WALL_TILE', 'currentState' => 'OLD'],
        ];
        $wet = $this->linesById($this->builder->build($inputs));
        $this->assertSame(20.0, $wet['DEM_FLOOR_TILE']['quantity']);
        $this->assertSame(20.0, $wet['DEM_WALL_TILE']['quantity']);
        $this->assertArrayNotHasKey('DEM_FLOOR_LAMINATE', $wet);
    }

    /** The debris estimate and the final cleaning read the same object area. */
    public function test_debris_and_cleaning_are_measured_against_the_same_object_area(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = 100;           // rooms add up to 20 m²
        $inputs['object']['demolitionRequired'] = true;
        $inputs['modules'] = ['services' => ['finalCleaning' => true]];

        $lines = $this->linesById($this->builder->build($inputs));

        $this->assertSame(100.0, $lines['LOG_FINAL_CLEANING']['quantity']);
        $this->assertSame(5.0, $lines['DEM_DEBRIS_LOADING']['quantity']); // 100 × 0.05
    }

    // ---- sealant -------------------------------------------------------------------

    /** The sealed joint runs around the room, so it is billed once per room. */
    public function test_tile_sealant_is_billed_once_per_room_not_once_per_tiled_surface(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = 4;
        $inputs['rooms'][0]['roomType'] = 'BATHROOM';
        $inputs['rooms'][0]['lengthMm'] = 2000;
        $inputs['rooms'][0]['widthMm'] = 2000;
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 4, 'finish' => 'FLOOR_TILE', 'currentState' => 'BARE', 'wetZone' => true],
            ['surface' => 'WALL', 'area' => 18, 'finish' => 'WALL_TILE', 'currentState' => 'PREPARED', 'wetZone' => true],
        ];

        $estimate = $this->builder->build($inputs);
        $sealant = array_values(array_filter($estimate['lines'], fn (array $l) => $l['itemId'] === 'TILE_SILICONE'));

        $this->assertCount(1, $sealant);
        $this->assertSame(8.0, $sealant[0]['quantity']);   // 2 × (2 + 2), not 16
        $this->assertSame(32.0, $sealant[0]['lineTotal']);
        // Grout IS per square metre of tile, so both surfaces count: 4 + 18.
        $this->assertSame(22.0, $this->linesById($estimate)['TILE_GROUT']['quantity']);
    }

    public function test_a_dry_tiled_room_gets_no_sealant(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [
            ['surface' => 'FLOOR', 'area' => 20, 'finish' => 'FLOOR_TILE', 'currentState' => 'BARE', 'wetZone' => false],
        ];

        $this->assertArrayNotHasKey('TILE_SILICONE', $this->linesById($this->builder->build($inputs)));
    }

    // ---- services, views, warnings ----------------------------------------------------

    public function test_the_design_switch_adds_the_design_project_line(): void
    {
        $off = $this->linesById($this->builder->build($this->inputs()));
        $this->assertArrayNotHasKey('SERVICE_DESIGN', $off);

        $inputs = $this->inputs();
        $inputs['object']['designProject'] = true;
        $inputs['object']['area'] = 100;

        $this->assertSame(1500.0, $this->linesById($this->builder->build($inputs))['SERVICE_DESIGN']['lineTotal']);
    }

    public function test_the_design_project_is_excluded_from_the_contingency_base(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['designProject'] = true;

        $design = $this->linesById($this->builder->build($inputs))['SERVICE_DESIGN'];

        $this->assertFalse($design['contingencyEligible']);
        $this->assertGreaterThan(0, $design['lineTotal']);
    }

    /** Master spec §16.2 — the same lines, grouped by room. */
    public function test_the_estimate_can_be_read_by_room_as_well_as_by_category(): void
    {
        $estimate = $this->builder->build($this->inputs());

        $this->assertNotEmpty($estimate['groups']);
        $this->assertSame('Qonaq', $estimate['byRoom'][0]['name']);
        $this->assertGreaterThan(0, $estimate['byRoom'][0]['total']);
    }

    public function test_an_area_mismatch_warns_but_still_produces_an_estimate(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = 200;

        $estimate = $this->builder->build($inputs);
        $codes = array_column($estimate['warnings'], 'code');

        $this->assertContains('AREA_MISMATCH', $codes);
        $this->assertGreaterThan(0, $estimate['totals']['grandTotal']);
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

    // ---- payload upgrade ---------------------------------------------------------------

    /** A session saved by the previous calculator is read, not refused. */
    public function test_a_version_two_payload_is_upgraded_to_the_finish_rows(): void
    {
        $old = [
            'v' => 2,
            'object' => ['condition' => 'PARTIAL', 'marketType' => 'SECONDARY', 'totalFloors' => 9, 'area' => 20],
            'rooms' => [[
                'id' => 'r1', 'roomType' => 'BATHROOM', 'lengthMm' => 2000, 'widthMm' => 2000, 'currentState' => 'OLD_TILE',
                'floorFinish' => 'FLOOR_TILE', 'wallFinish' => 'WALL_TILE', 'ceilingFinish' => 'CEILING_PAINT',
                'zones' => [['surface' => 'FLOOR', 'area' => 4, 'finish' => 'FLOOR_TILE', 'currentState' => 'ROUGH_FLOOR']],
            ]],
            'materials' => ['MAT_FLOOR_TILE' => ['mode' => 'EXCLUDED']],
            'works' => ['FLOOR_TILE_INSTALL' => ['selected' => false]],
            'modules' => ['hvac' => ['systemType' => 'VRF'], 'services' => ['finalCleaning' => false]],
        ];

        $upgraded = Units::upgradePayload($old);

        $this->assertSame(3, $upgraded['v']);
        $this->assertSame('PREPARED', $upgraded['object']['condition']);
        $this->assertArrayNotHasKey('marketType', $upgraded['object']);
        $this->assertSame('OLD', $upgraded['rooms'][0]['currentState']);
        $this->assertSame('BARE', $upgraded['rooms'][0]['zones'][0]['currentState']);
        $this->assertSame(['FLOOR_TILE' => ['mode' => 'EXCLUDED']], $upgraded['materials']);
        $this->assertSame(['FLOOR_TILE' => ['selected' => false]], $upgraded['works']);
        $this->assertSame(['services' => ['finalCleaning' => false]], $upgraded['modules']);

        // When only the old market question was answered, it still seeds the condition.
        $this->assertSame('OLD_RENOVATION', Units::upgradePayload(['v' => 2, 'object' => ['marketType' => 'SECONDARY']])['object']['condition']);

        // And the upgraded payload prices normally.
        $this->assertGreaterThan(0, $this->builder->build($upgraded)['totals']['grandTotal']);
    }

    // ---- snapshots, cart, requests -----------------------------------------------------

    /** A stored estimate does not move when a rate does. */
    public function test_a_snapshot_keeps_its_prices_after_a_rate_change(): void
    {
        $user = User::factory()->create();

        $create = $this->actingAs($user)->postJson('/api/calculator/sessions', $this->inputs());
        $create->assertCreated();

        $uuid = $create->json('uuid');
        $snapshot = $this->actingAs($user)->postJson("/api/calculator/sessions/{$uuid}/snapshot");
        $snapshot->assertCreated();

        $frozenTotal = $snapshot->json('totals.grandTotal');

        CalculatorFinish::where('finish_id', 'PAINT')->update(['labour_rate' => 40]);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $stored = $this->actingAs($user)->getJson('/api/calculator/snapshots/'.$snapshot->json('uuid'));
        $recalculated = $this->actingAs($user)->getJson("/api/calculator/sessions/{$uuid}");

        $this->assertSame($frozenTotal, $stored->json('totals.grandTotal'));
        $this->assertGreaterThan($frozenTotal, $recalculated->json('estimate.totals.grandTotal'));
    }

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

    public function test_a_guest_cart_sent_with_the_request_is_priced_from_the_catalog(): void
    {
        $product = $this->product(['price' => 80]);

        $inputs = $this->inputs();
        $inputs['basket'] = [
            'useCart' => true,
            'excluded' => [],
            'items' => [['productId' => $product->id, 'quantity' => 3]],
        ];

        $response = $this->postJson('/api/calculator/estimate', $inputs);
        $response->assertOk();

        $line = collect($response->json('lines'))->firstWhere('itemId', 'CART_'.$product->id);

        $this->assertNotNull($line);
        $this->assertEquals(3, $line['quantity']);
        $this->assertEquals(240.0, $line['lineTotal']); // 3 × catalog 80, not anything client-sent
    }

    public function test_a_product_used_as_a_material_is_not_billed_twice_from_the_cart(): void
    {
        $product = $this->product(['package_size' => 1.44, 'price' => 20]);

        $inputs = $this->withFloor('FLOOR_TILE', 'PREPARED', 30);
        $inputs['materials'] = ['FLOOR_TILE' => ['mode' => 'CATALOG_PRODUCT', 'productId' => $product->id]];
        $inputs['basket'] = ['useCart' => true, 'items' => [['productId' => $product->id, 'quantity' => 2]]];

        $lines = $this->linesById($this->estimateViaApi($inputs));

        $this->assertArrayHasKey('PRODUCT_'.$product->id, $lines);
        $this->assertArrayNotHasKey('CART_'.$product->id, $lines);
        $this->assertEquals(23, $lines['PRODUCT_'.$product->id]['quantity']);
    }

    public function test_an_unpublished_product_in_the_browser_cart_is_ignored(): void
    {
        $product = $this->product(['price' => 80, 'is_visible' => false]);

        $inputs = $this->inputs();
        $inputs['basket'] = ['useCart' => true, 'items' => [['productId' => $product->id, 'quantity' => 2]]];

        $response = $this->postJson('/api/calculator/estimate', $inputs);

        $this->assertNull(collect($response->json('lines'))->firstWhere('itemId', 'CART_'.$product->id));
    }

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
        $this->assertNotNull($request->calculator_snapshot_id);
        $this->assertEqualsWithDelta($created->json('estimate.totals.grandTotal'), (float) $request->total_amount, 0.01);

        $frozen = (float) $request->total_amount;

        CalculatorFinish::where('finish_id', 'PAINT')->update(['labour_rate' => 99]);
        VersionedCache::bump(VersionedCache::CALCULATOR);

        $this->assertSame($frozen, (float) $request->fresh()->total_amount);
    }

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

    // ---- helpers -----------------------------------------------------------------------

    /**
     * One 5×4×3 living room with a door and a window in a bare shell: painted walls,
     * laminate on a prepared floor, a painted ceiling. Final cleaning off so the
     * service lines do not blur the work assertions.
     */
    private function inputs(): array
    {
        return [
            'v' => Units::PAYLOAD_VERSION,
            'object' => [
                'objectType' => 'APARTMENT',
                'area' => 20,
                'cityKey' => 'BAKU',
                'floor' => 7,
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
                    ['type' => 'DOOR', 'widthMm' => 900, 'heightMm' => 2100, 'qty' => 1, 'installNewDoor' => true],
                    ['type' => 'WINDOW', 'widthMm' => 1500, 'heightMm' => 1400, 'qty' => 1],
                ],
                'zones' => [
                    ['surface' => 'WALL', 'area' => 50.01, 'finish' => 'PAINT', 'currentState' => 'BARE'],
                    ['surface' => 'FLOOR', 'area' => 20, 'finish' => 'LAMINATE', 'currentState' => 'PREPARED'],
                    ['surface' => 'CEILING', 'area' => 20, 'finish' => 'CEILING_PAINT', 'currentState' => 'BARE'],
                ],
            ]],
            'works' => [],
            'materials' => [],
            'modules' => ['services' => ['finalCleaning' => false]],
            'basket' => ['useCart' => false],
        ];
    }

    /** The base room with a single wall zone of the given finish, state and area. */
    private function withWall(string $finish, string $state, float $area = 50.01): array
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [['surface' => 'WALL', 'area' => $area, 'finish' => $finish, 'currentState' => $state]];

        return $inputs;
    }

    private function withFloor(string $finish, string $state, float $area = 20): array
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [['surface' => 'FLOOR', 'area' => $area, 'finish' => $finish, 'currentState' => $state]];

        return $inputs;
    }

    private function withCeiling(string $finish, string $state, float $area = 20): array
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['zones'] = [['surface' => 'CEILING', 'area' => $area, 'finish' => $finish, 'currentState' => $state]];

        return $inputs;
    }

    /** Two rooms with tiled floors of the given areas, in square metres. */
    private function twoTiledRooms(float $first, float $second): array
    {
        $inputs = $this->withFloor('FLOOR_TILE', 'PREPARED', $first);
        $inputs['object']['area'] = $first + $second;
        $inputs['rooms'][1] = $inputs['rooms'][0];
        $inputs['rooms'][1]['id'] = 'r2';
        $inputs['rooms'][1]['name'] = 'Hamam';
        $inputs['rooms'][1]['zones'][0]['area'] = $second;

        return $inputs;
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
