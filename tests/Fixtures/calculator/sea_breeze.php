<?php

/*
 * «Sea Breeze» — Archi's acceptance object for the detailed calculator (TZ
 * «Texniki tapşırıq: Archi ətraflı kalkulyatorunun təkmilləşdirilməsi», 17.11.2026).
 *
 * 69 m² flat, 5 rooms, shell («Təmirsiz (mayak)»), Standard, ceiling 3200 mm, freight
 * lift, no demolition, new wiring, no design project. Rooms (mm):
 *
 *   Bedroom 12        3865 × 3105   laminate / paint / plasterboard   door 850×2100            warm 7 m²
 *   Bedroom 16        4820 × 3385   laminate / paint / plasterboard   door 800×2100, window 2400×2530   warm 8 m²
 *   Kitchen-living    6230 × 4475   laminate + 2 m² porcelain / paint / plasterboard   window 3700×2530, passage   warm 27 m²
 *   Bathroom          2353 × 2125   porcelain / porcelain / plasterboard   door 800×2100   warm 3 m²
 *   Corridor          2490 × 2410   laminate / paint / plasterboard   entrance door 1000×2270
 *
 * `$detailed = true` adds the project's own point counts («Ətraflı» mode); otherwise
 * every room is left in «Sadə» mode and the norms fill them in.
 */

return function (string $level = 'FULL', bool $detailed = false): array {
    $room = function (string $id, string $type, int $l, int $w, array $extra) use ($detailed) {
        $room = array_merge([
            'id' => $id,
            'name' => $id,
            'roomType' => $type,
            'lengthMm' => $l,
            'widthMm' => $w,
            'heightMm' => 3200,
            'floorFinish' => 'LAMINATE',
            'wallFinish' => 'PAINT',
            'ceilingFinish' => 'GYPSUM_FLAT',
            'openings' => [],
            'warmFloor' => ['type' => 'NONE'],
        ], $extra);

        if (! $detailed) {
            unset($room['points'], $room['pointsTouched']);
        }

        return $room;
    };

    return [
        'v' => 3,
        'object' => [
            'objectType' => 'APARTMENT',
            'area' => 69,
            'cityKey' => 'baku',
            'floor' => 5,
            'condition' => 'SHELL',
            'level' => $level,
            'ceilingHeightMm' => 3200,
            'hasFreightElevator' => true,
            'demolitionRequired' => false,
            'rewireElectrics' => true,
            'designProject' => false,
            'segment' => 'STANDARD',
        ],
        'rooms' => [
            $room('bed12', 'BEDROOM', 3865, 3105, [
                'openings' => [['type' => 'DOOR', 'widthMm' => 850, 'heightMm' => 2100, 'qty' => 1, 'installNewDoor' => true]],
                'warmFloor' => ['type' => 'ELECTRIC', 'area' => 7],
                'pointsTouched' => true,
                'points' => ['sockets' => 6, 'switches' => 2, 'lights_spot' => 5, 'lights_chandelier' => 1, 'wall_lights' => 2, 'data' => 1, 'ac_power' => 1],
            ]),
            $room('bed16', 'BEDROOM', 4820, 3385, [
                'openings' => [
                    ['type' => 'DOOR', 'widthMm' => 800, 'heightMm' => 2100, 'qty' => 1, 'installNewDoor' => true],
                    ['type' => 'WINDOW', 'widthMm' => 2400, 'heightMm' => 2530, 'qty' => 1],
                ],
                'warmFloor' => ['type' => 'ELECTRIC', 'area' => 8],
                'pointsTouched' => true,
                'points' => ['sockets' => 7, 'switches' => 2, 'lights_spot' => 6, 'lights_chandelier' => 1, 'wall_lights' => 2, 'tv' => 1, 'data' => 1, 'ac_power' => 1],
            ]),
            $room('kitchen', 'KITCHEN_LIVING', 6230, 4475, [
                'floorZones' => [['finish' => 'FLOOR_TILE_LARGE', 'areaM2' => 2]],
                'openings' => [
                    ['type' => 'WINDOW', 'widthMm' => 3700, 'heightMm' => 2530, 'qty' => 1],
                    ['type' => 'PASSAGE', 'widthMm' => 1200, 'heightMm' => 2300, 'qty' => 1],
                ],
                'kitchenFurnitureMm' => 3600,
                'warmFloor' => ['type' => 'ELECTRIC', 'area' => 27],
                'pointsTouched' => true,
                'points' => ['sockets' => 15, 'switches' => 3, 'lights_spot' => 11, 'lights_chandelier' => 1, 'lights_pendant' => 1, 'tv' => 1, 'data' => 1, 'ac_power' => 2, 'appliance' => 3],
            ]),
            $room('bath', 'BATHROOM', 2353, 2125, [
                'floorFinish' => 'FLOOR_TILE_LARGE',
                'wallFinish' => 'WALL_TILE_LARGE',
                'openings' => [['type' => 'DOOR', 'widthMm' => 800, 'heightMm' => 2100, 'qty' => 1, 'installNewDoor' => true]],
                'warmFloor' => ['type' => 'ELECTRIC', 'area' => 3],
                'pointsTouched' => true,
                'points' => ['sockets' => 2, 'switches' => 1, 'lights_spot' => 2],
            ]),
            $room('corridor', 'CORRIDOR', 2490, 2410, [
                'openings' => [['type' => 'DOOR', 'widthMm' => 1000, 'heightMm' => 2270, 'qty' => 1, 'isEntrance' => true, 'installNewDoor' => false]],
                'pointsTouched' => true,
                'points' => ['sockets' => 3, 'switches' => 3, 'lights_spot' => 3, 'intercom' => 1],
            ]),
        ],
        'works' => [],
        'materials' => [],
        'modules' => ['services' => ['finalCleaning' => true]],
        'basket' => ['useCart' => false, 'excluded' => []],
    ];
};
