<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\FinishFamily;
use App\Enums\Calculator\GeometryMode;
use App\Enums\Calculator\LightingType;
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
 * impossible without them, and NetWallArea is what plaster, putty, sanding, primer
 * and every wall finish are all billed by.
 *
 * THIS CLASS IS THE UNIT BOUNDARY. Linear dimensions arrive in millimetres (keys
 * ending in `Mm`) and leave as metres; areas arrive and leave in m². Downstream —
 * the dependency engine, the modules, the materials, the tariffs — knows nothing but
 * metres. Every conversion goes through Units::mmToM(); see Units for the full rule.
 */
class GeometryEngine
{
    /**
     * The ceiling lights — one decision (TZ §11): a visitor who counts any of them has
     * counted all of them. Old payloads sent one `lights` count; it is the chandelier.
     */
    private const LIGHT_KEYS = ['lights_spot', 'lights_pendant', 'lights_chandelier', 'lights_track'];

    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, array<string, mixed>>  $rooms  raw room input
     * @param  array<string, mixed>  $object  the object step (ceiling height)
     * @return array<int, RoomGeometry>
     */
    public function buildRooms(array $rooms, ObjectCondition $condition, array $object = []): array
    {
        // The object's ceiling height is the default of every room that does not
        // state its own (TZ §4); the admin setting is the default of the object.
        $defaultHeightMm = (float) ($object['ceilingHeightMm'] ?? 0) > 0
            ? (float) $object['ceilingHeightMm']
            : $this->config->float('DEFAULT_ROOM_HEIGHT_MM', 2800);

        // The object's lighting type is the default of every room that does not state
        // its own (TZ 19.11.2026 §43, the same rule as the ceiling height); AUTO or
        // nothing means «follow the ceiling».
        $objectLighting = LightingType::fromAny($object['lightingType'] ?? null);

        $built = [];

        foreach (array_values($rooms) as $index => $room) {
            $built[] = $this->buildRoom(is_array($room) ? $room : [], $index, $condition, $defaultHeightMm, $objectLighting);
        }

        return $built;
    }

    private function buildRoom(array $input, int $index, ObjectCondition $condition, float $defaultHeightMm, ?LightingType $objectLighting = null): RoomGeometry
    {
        $warnings = [];

        $id = (string) ($input['id'] ?? 'room-'.($index + 1));
        $roomType = RoomType::tryFrom((string) ($input['roomType'] ?? '')) ?? RoomType::Other;
        $norm = $this->config->roomType($roomType);
        $name = trim((string) ($input['name'] ?? '')) ?: __($roomType->labelKey());
        $mode = GeometryMode::tryFrom((string) ($input['geometryMode'] ?? '')) ?? GeometryMode::Standard;

        // mm → m, once. Everything below this line is metres and m².
        $length = Units::dimension($input, 'lengthMm');
        $width = Units::dimension($input, 'widthMm');
        $height = Units::dimension($input, 'heightMm', $defaultHeightMm);

        $openings = $this->normaliseOpenings($input['openings'] ?? []);
        $doorArea = $this->openingArea($openings, 'DOOR');
        $windowArea = $this->openingArea($openings, 'WINDOW');
        $passageArea = $this->openingArea($openings, 'PASSAGE');
        $floorGapWidth = $this->openingWidthSum($openings, ['DOOR', 'PASSAGE']);

        // Kitchen furniture stands on the floor along the wall: no skirting behind it.
        $kitchenFurniture = in_array($roomType, [RoomType::Kitchen, RoomType::KitchenLiving], true)
            ? Units::dimension($input, 'kitchenFurnitureMm')
            : 0.0;

        if ($mode === GeometryMode::Manual) {
            // Complex shape: the visitor (or a sales manager) states the three
            // quantities directly rather than letting L×W lie about the room. The
            // two areas are m²; only the perimeter is a linear dimension, hence mm.
            $floorArea = max(0.0, (float) ($input['floorArea'] ?? 0));
            $perimeter = Units::dimension($input, 'perimeterMm');
            $grossWallArea = $perimeter * $height;
            $netWallArea = (float) ($input['netWallArea'] ?? max($grossWallArea - $doorArea - $windowArea - $passageArea, 0));
        } else {
            if ($length <= 0 || $width <= 0) {
                $warnings[] = 'ROOM_DIMENSIONS_REQUIRED';
            }

            $floorArea = $length * $width;
            $perimeter = 2 * ($length + $width);
            $grossWallArea = $perimeter * $height;
            $netWallArea = max($grossWallArea - $doorArea - $windowArea - $passageArea, 0.0);

            if ($grossWallArea > 0 && $doorArea + $windowArea + $passageArea > $grossWallArea) {
                $warnings[] = 'OPENINGS_EXCEED_WALL_AREA';
            }
        }

        $ceilingArea = $floorArea;
        $skirtingLength = max($perimeter - $floorGapWidth - $kitchenFurniture, 0.0);

        $zones = $this->buildZones($input, $id, $norm->needsWaterproofing, $condition, $floorArea, $ceilingArea, $netWallArea, $warnings);

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

        $suspended = (bool) array_filter(
            $zones,
            fn (SurfaceZone $z) => $z->surface === SurfaceType::Ceiling && $z->finish?->family === FinishFamily::Suspended,
        );
        // The room's own type; else the object's — unless the room explicitly says
        // AUTO («Tavana görə»); else the ceiling decides (TZ №2 §43).
        $roomChoice = $input['lightingType'] ?? null;
        $lighting = LightingType::fromAny($roomChoice)
            ?? (is_string($roomChoice) && strtoupper($roomChoice) === LightingType::AUTO ? null : $objectLighting)
            ?? LightingType::forCeiling($suspended);

        [$points, $fixtures, $byNorm] = $this->pointsAndFixtures($input, $norm, $floorArea, $lighting);

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
            points: $points,
            fixtures: $fixtures,
            wetZone: (bool) ($input['wetZone'] ?? $norm->hasPlumbing),
            warnings: $warnings,
            needsWaterproofing: $norm->needsWaterproofing,
            pointsByNorm: $byNorm,
            passageArea: $passageArea,
            kitchenFurniture: $kitchenFurniture,
            lightingType: $lighting,
        );
    }

    /**
     * «Sadə» or «Ətraflı» (TZ §6).
     *
     * A room the visitor never opened the counters of is filled from the room-type
     * norms — sockets per m², spots per m² of a plasterboard ceiling, water points per
     * fixture — and is recalculated whenever its size or ceiling changes. A room
     * whose points were edited (pointsTouched) keeps the visitor's numbers; a key it
     * does not send still comes from the norm, so one edited counter never zeroes
     * the rest.
     *
     * Old payloads carry no pointsTouched flag: a room that sends points counts as
     * edited, exactly as it did before the norms existed.
     *
     * The lighting type decides the norm's ceiling lights (TZ 19.11.2026 §43), and the
     * track rail decides its feed points in both modes: one per TRACK_RAIL_FEED_M of
     * rail, at least one wherever there are track lights (`track_feed`, an electrical
     * point like any other — the cable follows it).
     *
     * @return array{0: array<string, int|float>, 1: array<string, int>, 2: bool}
     */
    private function pointsAndFixtures(array $input, RoomTypeNorm $norm, float $floorArea, LightingType $lighting): array
    {
        $normPoints = $norm->points($floorArea, $lighting, [
            'spotAreaM2' => $this->config->float('SPOT_AREA_M2', 2.7),
            'trackLightAreaM2' => $this->config->float('TRACK_LIGHT_AREA_M2', 1.4),
            'trackRailMPerM2' => $this->config->float('TRACK_RAIL_M_PER_M2', 0.33),
        ]);
        $normFixtures = $norm->defaultFixtures();

        // Water and sewer points of the norm always follow the fixtures.
        unset($normPoints['water_points'], $normPoints['sewer_points']);

        $sentPoints = is_array($input['points'] ?? null) ? $input['points'] : [];
        // Derived, never typed: whatever a client sends here is recalculated.
        unset($sentPoints['track_feed']);

        $touched = array_key_exists('pointsTouched', $input)
            ? (bool) $input['pointsTouched']
            : $sentPoints !== [];

        if (! $touched) {
            return [$this->withTrackFeed($this->withPipePoints($normPoints, $normFixtures, [])), $normFixtures, true];
        }

        if (array_key_exists('lights', $sentPoints)) {
            $sentPoints['lights_chandelier'] = (int) ($sentPoints['lights_chandelier'] ?? 0) + (int) $sentPoints['lights'];
            unset($sentPoints['lights']);
        }

        // The ceiling lights are one decision: a visitor who says «5 spots» has not
        // also asked for the norm's chandelier on top.
        $defaults = $normPoints;

        if (array_intersect(self::LIGHT_KEYS, array_keys($sentPoints)) !== []) {
            foreach (self::LIGHT_KEYS as $key) {
                unset($defaults[$key]);
            }
        }

        $points = $this->counts($sentPoints, $defaults);
        $fixtures = $this->counts($input['fixtures'] ?? null, $normFixtures);

        // A rail without track lights is not a track system: the norm's rail goes
        // when the visitor's own light count has no track lights in it.
        if (! array_key_exists('track_rail_m', $sentPoints) && ($points['lights_track'] ?? 0) <= 0) {
            unset($points['track_rail_m']);
        }

        return [$this->withTrackFeed($this->withPipePoints($points, $fixtures, $sentPoints)), $fixtures, false];
    }

    /**
     * Track feed points: CEIL(rail / TRACK_RAIL_FEED_M), at least one wherever there
     * are track lights or a rail (TZ 19.11.2026 §43).
     *
     * @param  array<string, int|float>  $points
     * @return array<string, int|float>
     */
    private function withTrackFeed(array $points): array
    {
        unset($points['track_feed']);

        $rail = (float) ($points['track_rail_m'] ?? 0);
        $lights = (int) ($points['lights_track'] ?? 0);
        $perFeed = $this->config->float('TRACK_RAIL_FEED_M', 3);

        $feeds = $rail > 0 && $perFeed > 0 ? (int) ceil(round($rail / $perFeed, 2)) : 0;

        if ($feeds === 0 && ($lights > 0 || $rail > 0)) {
            $feeds = 1;
        }

        if ($feeds > 0) {
            $points['track_feed'] = $feeds;
        }

        return $points;
    }

    /**
     * Water and sewer points: the visitor's own count when they gave one, otherwise
     * one per fixture that needs it.
     *
     * @return array<string, int|float>
     */
    private function withPipePoints(array $points, array $fixtures, array $sent): array
    {
        [$water, $sewer] = RoomTypeNorm::pointsFromFixtures($fixtures);

        $points['water_points'] = array_key_exists('water_points', $sent) ? max(0, (int) $sent['water_points']) : $water;
        $points['sewer_points'] = array_key_exists('sewer_points', $sent) ? max(0, (int) $sent['sewer_points']) : $sewer;

        return array_filter($points, fn ($v) => $v > 0);
    }

    /**
     * Openings carry real dimensions with editable defaults, so the anketa stays
     * short without giving up the arithmetic (master spec §6).
     *
     * Input is `widthMm` / `heightMm`; the normalised opening is metres, like every
     * other figure the engine passes around. A PASSAGE is a doorless opening: it
     * takes wall and skirting away and never gets a door (TZ §12).
     */
    private function normaliseOpenings(mixed $raw): array
    {
        $door = [
            'widthMm' => $this->config->float('DEFAULT_DOOR_WIDTH_MM', 900),
            'heightMm' => $this->config->float('DEFAULT_DOOR_HEIGHT_MM', 2100),
        ];

        $defaults = [
            'DOOR' => $door,
            'PASSAGE' => $door,
            'WINDOW' => [
                'widthMm' => $this->config->float('DEFAULT_WINDOW_WIDTH_MM', 1500),
                'heightMm' => $this->config->float('DEFAULT_WINDOW_HEIGHT_MM', 1400),
            ],
        ];

        $openings = [];

        foreach (is_array($raw) ? $raw : [] as $opening) {
            if (! is_array($opening)) {
                continue;
            }

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
                'installNewDoor' => $type === 'DOOR' && (bool) ($opening['installNewDoor'] ?? false),
                'isEntrance' => $type === 'DOOR' && (bool) ($opening['isEntrance'] ?? false),
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

    /** @param array<int, string> $types */
    private function openingWidthSum(array $openings, array $types): float
    {
        $sum = 0.0;

        foreach ($openings as $opening) {
            if (in_array($opening['type'], $types, true)) {
                $sum += $opening['width'] * $opening['qty'];
            }
        }

        return $sum;
    }

    /**
     * Zones are what the visitor actually answered. When a room only carries the
     * shorthand "wall finish = PAINT" the whole surface becomes one zone; when the
     * bathroom is half tile and half paint the frontend sends two.
     *
     * The finish is looked up in calculator_finishes; an unknown id or a finish that
     * belongs to another surface is dropped rather than priced wrongly.
     *
     * Waterproofing is the sanitary rooms' alone — the whole floor and every wall,
     * whatever finish goes on top (TZ §9). A zone may still say otherwise itself.
     *
     * @return array<int, SurfaceZone>
     */
    private function buildZones(
        array $input,
        string $roomId,
        bool $waterproofed,
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
            if (! is_array($zone)) {
                continue;
            }

            $surface = SurfaceType::tryFrom(strtoupper((string) ($zone['surface'] ?? '')));

            if (! $surface) {
                continue;
            }

            $area = max(0.0, (float) ($zone['area'] ?? 0));

            if ($area <= 0) {
                continue;
            }

            $finish = $this->config->finish(isset($zone['finish']) ? strtoupper((string) $zone['finish']) : null);

            if ($finish && ($finish->surface !== $surface || ! $finish->family->allows($surface))) {
                $finish = null;
            }

            $state = SurfaceState::fromAny($zone['currentState'] ?? null) ?? $condition->defaultSurfaceState();

            $totals[$surface->value] += $area;

            $zones[] = new SurfaceZone(
                roomId: $roomId,
                surface: $surface,
                area: $area,
                finish: $finish,
                currentState: $state,
                wetZone: (bool) ($zone['wetZone'] ?? ($surface !== SurfaceType::Ceiling && $waterproofed)),
                isMain: (bool) ($zone['isMain'] ?? true),
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

    /**
     * Zones from the room-level shorthand: the main finish of each surface, plus the
     * extra finishes of floorZones[] / wallZones[] (TZ §10) — «28 m² = 26 m² laminate
     * + 2 m² porcelain». The main finish covers whatever the extras leave.
     */
    private function shorthandZones(array $input, float $floorArea, float $ceilingArea, float $netWallArea): array
    {
        $map = [
            ['key' => 'floorFinish', 'extra' => 'floorZones', 'surface' => SurfaceType::Floor->value, 'area' => $floorArea],
            ['key' => 'wallFinish', 'extra' => 'wallZones', 'surface' => SurfaceType::Wall->value, 'area' => $netWallArea],
            ['key' => 'ceilingFinish', 'extra' => null, 'surface' => SurfaceType::Ceiling->value, 'area' => $ceilingArea],
        ];

        $zones = [];

        foreach ($map as $entry) {
            if ($entry['area'] <= 0) {
                continue;
            }

            $extraArea = 0.0;

            foreach ($entry['extra'] && is_array($input[$entry['extra']] ?? null) ? $input[$entry['extra']] : [] as $extra) {
                $finish = is_array($extra) ? ($extra['finish'] ?? null) : null;
                $area = is_array($extra) ? max(0.0, (float) ($extra['areaM2'] ?? $extra['area'] ?? 0)) : 0.0;

                // An extra zone can never be larger than what is left of the surface.
                $area = min($area, max($entry['area'] - $extraArea, 0.0));

                if ($finish === null || $finish === '' || $area <= 0) {
                    continue;
                }

                $extraArea += $area;
                $zones[] = [
                    'surface' => $entry['surface'],
                    'area' => $area,
                    'finish' => $finish,
                    'currentState' => $input['currentState'] ?? null,
                    'isMain' => false,
                ];
            }

            $finish = $input[$entry['key']] ?? null;
            $mainArea = $entry['area'] - $extraArea;

            if ($finish === null || $finish === '' || $mainArea <= 0.0001) {
                continue;
            }

            $zones[] = [
                'surface' => $entry['surface'],
                'area' => $mainArea,
                'finish' => $finish,
                'currentState' => $input['currentState'] ?? null,
                'isMain' => true,
            ];
        }

        return $zones;
    }

    /**
     * Point and fixture counts stay editable — the defaults keep the form short, the
     * edits keep the estimate honest (master spec §11.1, §20). One edited key never
     * resets the others.
     *
     * A length key (the track rail, metres) keeps one decimal; every other key is a
     * whole count.
     *
     * @param  array<string, int|float>  $defaults
     * @return array<string, int|float>
     */
    private function counts(mixed $input, array $defaults): array
    {
        $counts = $defaults;

        foreach (is_array($input) ? $input : [] as $key => $value) {
            $key = (string) $key;
            $counts[$key] = in_array($key, RoomTypeNorm::LENGTH_KEYS, true)
                ? max(0.0, round(is_numeric($value) ? (float) $value : 0.0, 1))
                : max(0, (int) $value);
        }

        return $counts;
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
            'DOOR_COUNT' => 0.0,
            'INTERIOR_DOOR_COUNT' => 0.0,
            'WINDOW_COUNT' => 0.0,
            'PASSAGE_COUNT' => 0.0,
            'WINDOW_SLOPES_LENGTH' => 0.0,
            'WINDOW_SILL_LENGTH' => 0.0,
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
            $q['INTERIOR_DOOR_COUNT'] += $room->interiorDoorCount();
            $q['WINDOW_SLOPES_LENGTH'] += $room->windowSlopesLength();
            $q['WINDOW_SILL_LENGTH'] += $room->windowSillLength();

            if ($room->warmFloorType === WarmFloorType::Electric) {
                $q['WARM_FLOOR_ELECTRIC_AREA'] += $room->warmFloorArea;
            } elseif ($room->warmFloorType === WarmFloorType::Water) {
                $q['WARM_FLOOR_WATER_AREA'] += $room->warmFloorArea;
            }

            foreach ($room->openings as $opening) {
                $key = match ($opening['type']) {
                    'DOOR' => 'DOOR_COUNT',
                    'PASSAGE' => 'PASSAGE_COUNT',
                    default => 'WINDOW_COUNT',
                };
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

                // Per-finish totals; the key is the finish id itself so a finish the
                // admin adds tomorrow never needs a new branch here.
                $key = $zone->finish->finishId.'_AREA';
                $q[$key] = ($q[$key] ?? 0.0) + $zone->area;

                if ($zone->finish->family === FinishFamily::Tile) {
                    $q[$zone->surface === SurfaceType::Floor ? 'TILE_FLOOR_AREA' : 'TILE_WALL_AREA'] += $zone->area;
                }
            }
        }

        $q['TILE_AREA'] = $q['TILE_FLOOR_AREA'] + $q['TILE_WALL_AREA'];

        return array_map(fn (float $v) => round($v, 4), $q);
    }
}
