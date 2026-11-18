<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\CalculatorFinishResource;
use App\Filament\Resources\CalculatorMaterialFallbackResource;
use App\Filament\Resources\CalculatorRoomTypeResource;
use App\Filament\Resources\CalculatorRoomTypeResource\Pages\EditCalculatorRoomType;
use App\Filament\Resources\CalculatorWorkRateResource;
use App\Models\CalculatorFinish;
use App\Models\CalculatorRoomType;
use App\Models\CalculatorSetting;
use App\Models\CalculatorWorkRate;
use App\Models\User;
use App\Services\Calculator\EstimateBuilder;
use App\Services\Calculator\Units;
use App\Support\VersionedCache;
use Database\Seeders\CalculatorSeeder;
use Database\Seeders\CalculatorTzSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The calculator TZ of 17.11.2026 («Archi ətraflı kalkulyatorunun təkmilləşdirilməsi»),
 * held to its own acceptance list on Archi's test object «Sea Breeze»
 * (tests/Fixtures/calculator/sea_breeze.php).
 */
class CalculatorTzTest extends TestCase
{
    use RefreshDatabase;

    private EstimateBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CalculatorSeeder::class);
        $this->builder = app(EstimateBuilder::class);
    }

    // ---- «Sadə» mode norms (§6) ------------------------------------------------------

    /** The norms reproduce the project's own count within a point or two (TZ §6 check). */
    public function test_simple_mode_fills_the_points_from_the_room_type_norms(): void
    {
        $estimate = $this->seaBreeze();
        $points = $this->sumRooms($estimate, 'points');

        $this->assertTrue($estimate['pointsByNorm']);
        $this->assertSame(34, $points['sockets']);
        $this->assertSame(11, $points['switches']);
        $this->assertSame(28, $points['lights_spot']);       // 1 per 2.7 m² under plasterboard
        $this->assertSame(32, $points['lights_spot'] + $points['lights_chandelier'] + $points['lights_pendant']);
        $this->assertSame(4, $points['wall_lights']);
        $this->assertSame(2, $points['tv']);                 // not in the 12 m² bedroom
        $this->assertSame(3, $points['data']);
        $this->assertSame(4, $points['ac_power']);           // +1 in the 27.9 m² kitchen-living
        $this->assertSame(3, $points['appliance']);
        $this->assertSame(1, $points['intercom']);
        $this->assertSame(7, $points['water_points']);
        $this->assertSame(6, $points['sewer_points']);
    }

    /** A room the visitor edited keeps their numbers; untouched keys still come from the norm. */
    public function test_detailed_mode_keeps_the_visitors_points(): void
    {
        $estimate = $this->seaBreeze(detailed: true);
        $points = $this->sumRooms($estimate, 'points');

        $this->assertFalse($estimate['pointsByNorm']);
        $this->assertSame(33, $points['sockets']);
        $this->assertSame(27, $points['lights_spot']);
        $this->assertSame(7, $points['water_points']);       // fixtures still from the norm
    }

    /** «Sadə» must not be a different estimate: within 10 % of the detailed one (TZ acceptance). */
    public function test_simple_and_detailed_modes_agree_within_ten_percent(): void
    {
        foreach (['FULL', 'WHITE_BOX'] as $level) {
            $simple = $this->seaBreeze($level)['totals']['grandTotal'];
            $detailed = $this->seaBreeze($level, detailed: true)['totals']['grandTotal'];

            $this->assertLessThan(0.10, abs($simple - $detailed) / $detailed, $level);
        }
    }

    // ---- what used to be missing (§9, §13–§16, §19–§22) ------------------------------

    public function test_waterproofing_is_in_the_bathroom_only(): void
    {
        $lines = $this->seaBreeze()['lines'];

        $floor = $this->linesOf($lines, 'FLOOR_WATERPROOF');
        $walls = $this->linesOf($lines, 'TILE_WATERPROOF_WALL');

        $this->assertSame(['bath'], array_unique(array_column($floor, 'roomId')));
        $this->assertSame(['bath'], array_unique(array_column($walls, 'roomId')));
        $this->assertEqualsWithDelta(5.0, array_sum(array_column($floor, 'quantity')), 0.01);
    }

    public function test_a_plasterboard_ceiling_is_filled_primed_and_painted(): void
    {
        $lines = $this->seaBreeze()['lines'];

        foreach (['CEIL_PUTTY', 'CEIL_SANDING', 'CEIL_PRIMER_BASE', 'CEILING_PAINT'] as $id) {
            $this->assertEqualsWithDelta(67.2, $this->quantity($lines, $id), 0.05, $id);
        }

        $this->assertSame([], $this->linesOf($lines, 'CEIL_PLASTER'));   // plasterboard needs no plaster

        // Paint by the litre: 67.2 m² × 2 coats / 10 m² per litre.
        $paint = $this->linesOf($lines, 'CEILING_PAINT_MATERIAL')[0];
        $this->assertSame('L', $paint['unit']);
        $this->assertEqualsWithDelta(13.44, $paint['quantity'], 0.05);
    }

    public function test_windows_doors_waste_and_the_missing_materials_are_in_the_estimate(): void
    {
        $lines = $this->seaBreeze()['lines'];

        $this->assertEqualsWithDelta(16.22, $this->quantity($lines, 'WINDOW_SLOPES'), 0.01);   // Σ (w + 2h)
        $this->assertEqualsWithDelta(6.1, $this->quantity($lines, 'WINDOW_SILL'), 0.01);       // Σ w
        $this->assertEqualsWithDelta(6.1, $this->quantity($lines, 'MAT_WINDOW_SILL'), 0.01);
        $this->assertSame(3.0, $this->quantity($lines, 'DOOR_OPENING_PREP'));                  // the entrance door is not one
        $this->assertSame(3.0, $this->quantity($lines, 'DOOR_INSTALL_INTERIOR'));

        // No demolition, still construction waste: 67.2 m² × 0.05 m³.
        $this->assertEqualsWithDelta(3.36, $this->quantity($lines, 'DEM_DEBRIS_LOADING'), 0.01);
        $this->assertSame(1.0, $this->quantity($lines, 'DEM_DEBRIS_DISPOSAL'));

        foreach (['MAT_PIPE_WATER', 'MAT_PIPE_SEWER', 'MAT_ELECTRIC_PANEL', 'MAT_CONDUIT', 'MAT_PLASTER_ACCESSORIES', 'MAT_LAMINATE_UNDERLAY'] as $id) {
            $this->assertGreaterThan(0, $this->quantity($lines, $id), $id);
        }

        $this->assertSame($this->quantity($lines, 'ELEC_CABLE'), $this->quantity($lines, 'MAT_CONDUIT'));

        // Face plates of every point: 34 sockets + 11 switches + 2 TV + 3 data + 1 intercom + 3 appliance.
        $this->assertSame(54.0, $this->quantity($lines, 'MAT_ELECTRIC_ACCESSORIES'));
    }

    public function test_fixtures_and_fittings_are_priced_by_kind(): void
    {
        $lines = $this->byId($this->seaBreeze()['lines']);

        $this->assertLessThan($lines['MAT_TOILET']['unitPrice'], $lines['MAT_MIXER']['unitPrice']);
        $this->assertLessThan($lines['MAT_LIGHT_CHANDELIER']['unitPrice'], $lines['MAT_LIGHT_SPOT']['unitPrice']);
        $this->assertArrayNotHasKey('MAT_SANITARY_BASIC', $lines);
        $this->assertArrayNotHasKey('MAT_LIGHTING_BASIC', $lines);
        $this->assertArrayNotHasKey('LIGHT_FIXTURE_INSTALL', $lines);
    }

    // ---- one area (§5) ----------------------------------------------------------------

    public function test_every_line_reads_the_rooms_area(): void
    {
        $estimate = $this->seaBreeze();

        $this->assertEqualsWithDelta(67.2, $estimate['calcArea'], 0.01);
        $this->assertEqualsWithDelta(67.2, $this->quantity($estimate['lines'], 'LOG_FINAL_CLEANING'), 0.01);
        $this->assertSame(2.0, $this->quantity($estimate['lines'], 'LOG_DELIVERY_TRIP'));   // CEIL(67.2 / 40)
    }

    public function test_a_far_off_object_area_warns_but_never_blocks(): void
    {
        $inputs = $this->seaBreezeInputs();
        $inputs['object']['area'] = 55;
        $inputs['rooms'][2]['lengthMm'] = 12000;   // rooms now ≈ 99 m²
        $inputs['rooms'][2]['widthMm'] = 5000;

        $estimate = $this->builder->build($inputs);
        $mismatch = collect($estimate['warnings'])->firstWhere('code', 'AREA_MISMATCH');

        $this->assertNotNull($mismatch);
        $this->assertGreaterThan(0, $estimate['totals']['grandTotal']);
        $this->assertEqualsWithDelta($estimate['calcArea'], $mismatch['roomsArea'], 0.01);
    }

    // ---- money: margin, contingency, stages (§1, §2) --------------------------------

    public function test_the_contractor_margin_and_the_reserve_follow_the_tz_formula(): void
    {
        $totals = $this->seaBreeze()['totals'];

        $this->assertSame(15.0, $totals['marginPercent']);
        $this->assertEqualsWithDelta($totals['baseSubtotal'] * 0.15, $totals['margin'], 0.05);
        // Every line here is eligible: reserve = (base + margin) × 10 %.
        $this->assertEqualsWithDelta(($totals['baseSubtotal'] + $totals['margin']) * 0.10, $totals['contingency'], 0.05);
        $this->assertEqualsWithDelta(
            $totals['baseSubtotal'] + $totals['margin'] + $totals['contingency'],
            $totals['grandTotal'],
            0.01,
        );
    }

    public function test_the_stage_totals_add_up_to_the_grand_total(): void
    {
        $estimate = $this->seaBreeze();
        $stages = $estimate['totals']['stages'];

        $this->assertGreaterThan(0, $stages['WHITE_BOX']['total']);
        $this->assertGreaterThan(0, $stages['FINISH']['total']);
        $this->assertEqualsWithDelta(
            $stages['WHITE_BOX']['total'] + $stages['FINISH']['total'] + $estimate['totals']['servicesBlock']['total'],
            $estimate['totals']['grandTotal'],
            0.02,
        );

        // Logistics and the final cleaning go to the last stage ordered.
        $this->assertSame('FINISH', $this->byId($estimate['lines'])['LOG_FINAL_CLEANING']['stage']);
        $this->assertSame('WHITE_BOX', $this->byId($estimate['lines'])['WALL_PLASTER']['stage'] ?? 'WHITE_BOX');
    }

    public function test_white_box_switches_the_finishing_stage_off_and_lets_it_back_on(): void
    {
        $estimate = $this->seaBreeze('WHITE_BOX');

        $this->assertSame('WHITE_BOX', $estimate['level']);
        $this->assertSame([], array_values(array_filter($estimate['lines'], fn ($l) => $l['stage'] === 'FINISH')));
        $this->assertSame(0.0, $estimate['totals']['stages']['FINISH']['total']);
        $this->assertSame('WHITE_BOX', $this->byId($estimate['lines'])['LOG_FINAL_CLEANING']['stage']);
        // White Box still paints the walls and the plasterboard ceiling.
        $this->assertGreaterThan(0, $this->quantity($estimate['lines'], 'PAINT'));
        $this->assertGreaterThan(0, $this->quantity($estimate['lines'], 'CEILING_PAINT'));

        $inputs = $this->seaBreezeInputs('WHITE_BOX');
        $inputs['works'] = ['LAMINATE' => ['selected' => true]];
        $lines = $this->builder->build($inputs)['lines'];

        $this->assertGreaterThan(0, $this->quantity($lines, 'LAMINATE'));
        $this->assertGreaterThan(0, $this->quantity($lines, 'LAMINATE_MATERIAL'));
        $this->assertSame('FINISH', $this->linesOf($lines, 'LAMINATE')[0]['stage']);
    }

    /**
     * The TZ's own acceptance figures (White Box 430–480, full 620–680 AZN/m²) are
     * measured on Archi's admin prices of 17.11. On the seed prices the engine must
     * still land in the same neighbourhood, and the full renovation must cost more.
     */
    public function test_sea_breeze_lands_near_the_quick_calculator(): void
    {
        $wb = $this->seaBreeze('WHITE_BOX')['totals']['perM2'];
        $full = $this->seaBreeze()['totals']['perM2'];

        $this->assertGreaterThan($wb, $full);
        $this->assertEqualsWithDelta(455, $wb, 455 * 0.25, "White Box {$wb} AZN/m²");
        $this->assertEqualsWithDelta(650, $full, 650 * 0.25, "Full {$full} AZN/m²");
    }

    // ---- rooms: openings, zones, height (§4, §10, §12) --------------------------------

    public function test_a_passage_and_the_kitchen_furniture_shorten_the_skirting(): void
    {
        $kitchen = $this->room($this->seaBreeze(), 'kitchen');

        // 2 × (6.23 + 4.475) − 1.2 passage − 3.6 furniture.
        $this->assertEqualsWithDelta(16.61, $kitchen['skirtingLength'], 0.01);
        $this->assertEqualsWithDelta(1.2 * 2.3, $kitchen['passageArea'], 0.001);
        $this->assertEqualsWithDelta(
            $kitchen['grossWallArea'] - $kitchen['windowArea'] - $kitchen['passageArea'],
            $kitchen['netWallArea'],
            0.001,
        );

        // The skirting is laid once, whatever share of the floor is laminate.
        $skirting = array_filter($this->seaBreeze()['lines'], fn ($l) => $l['itemId'] === 'SKIRTING_INSTALL' && $l['roomId'] === 'kitchen');
        $this->assertCount(1, $skirting);
    }

    public function test_extra_floor_zones_split_the_surface(): void
    {
        $lines = $this->seaBreeze()['lines'];
        $kitchen = fn (string $id) => array_sum(array_column(array_filter($lines, fn ($l) => $l['itemId'] === $id && $l['roomId'] === 'kitchen'), 'quantity'));

        $this->assertEqualsWithDelta(2.0, $kitchen('FLOOR_TILE_LARGE'), 0.001);
        $this->assertEqualsWithDelta(6.23 * 4.475 - 2, $kitchen('LAMINATE'), 0.001);
    }

    public function test_rooms_take_the_objects_ceiling_height(): void
    {
        $inputs = $this->seaBreezeInputs();

        foreach ($inputs['rooms'] as $i => $room) {
            unset($inputs['rooms'][$i]['heightMm']);
        }

        $estimate = $this->builder->build($inputs);

        $this->assertSame([3200.0], array_values(array_unique(array_column($estimate['rooms'], 'heightMm'))));
    }

    // ---- own materials (§25) ----------------------------------------------------------

    public function test_a_material_bought_by_the_visitor_is_estimated_but_not_summed(): void
    {
        $inputs = $this->seaBreezeInputs();
        $inputs['materials'] = ['LAMINATE' => ['mode' => 'EXCLUDED']];

        $estimate = $this->builder->build($inputs);
        $line = $this->linesOf($estimate['lines'], 'LAMINATE_MATERIAL')[0];

        $this->assertSame(0.0, $line['lineTotal']);
        $this->assertEqualsWithDelta($line['quantity'] * $line['meta']['estimatedUnitPrice'], $line['meta']['estimatedTotal'], 0.01);
        $this->assertEqualsWithDelta($line['meta']['estimatedTotal'], $estimate['totals']['excludedMaterials'], 0.01);
    }

    // ---- config, data migration, admin ----------------------------------------------------

    public function test_the_config_endpoint_publishes_stages_levels_and_room_type_norms(): void
    {
        $config = $this->getJson('/api/calculator/config')->assertOk()->json();

        $this->assertSame(['FULL', 'WHITE_BOX'], array_column($config['enums']['levels'], 'value'));
        $this->assertSame(15.0, (float) $config['settings']['contractorMarginPercent']);
        $this->assertSame('WHITE_BOX', collect($config['works'])->firstWhere('workId', 'WALL_PLASTER')['stage']);
        $this->assertSame('FINISH', collect($config['finishes'])->firstWhere('id', 'LAMINATE')['labourStage']);

        $kitchenLiving = collect($config['enums']['roomTypes'])->firstWhere('value', 'KITCHEN_LIVING');
        $this->assertNotNull($kitchenLiving);
        $this->assertTrue($kitchenLiving['hasPlumbing']);
        $this->assertFalse($kitchenLiving['needsWaterproofing']);
        $this->assertContains('lights_spot', $kitchenLiving['pointKeys']);
        $this->assertSame(1, $kitchenLiving['defaultFixtures']['dishwasher']);
    }

    /** The production migration never overwrites a price Archi already set — and converts paint once. */
    public function test_the_data_migration_keeps_archis_prices(): void
    {
        CalculatorWorkRate::where('work_id', 'DOOR_OPENING_PREP')->update(['rate' => 77, 'is_active' => false]);
        CalculatorSetting::where('key', 'CONTRACTOR_MARGIN_PERCENT')->update(['value' => '12']);
        CalculatorRoomType::where('room_type', 'BEDROOM')->update(['ac_extra_above_m2' => 99]);
        $paint = (float) CalculatorFinish::where('finish_id', 'PAINT')->value('material_standard');

        (new CalculatorTzSeeder)->apply(fresh: false);

        $door = CalculatorWorkRate::where('work_id', 'DOOR_OPENING_PREP')->first();
        $this->assertSame(77.0, (float) $door->rate);
        $this->assertTrue($door->is_active);
        $this->assertSame('12', CalculatorSetting::where('key', 'CONTRACTOR_MARGIN_PERCENT')->value('value'));
        $this->assertSame(99.0, (float) CalculatorRoomType::where('room_type', 'BEDROOM')->value('ac_extra_above_m2'));
        $this->assertSame($paint, (float) CalculatorFinish::where('finish_id', 'PAINT')->value('material_standard'));
    }

    public function test_the_new_admin_screens_render_and_save(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs(User::factory()->admin()->create(), 'admin');

        // A category the code does not know yet must not take the page down (TZ §37).
        DB::table('calculator_work_rates')->where('work_id', 'WALL_PLASTER')->update(['category' => 'SOMETHING_NEW']);
        DB::table('calculator_work_rates')->where('work_id', 'WALL_PUTTY')->update(['category' => 'VENTILATION']);

        foreach ([CalculatorRoomTypeResource::class, CalculatorWorkRateResource::class, CalculatorFinishResource::class, CalculatorMaterialFallbackResource::class] as $resource) {
            $this->get($resource::getUrl('index'))->assertOk();
        }

        $this->get(CalculatorWorkRateResource::getUrl('edit', ['record' => CalculatorWorkRate::where('work_id', 'WINDOW_SLOPES')->first()]))
            ->assertOk()
            ->assertSee('Pəncərə yanlıqları');

        $bedroom = CalculatorRoomType::where('room_type', 'BEDROOM')->first();
        $this->get(CalculatorRoomTypeResource::getUrl('edit', ['record' => $bedroom]))->assertOk();

        Livewire::test(EditCalculatorRoomType::class, ['record' => $bedroom->getRouteKey()])
            ->fillForm(['point_norms.sockets.base' => 5, 'point_norms.sockets.per_m2' => 0.5])
            ->call('save')
            ->assertHasNoFormErrors();

        VersionedCache::bump(VersionedCache::CALCULATOR);
        $bed12 = $this->room(app(EstimateBuilder::class)->build($this->seaBreezeInputs()), 'bed12');

        $this->assertSame(11, $bed12['points']['sockets']);   // 5 + 0.5 × 12.0
    }

    // ---- helpers -----------------------------------------------------------------------

    private function seaBreezeInputs(string $level = 'FULL', bool $detailed = false): array
    {
        $make = require base_path('tests/Fixtures/calculator/sea_breeze.php');

        return Units::upgradePayload($make($level, $detailed));
    }

    private function seaBreeze(string $level = 'FULL', bool $detailed = false): array
    {
        return $this->builder->build($this->seaBreezeInputs($level, $detailed));
    }

    /** @return array<string, int> */
    private function sumRooms(array $estimate, string $key): array
    {
        $sum = [];

        foreach ($estimate['rooms'] as $room) {
            foreach ($room[$key] as $k => $v) {
                $sum[$k] = ($sum[$k] ?? 0) + $v;
            }
        }

        return $sum + array_fill_keys(['lights_pendant', 'lights_chandelier', 'tv', 'intercom', 'appliance'], 0);
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
