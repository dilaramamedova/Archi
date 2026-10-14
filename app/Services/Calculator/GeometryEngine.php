<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\FinishCategory;
use App\Enums\Calculator\GeometryMode;
use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\RoomType;
use App\Enums\Calculator\SurfaceState;
use App\Enums\Calculator\SurfaceType;
use App\Enums\Calculator\WarmFloorType;

/**
 * Turns the raw room answers into geometry and into the aggregated quantity layer
 * every work formula reads from (master spec §6, §6.3).
 *
 * The whole point is that a quantity is derived exactly once. A door is entered with
 * its width and height — not merely counted — because NetWallArea is mathematically
 * impossible without them, and NetWallArea is what plaster, putty, sanding, primer,
 * paint and wallpaper are all billed by.
 *
 * THIS CLASS IS THE UNIT BOUNDARY. Linear dimensions arrive in millimetres (keys
 * ending in `Mm`) and leave as metres; areas arrive and leave in m². Downstream —
 * the dependency engine, the modules, the materials, the tariffs — knows nothing but
 * metres. Every conversion goes through Units::mmToM(); see Units for the full rule.
 */
class GeometryEngine
{
    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, array<string, mixed>>  $rooms  raw room input
     * @return array<int, RoomGeometry>
     */
    public function buildRooms(array $rooms, ObjectCondition $condition): array
    {
        $built = [];

        foreach (array_values($rooms) as $index => $room) {
            $built[] = $this->buildRoom($room, $index, $condition);
        }

        return $built;
    }

    private function buildRoom(array $input, int $index, ObjectCondition $condition): RoomGeometry
    {
        $warnings = [];

        $id = (string) ($input['id'] ?? 'room-'.($index + 1));
        $roomType = RoomType::tryFrom((string) ($input['roomType'] ?? '')) ?? RoomType::Other;
        $name = trim((string) ($input['name'] ?? '')) ?: __($roomType->labelKey());
        $mode = GeometryMode::tryFrom((string) ($input['geometryMode'] ?? '')) ?? GeometryMode::Standard;

        // mm → m, once. Everything below this line is metres and m².
        $length = Units::dimension($input, 'lengthMm');
        $width = Units::dimension($input, 'widthMm');
        $height = Units::dimension($input, 'heightMm', $this->config->float('DEFAULT_ROOM_HEIGHT_MM', 2800));

        $openings = $this->normaliseOpenings($input['openings'] ?? []);
        $doorArea = $this->openingArea($openings, 'DOOR');
        $windowArea = $this->openingArea($openings, 'WINDOW');
        $doorWidthSum = $this->doorWidthSum($openings);

        if ($mode === GeometryMode::Manual) {
            // Complex shape: the visitor (or a sales manager) states the three
            // quantities directly rather than letting L×W lie about the room.
            // The two areas are m² — an area was never in millimetres; only the
            // perimeter is a linear dimension and therefore mm.
            $floorArea = max(0.0, (float) ($input['floorArea'] ?? 0));
            $perimeter = Units::dimension($input, 'perimeterMm');
            $grossWallArea = $perimeter * $height;
            $netWallArea = (float) ($input['netWallArea'] ?? max($grossWallArea - $doorArea - $windowArea, 0));
        } else {
            if ($length <= 0 || $width <= 0) {
                $warnings[] = 'ROOM_DIMENSIONS_REQUIRED';
            }

            $floorArea = $length * $width;
            $perimeter = 2 * ($length + $width);
            $grossWallArea = $perimeter * $height;
            $netWallArea = max($grossWallArea - $doorArea - $windowArea, 0.0);

            if ($grossWallArea > 0 && $doorArea + $windowArea > $grossWallArea) {
                $warnings[] = 'OPENINGS_EXCEED_WALL_AREA';
            }
        }

        $ceilingArea = $floorArea;
        $skirtingLength = max($perimeter - $doorWidthSum, 0.0);

        $zones = $this->buildZones($input, $id, $roomType, $condition, $floorArea, $ceilingArea, $netWallArea, $warnings);

        $warmFloorType = WarmFloorType::tryFrom((string) (data_get($input, 'warmFloor.type') ?? 'NONE')) ?? WarmFloorType::None;
        $warmFloorArea = (float) (data_get($input, 'warmFloor.area') ?? $floorArea);

        if ($warmFloorType === WarmFloorType::None) {
            $warmFloorArea = 0.0;
        } elseif ($warmFloorArea > $floorArea + 0.001) {
            // A warm floor larger than the floor it lies on is an input error, not a
            // reason to refuse a number: clamp and say so.
            $warnings[] = 'WARM_FLOOR_AREA_EXCEEDS_FLOOR';
            $warmFloorArea = $floorArea;
        }

        return new RoomGeometry(
            id: $id,
            name: $name,
            roomType: $roomType,
            geometryMode: $mode,
            height: $height,
            floorArea: $floorArea,
            ceilingArea: $ceilingArea,
            perimeter: $perimeter,
            grossWallArea: $grossWallArea,
            doorArea: $doorArea,
            windowArea: $windowArea,
            netWallArea: $netWallArea,
            skirtingLength: $skirtingLength,
            openings: $openings,
            zones: $zones,
            warmFloorType: $warmFloorType,
            warmFloorArea: max(0.0, $warmFloorArea),
            points: $this->normalisePoints($input, $roomType),
            wetZone: (bool) ($input['wetZone'] ?? $roomType->isWet()),
            warnings: $warnings,
        );
    }

    /**
     * Openings carry real dimensions with editable defaults, so the anketa stays
     * short without giving up the arithmetic (master spec §6).
     *
     * Input is `widthMm` / `heightMm`; the normalised opening is metres, like every
     * other figure the engine passes around.
     */
    private function normaliseOpenings(mixed $raw): array
    {
        $defaults = [
            'DOOR' => [
                'widthMm' => $this->config->float('DEFAULT_DOOR_WIDTH_MM', 900),
                'heightMm' => $this->config->float('DEFAULT_DOOR_HEIGHT_MM', 2100),
            ],
            'WINDOW' => [
                'widthMm' => $this->config->float('DEFAULT_WINDOW_WIDTH_MM', 1500),
                'heightMm' => $this->config->float('DEFAULT_WINDOW_HEIGHT_MM', 1400),
            ],
        ];

        $openings = [];

        foreach (is_array($raw) ? $raw : [] as $opening) {
            $type = strtoupper((string) ($opening['type'] ?? 'DOOR'));

            if (! isset($defaults[$type])) {
                continue;
            }

            $qty = max(0, (int) ($opening['qty'] ?? 1));

            if ($qty === 0) {
                continue;
            }

            $openings[] = [
                'type' => $type,
                'width' => Units::dimension($opening, 'widthMm', $defaults[$type]['widthMm']),
                'height' => Units::dimension($opening, 'heightMm', $defaults[$type]['heightMm']),
                'qty' => $qty,
                'subtype' => isset($opening['subtype']) ? strtoupper((string) $opening['subtype']) : null,
                'installNewDoor' => $type === 'DOOR' && (bool) ($opening['installNewDoor'] ?? false),
            ];
        }

        return $openings;
    }

    private function openingArea(array $openings, string $type): float
    {
        $area = 0.0;

        foreach ($openings as $opening) {
            if ($opening['type'] === $type) {
                $area += $opening['width'] * $opening['height'] * $opening['qty'];
            }
        }

        return $area;
    }

    private function doorWidthSum(array $openings): float
    {
        $sum = 0.0;

        foreach ($openings as $opening) {
            if ($opening['type'] === 'DOOR') {
                $sum += $opening['width'] * $opening['qty'];
            }
        }

        return $sum;
    }

    /**
     * Zones are what the visitor actually answered. When a room only carries the
     * shorthand "wall finish = paint" the whole surface becomes one zone; when the
     * bathroom is half tile and half paint the frontend sends two.
     *
     * @return array<int, SurfaceZone>
     */
    private function buildZones(
        array $input,
        string $roomId,
        RoomType $roomType,
        ObjectCondition $condition,
        float $floorArea,
        float $ceilingArea,
        float $netWallArea,
        array &$warnings,
    ): array {
        $raw = $input['zones'] ?? null;

        if (! is_array($raw) || $raw === []) {
            $raw = $this->shorthandZones($input, $floorArea, $ceilingArea, $netWallArea);
        }

        $zones = [];
        $totals = [SurfaceType::Floor->value => 0.0, SurfaceType::Wall->value => 0.0, SurfaceType::Ceiling->value => 0.0];

        foreach ($raw as $zone) {
            $surface = SurfaceType::tryFrom(strtoupper((string) ($zone['surface'] ?? '')));

            if (! $surface) {
                continue;
            }

            $area = max(0.0, (float) ($zone['area'] ?? 0));

            if ($area <= 0) {
                continue;
            }

            $finish = FinishCategory::tryFrom(strtoupper((string) ($zone['finish'] ?? '')));

            // A finish belonging to another surface is a frontend bug, not a price:
            // drop it rather than billing a floor covering per wall square metre.
            if ($finish && $finish->surface() !== $surface) {
                $finish = null;
            }

            $state = SurfaceState::tryFrom(strtoupper((string) ($zone['currentState'] ?? '')))
                ?? $condition->defaultSurfaceState($surface);

            $totals[$surface->value] += $area;

            $zones[] = new SurfaceZone(
                roomId: $roomId,
                surface: $surface,
                area: $area,
                finish: $finish,
                currentState: $state,
                wetZone: (bool) ($zone['wetZone'] ?? match ($surface) {
                    SurfaceType::Floor => $roomType->isWet(),
                    SurfaceType::Wall => $roomType->hasWetWalls(),
                    SurfaceType::Ceiling => false,
                }),
                options: is_array($zone['options'] ?? null) ? $zone['options'] : [],
            );
        }

        $limits = [
            SurfaceType::Floor->value => $floorArea,
            SurfaceType::Wall->value => $netWallArea,
            SurfaceType::Ceiling->value => $ceilingArea,
        ];

        foreach ($limits as $surface => $limit) {
            if ($limit > 0 && $totals[$surface] > $limit + 0.01) {
                $warnings[] = 'ZONE_AREA_EXCEEDS_'.$surface;
            }
        }

        return $zones;
    }

    /** One zone per surface, from the room-level finish shorthand. */
    private function shorthandZones(array $input, float $floorArea, float $ceilingArea, float $netWallArea): array
    {
        $map = [
            ['key' => 'floorFinish', 'surface' => SurfaceType::Floor->value, 'area' => $floorArea],
            ['key' => 'wallFinish', 'surface' => SurfaceType::Wall->value, 'area' => $netWallArea],
            ['key' => 'ceilingFinish', 'surface' => SurfaceType::Ceiling->value, 'area' => $ceilingArea],
        ];

        $zones = [];

        foreach ($map as $entry) {
            $finish = $input[$entry['key']] ?? null;

            if ($finish === null || $entry['area'] <= 0) {
                continue;
            }

            $zones[] = [
                'surface' => $entry['surface'],
                'area' => $entry['area'],
                'finish' => $finish,
                'currentState' => $input['currentState'] ?? null,
                'options' => $input[$entry['key'].'Options'] ?? [],
            ];
        }

        return $zones;
    }

    /**
     * Point counts default from the room type and stay editable — the defaults keep
     * the form short, the edits keep the estimate honest (master spec §11.1, §20).
     */
    private function normalisePoints(array $input, RoomType $roomType): array
    {
        $points = $roomType->defaultPoints();

        foreach (is_array($input['points'] ?? null) ? $input['points'] : [] as $key => $value) {
            $points[$key] = max(0, (int) $value);
        }

        return $points;
    }

    /**
     * The derived quantity layer (master spec §6.3): the aggregated variables every
     * object-level work refers to. Room-level works read the room itself.
     *
     * @param  array<int, RoomGeometry>  $rooms
     * @return array<string, float>
     */
    public function deriveQuantities(array $rooms): array
    {
        $q = [
            'TOTAL_FLOOR_AREA' => 0.0,
            'TOTAL_CEILING_AREA' => 0.0,
            'TOTAL_NET_WALL_AREA' => 0.0,
            'TOTAL_GROSS_WALL_AREA' => 0.0,
            'TOTAL_PERIMETER' => 0.0,
            'TOTAL_SKIRTING_LENGTH' => 0.0,
            'WET_FLOOR_AREA' => 0.0,
            'WET_WALL_AREA' => 0.0,
            'WARM_FLOOR_AREA' => 0.0,
            'WARM_FLOOR_ELECTRIC_AREA' => 0.0,
            'WARM_FLOOR_WATER_AREA' => 0.0,
            'TILE_FLOOR_AREA' => 0.0,
            'TILE_WALL_AREA' => 0.0,
            'PAINT_WALL_AREA' => 0.0,
            'PAINT_CEILING_AREA' => 0.0,
            'DOOR_COUNT' => 0.0,
            'WINDOW_COUNT' => 0.0,
            'ROOM_COUNT' => (float) count($rooms),
        ];

        foreach ($rooms as $room) {
            $q['TOTAL_FLOOR_AREA'] += $room->floorArea;
            $q['TOTAL_CEILING_AREA'] += $room->ceilingArea;
            $q['TOTAL_NET_WALL_AREA'] += $room->netWallArea;
            $q['TOTAL_GROSS_WALL_AREA'] += $room->grossWallArea;
            $q['TOTAL_PERIMETER'] += $room->perimeter;
            $q['TOTAL_SKIRTING_LENGTH'] += $room->skirtingLength;
            $q['WARM_FLOOR_AREA'] += $room->warmFloorArea;

            if ($room->warmFloorType === WarmFloorType::Electric) {
                $q['WARM_FLOOR_ELECTRIC_AREA'] += $room->warmFloorArea;
            } elseif ($room->warmFloorType === WarmFloorType::Water) {
                $q['WARM_FLOOR_WATER_AREA'] += $room->warmFloorArea;
            }

            foreach ($room->openings as $opening) {
                $key = $opening['type'] === 'DOOR' ? 'DOOR_COUNT' : 'WINDOW_COUNT';
                $q[$key] += $opening['qty'];
            }

            foreach ($room->zones as $zone) {
                if ($zone->wetZone) {
                    if ($zone->surface === SurfaceType::Floor) {
                        $q['WET_FLOOR_AREA'] += $zone->area;
                    } elseif ($zone->surface === SurfaceType::Wall) {
                        $q['WET_WALL_AREA'] += $zone->area;
                    }
                }

                if (! $zone->finish) {
                    continue;
                }

                // Per-finish totals; the key is the finish itself so a new finish
                // never needs a new branch here.
                $key = $zone->finish->value.'_AREA';
                $q[$key] = ($q[$key] ?? 0.0) + $zone->area;

                match ($zone->finish) {
                    FinishCategory::FloorTile => $q['TILE_FLOOR_AREA'] += $zone->area,
                    FinishCategory::WallTile => $q['TILE_WALL_AREA'] += $zone->area,
                    FinishCategory::Paint => $q['PAINT_WALL_AREA'] += $zone->area,
                    FinishCategory::CeilingPaint => $q['PAINT_CEILING_AREA'] += $zone->area,
                    default => null,
                };
            }
        }

        $q['TILE_AREA'] = $q['TILE_FLOOR_AREA'] + $q['TILE_WALL_AREA'];

        return array_map(fn (float $v) => round($v, 4), $q);
    }
}
