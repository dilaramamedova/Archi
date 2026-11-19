<?php

/*
 * «Leyla Loft» — Archi's second acceptance object (TZ №2 «Ətraflı kalkulyator»,
 * 19.11.2026). 72.6 m² flat, shell («Təmirsiz (mayak)»), Standard, ceiling 3240 mm,
 * freight lift, no demolition, new wiring, no design project. Every ceiling is
 * painted concrete; the living room, bedroom, kitchen and corridor are lit by a
 * TRACK system. Rooms are rectangles of the project's areas (mm):
 *
 *   Living 29.9     6590 × 4539   parquet / decor plaster + 7.5 m² brick + 13 m² paint / paint
 *                                  window 2800×1900, balcony door 800×2300, passage 1800 to the kitchen
 *   Balcony 4.8     2970 × 1616   porcelain / paint / paint          glazing 2970×1900
 *   Bedroom 12.2    4535 × 2690   parquet / paint + 7.8 m² wallpaper / paint
 *                                  door 800×2300, window 1560×1900, balcony door 800×2300
 *   Wardrobe 2.4    2008 × 1195   parquet / paint / paint            door 800×2300
 *   Kitchen 10.6    3649 × 2905   porcelain / decor plaster + 3.2 m² tile apron / paint
 *                                  passage 1300 to the corridor, kitchen run 5300, warm floor 6.1 m²
 *   Corridor 8      5714 × 1400   parquet / decor plaster / paint    entrance door 900×2200
 *   Bathroom 3.9    2187 × 1783   porcelain / tile + 6 m² brick + 6 m² decor plaster / paint
 *                                  door 800×2300, warm floor 3 m²
 *
 * Brick is the WALL_STONE row; a balcony door is part of the window block (a
 * WINDOW opening: reveals, no interior door).
 *
 * `$detailed = true` adds the project's own point counts («Ətraflı»: ≈ 41 sockets,
 * 15 switches, 44 track lights on 20 m of rail, 3 pendants, 9 spots, 3 wall lights,
 * 1 TV, 2 data, 2 AC, 3 appliance, 1 intercom, 9 LED outputs; water 6, sewer 6);
 * otherwise every room is «Sadə» and the norms fill them in.
 */

return function (string $level = 'FULL', bool $detailed = false): array {
    $room = function (string $id, string $type, int $l, int $w, array $extra, array $points = [], array $fixtures = []) use ($detailed) {
        $room = array_merge([
            'id' => $id,
            'name' => $id,
            'roomType' => $type,
            'lengthMm' => $l,
            'widthMm' => $w,
            'heightMm' => 3240,
            'floorFinish' => 'PARQUET',
            'wallFinish' => 'PAINT',
            'ceilingFinish' => 'CEILING_PAINT',
            'openings' => [],
            'warmFloor' => ['type' => 'NONE'],
        ], $extra);

        if ($detailed) {
            $room['pointsTouched'] = true;
            $room['points'] = $points;
            $room['fixtures'] = $fixtures;
        }

        return $room;
    };

    $door = fn (int $w = 800, int $h = 2300, array $extra = []) => ['type' => 'DOOR', 'widthMm' => $w, 'heightMm' => $h, 'qty' => 1, 'installNewDoor' => true] + $extra;
    $window = fn (int $w, int $h) => ['type' => 'WINDOW', 'widthMm' => $w, 'heightMm' => $h, 'qty' => 1];
    $passage = fn (int $w) => ['type' => 'PASSAGE', 'widthMm' => $w, 'heightMm' => 2300, 'qty' => 1];

    return [
        'v' => 4,
        'object' => [
            'objectType' => 'APARTMENT',
            'area' => 72.6,
            'cityKey' => 'baku',
            'floor' => 7,
            'condition' => 'SHELL',
            'level' => $level,
            'ceilingHeightMm' => 3240,
            'hasFreightElevator' => true,
            'demolitionRequired' => false,
            'rewireElectrics' => true,
            'designProject' => false,
            'segment' => 'STANDARD',
            // The object follows the ceiling (painted → chandeliers); four rooms say TRACK.
            'lightingType' => 'AUTO',
        ],
        'rooms' => [
            $room('living', 'LIVING', 6590, 4539, [
                'lightingType' => 'TRACK',
                'wallFinish' => 'DECOR_PLASTER',
                'wallZones' => [['finish' => 'WALL_STONE', 'areaM2' => 7.5], ['finish' => 'PAINT', 'areaM2' => 13]],
                'openings' => [$window(2800, 1900), $window(800, 2300), $passage(1800)],
            ], ['sockets' => 15, 'switches' => 4, 'lights_track' => 22, 'track_rail_m' => 10, 'lights_pendant' => 1, 'wall_lights' => 1,
                'tv' => 1, 'data' => 1, 'ac_power' => 1, 'led_output' => 4]),

            $room('balcony', 'BALCONY', 2970, 1616, [
                'floorFinish' => 'FLOOR_TILE_LARGE',
                'openings' => [$window(2970, 1900)],
            ], ['sockets' => 1, 'switches' => 1, 'lights_spot' => 3]),

            $room('bedroom', 'BEDROOM', 4535, 2690, [
                'lightingType' => 'TRACK',
                'wallZones' => [['finish' => 'WALLPAPER', 'areaM2' => 7.8]],
                'openings' => [$door(), $window(1560, 1900), $window(800, 2300)],
            ], ['sockets' => 7, 'switches' => 3, 'lights_track' => 9, 'track_rail_m' => 4, 'wall_lights' => 2, 'data' => 1, 'ac_power' => 1, 'led_output' => 2]),

            $room('wardrobe', 'WARDROBE', 2008, 1195, [
                'openings' => [$door()],
            ], ['sockets' => 1, 'switches' => 1, 'lights_spot' => 2]),

            $room('kitchen', 'KITCHEN', 3649, 2905, [
                'lightingType' => 'TRACK',
                'floorFinish' => 'FLOOR_TILE_LARGE',
                'wallFinish' => 'DECOR_PLASTER',
                'wallZones' => [['finish' => 'WALL_TILE', 'areaM2' => 3.2]],
                'openings' => [$passage(1300)],
                'kitchenFurnitureMm' => 5300,
                'warmFloor' => ['type' => 'ELECTRIC', 'area' => 6.1],
            ], ['sockets' => 11, 'switches' => 2, 'lights_track' => 7, 'track_rail_m' => 3.5, 'lights_pendant' => 2, 'appliance' => 3, 'led_output' => 3],
                ['kitchen_sink' => 1, 'dishwasher' => 1, 'washer' => 1, 'mixer' => 1]),

            $room('corridor', 'CORRIDOR', 5714, 1400, [
                'lightingType' => 'TRACK',
                'wallFinish' => 'DECOR_PLASTER',
                'openings' => [$door(900, 2200, ['isEntrance' => true, 'installNewDoor' => false])],
            ], ['sockets' => 3, 'switches' => 3, 'lights_track' => 6, 'track_rail_m' => 2.5, 'intercom' => 1]),

            $room('bath', 'BATHROOM', 2187, 1783, [
                'floorFinish' => 'FLOOR_TILE_LARGE',
                'wallFinish' => 'WALL_TILE',
                'wallZones' => [['finish' => 'WALL_STONE', 'areaM2' => 6], ['finish' => 'DECOR_PLASTER', 'areaM2' => 6]],
                'openings' => [$door()],
                'warmFloor' => ['type' => 'ELECTRIC', 'area' => 3],
            ], ['sockets' => 3, 'switches' => 1, 'lights_spot' => 4],
                ['toilet' => 1, 'basin' => 1, 'shower' => 1, 'hygienic_shower' => 0, 'washer' => 0, 'towel_rail' => 1, 'mixer' => 2]),
        ],
        'works' => [],
        'materials' => [],
        'modules' => ['services' => ['finalCleaning' => true]],
        'basket' => ['useCart' => false, 'excluded' => []],
    ];
};
