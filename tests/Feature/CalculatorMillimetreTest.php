<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CalculatorSession;
use App\Models\User;
use App\Services\Calculator\EstimateBuilder;
use App\Services\Calculator\Units;
use Database\Seeders\CalculatorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The unit boundary.
 *
 * The visitor types millimetres; the tariffs are priced per m² and per running metre,
 * so the engine prices in metres. One class converts — App\Services\Calculator\Units
 * — and these tests are what hold that line: that the conversion happens, that it
 * happens exactly once, and that a session saved while the calculator still spoke
 * metres can never be re-read as millimetres and quote a client 4.5 mm of room.
 */
class CalculatorMillimetreTest extends TestCase
{
    use RefreshDatabase;

    private EstimateBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CalculatorSeeder::class);
        $this->builder = app(EstimateBuilder::class);
    }

    /** 4500 × 3200 mm is 14.4 m², and every derived figure follows in metres. */
    public function test_room_dimensions_are_read_as_millimetres_and_derived_in_metres(): void
    {
        $room = $this->builder->build($this->inputs())['rooms'][0];

        $this->assertSame(14.4, $room['floorArea']);        // 4.5 × 3.2
        $this->assertSame(14.4, $room['ceilingArea']);
        $this->assertSame(15.4, $room['perimeter']);        // 2 × (4.5 + 3.2)
        $this->assertSame(43.12, $room['grossWallArea']);   // 15.4 × 2.8
        // 43.12 − (0.9 × 2.1) − (1.5 × 1.4) = 39.13
        $this->assertSame(39.13, $room['netWallArea']);
        // The skirting does not cross the doorway: 15.4 − 0.9
        $this->assertSame(14.5, $room['skirtingLength']);
    }

    /**
     * The rounding of a dimension must not be able to drift: 2 437 mm is 2.437 m, not
     * 2.44 m, because a 15-room flat would otherwise lose a square metre of wall.
     */
    public function test_an_odd_millimetre_dimension_is_not_rounded_on_the_way_in(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['lengthMm'] = 2437;
        $inputs['rooms'][0]['widthMm'] = 1013;

        $room = $this->builder->build($inputs)['rooms'][0];

        $this->assertSame(2.4687, $room['floorArea']);   // 2.437 × 1.013
        $this->assertSame(6.9, $room['perimeter']);      // 2 × (2.437 + 1.013)
    }

    /** A missing height falls back to the configured default, also in millimetres. */
    public function test_the_default_room_height_is_configured_in_millimetres(): void
    {
        $inputs = $this->inputs();
        unset($inputs['rooms'][0]['heightMm']);

        // 15.4 m of perimeter × the 2 800 mm default
        $this->assertSame(43.12, $this->builder->build($inputs)['rooms'][0]['grossWallArea']);
    }

    /** A missing opening dimension falls back to the configured default, in mm. */
    public function test_the_default_opening_sizes_are_configured_in_millimetres(): void
    {
        $inputs = $this->inputs();
        $inputs['rooms'][0]['openings'] = [['type' => 'DOOR', 'qty' => 1]];

        $room = $this->builder->build($inputs)['rooms'][0];

        // 900 × 2100 mm = 1.89 m²
        $this->assertSame(1.89, $room['doorArea']);
    }

    /**
     * One rule, in both directions. The response does not repeat the entered length
     * in metres beside the millimetres the visitor typed — a reader who sees both
     * will believe the wrong one — and what it does echo, it echoes in millimetres.
     */
    public function test_the_response_never_mixes_the_two_units(): void
    {
        $room = $this->builder->build($this->inputs())['rooms'][0];

        foreach (['length', 'width', 'height', 'perimeterMm'] as $key) {
            $this->assertArrayNotHasKey($key, $room);
        }

        $this->assertSame(900.0, $room['openings'][0]['widthMm']);
        $this->assertSame(2100.0, $room['openings'][0]['heightMm']);
        $this->assertArrayNotHasKey('width', $room['openings'][0]);

        // Derived quantities stay in the unit their tariff is priced in.
        $this->assertSame(15.4, $room['perimeter']);
        $this->assertSame(14.5, $room['skirtingLength']);
    }

    /** An area is not a dimension: object.area stays square metres. */
    public function test_an_area_is_still_square_metres(): void
    {
        $inputs = $this->inputs();
        $inputs['object']['area'] = 14.4;
        $inputs['object']['designProject'] = true;

        // 14.4 m² × 15 ₼ = 216 ₼, lifted to the 300 ₼ minimum order — which only
        // makes sense if 14.4 was read as square metres rather than as millimetres.
        $this->assertSame(
            300.0,
            $this->lineById($this->builder->build($inputs), 'SERVICE_DESIGN')['lineTotal'],
        );
    }

    /**
     * THE MIGRATION TRAP. A session stored before the switch holds metres; reading it
     * as millimetres would turn a 4.5 m room into 4.5 mm, make every area zero and
     * quote the client nothing at all. The version stamp — not the magnitude of the
     * number — decides.
     */
    public function test_a_stored_metre_payload_is_upgraded_rather_than_misread(): void
    {
        $upgraded = Units::upgradePayload($this->legacyInputs());

        $this->assertSame(Units::PAYLOAD_VERSION, $upgraded['v']);
        $this->assertSame(4500.0, $upgraded['rooms'][0]['lengthMm']);
        $this->assertSame(3200.0, $upgraded['rooms'][0]['widthMm']);
        $this->assertSame(2800.0, $upgraded['rooms'][0]['heightMm']);
        $this->assertSame(900.0, $upgraded['rooms'][0]['openings'][0]['widthMm']);
        $this->assertSame(2100.0, $upgraded['rooms'][0]['openings'][0]['heightMm']);

        // The metre keys are gone, so nothing downstream can read them by accident.
        $this->assertArrayNotHasKey('length', $upgraded['rooms'][0]);
        $this->assertArrayNotHasKey('width', $upgraded['rooms'][0]['openings'][0]);

        // And the result is the same estimate the millimetre payload produces.
        $this->assertSame(14.4, $this->builder->build($upgraded)['rooms'][0]['floorArea']);
    }

    /** Upgrading twice must not multiply twice. */
    public function test_upgrading_an_already_current_payload_changes_nothing(): void
    {
        $once = Units::upgradePayload($this->legacyInputs());

        $this->assertSame($once, Units::upgradePayload($once));
    }

    /**
     * The upgrade sits on the single recalculation path, so a session saved in metres
     * months ago still reads back as the same flat through the HTTP API.
     */
    public function test_a_session_saved_in_metres_still_recalculates_correctly(): void
    {
        $this->actingAs(User::factory()->create());

        $uuid = $this->postJson('/api/calculator/sessions', $this->legacyInputs())
            ->assertCreated()
            ->json('uuid');

        // Stored under the current version, with the dimensions already converted.
        $stored = CalculatorSession::where('uuid', $uuid)->firstOrFail();
        $this->assertSame(14.4, $this->builder->build($stored->inputs)['rooms'][0]['floorArea']);

        $this->getJson("/api/calculator/sessions/{$uuid}")
            ->assertOk()
            ->assertJsonPath('estimate.rooms.0.floorArea', 14.4);
    }

    /**
     * A snapshot is a frozen record: it is served from its stored lines and totals and
     * is never recalculated, so the unit switch cannot move a figure a client already
     * has in a PDF (master spec §22).
     */
    public function test_a_snapshot_keeps_the_figures_it_was_frozen_with(): void
    {
        $this->actingAs(User::factory()->create());

        $uuid = $this->postJson('/api/calculator/sessions', $this->inputs())->assertCreated()->json('uuid');
        $snapshot = $this->postJson("/api/calculator/sessions/{$uuid}/snapshot")->assertCreated()->json();

        $frozen = $snapshot['totals']['grandTotal'];

        $this->getJson("/api/calculator/snapshots/{$snapshot['uuid']}")
            ->assertOk()
            ->assertJsonPath('totals.grandTotal', $frozen);
    }

    /** The frontend is told the unit and the payload version, so it cannot guess. */
    public function test_the_config_endpoint_publishes_the_millimetre_defaults(): void
    {
        $settings = $this->getJson('/api/calculator/config')->assertOk()->json('settings');

        $this->assertSame(Units::PAYLOAD_VERSION, $settings['payloadVersion']);
        $this->assertEquals(2800, $settings['defaultRoomHeightMm']);
        $this->assertEquals(900, $settings['defaultDoor']['widthMm']);
        $this->assertEquals(2100, $settings['defaultDoor']['heightMm']);
        $this->assertEquals(1500, $settings['defaultWindow']['widthMm']);
        $this->assertEquals(1400, $settings['defaultWindow']['heightMm']);

        // Nothing in the payload is still advertised in metres.
        $this->assertArrayNotHasKey('defaultRoomHeight', $settings);
        $this->assertArrayNotHasKey('width', $settings['defaultDoor']);
    }

    /** One 4500 × 3200 × 2800 mm room with a door and a window. */
    private function inputs(): array
    {
        return [
            'v' => Units::PAYLOAD_VERSION,
            'object' => [
                'objectType' => 'APARTMENT',
                'area' => 14.4,
                'cityKey' => 'BAKU',
                'floor' => 3,
                'condition' => 'SHELL',
                'segment' => 'STANDARD',
                'hasFreightElevator' => true,
            ],
            'rooms' => [[
                'id' => 'r1',
                'name' => 'Qonaq',
                'roomType' => 'LIVING',
                'lengthMm' => 4500,
                'widthMm' => 3200,
                'heightMm' => 2800,
                'points' => [],
                'openings' => [
                    ['type' => 'DOOR', 'widthMm' => 900, 'heightMm' => 2100, 'qty' => 1, 'subtype' => 'INTERIOR'],
                    ['type' => 'WINDOW', 'widthMm' => 1500, 'heightMm' => 1400, 'qty' => 1],
                ],
                'floorFinish' => 'LAMINATE',
                'wallFinish' => 'PAINT',
                'ceilingFinish' => 'CEILING_PAINT',
            ]],
            'works' => [],
            'materials' => [],
            'modules' => ['demolition' => ['estimateDebris' => false], 'services' => ['finalCleaning' => false]],
            'basket' => ['useCart' => false],
        ];
    }

    /** The same room as inputs(), in the metres a pre-switch session holds. */
    private function legacyInputs(): array
    {
        $inputs = $this->inputs();
        unset($inputs['v']);

        $inputs['rooms'][0]['length'] = 4.5;
        $inputs['rooms'][0]['width'] = 3.2;
        $inputs['rooms'][0]['height'] = 2.8;
        unset($inputs['rooms'][0]['lengthMm'], $inputs['rooms'][0]['widthMm'], $inputs['rooms'][0]['heightMm']);

        foreach ([0 => [0.9, 2.1], 1 => [1.5, 1.4]] as $index => [$width, $height]) {
            $inputs['rooms'][0]['openings'][$index]['width'] = $width;
            $inputs['rooms'][0]['openings'][$index]['height'] = $height;
            unset(
                $inputs['rooms'][0]['openings'][$index]['widthMm'],
                $inputs['rooms'][0]['openings'][$index]['heightMm'],
            );
        }

        return $inputs;
    }

    /** @return array<string, mixed> */
    private function lineById(array $estimate, string $itemId): array
    {
        foreach ($estimate['lines'] as $line) {
            if ($line['itemId'] === $itemId) {
                return $line;
            }
        }

        $this->fail("No line {$itemId} in the estimate.");
    }
}
