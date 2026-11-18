<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\WarmFloorType;

/**
 * Everything that is not a surface: electrics, plumbing, air conditioning, light
 * fittings, doors and windows, warm floors, demolition and waste, and the additional
 * services (master spec §11, TZ 17.11.2026).
 *
 * Nothing here has its own input block. The rooms already say how many sockets,
 * lights, water points, fixtures and air conditioners they have — the visitor's own
 * counts or the room-type norms — and the object step says whether the flat is
 * stripped out and rewired. The engine derives the rest (cable, pipes, the panel,
 * old fixtures, debris) from those counts with norms the admin controls. Object area
 * is never a quantity source for electrics or plumbing.
 */
class ModuleResolver
{
    /** Room point key => rough electrical installation, per point. */
    private const ELECTRICAL_POINTS = [
        'sockets' => 'ELEC_SOCKET_POINT',
        'switches' => 'ELEC_SWITCH_POINT',
        'lights_spot' => 'ELEC_LIGHT_POINT',
        'lights_pendant' => 'ELEC_LIGHT_POINT',
        'lights_chandelier' => 'ELEC_LIGHT_POINT',
        'wall_lights' => 'ELEC_WALL_LIGHT_POINT',
        'tv' => 'ELEC_TV_POINT',
        'data' => 'ELEC_DATA_POINT',
        'intercom' => 'ELEC_INTERCOM_POINT',
        'ac_power' => 'ELEC_AC_POINT',
        'appliance' => 'ELEC_APPLIANCE_POINT',
    ];

    /** Rough plumbing, per point. */
    private const PLUMBING_POINTS = [
        'water_points' => 'PLUMB_WATER_POINT',
        'sewer_points' => 'PLUMB_SEWER_POINT',
    ];

    /** Room fixture key => installation / connection: every fixture is its own rate. */
    private const FIXTURES = [
        'toilet' => 'PLUMB_TOILET_INSTALL',
        'basin' => 'PLUMB_BASIN_INSTALL',
        'shower' => 'PLUMB_SHOWER_INSTALL',
        'bathtub' => 'PLUMB_BATHTUB_INSTALL',
        'hygienic_shower' => 'PLUMB_HYGIENIC_SHOWER',
        'washer' => 'PLUMB_WASHER_CONNECT',
        'kitchen_sink' => 'PLUMB_KITCHEN_SINK',
        'dishwasher' => 'PLUMB_DISHWASHER_CONNECT',
        'mixer' => 'PLUMB_MIXER_INSTALL',
        'towel_rail' => 'PLUMB_TOWEL_RAIL',
        'boiler' => 'PLUMB_BOILER_INSTALL',
    ];

    /** Light kind => its own installation (TZ §17): a spot is not a chandelier. */
    private const LIGHT_FITTINGS = [
        'lights_spot' => 'LIGHT_SPOT_INSTALL',
        'lights_pendant' => 'LIGHT_PENDANT_INSTALL',
        'lights_chandelier' => 'LIGHT_CHANDELIER_INSTALL',
        'wall_lights' => 'LIGHT_WALL_INSTALL',
    ];

    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, RoomGeometry>  $rooms
     * @param  array<string, mixed>  $modules
     * @return array<int, WorkRequest>
     */
    public function resolve(array $rooms, array $modules, array $object, array $derived, ObjectCondition $condition): array
    {
        $rewire = (bool) ($object['rewireElectrics'] ?? $condition->defaultRewiring());
        $demolish = (bool) ($object['demolitionRequired'] ?? $condition->defaultDemolition());
        $area = self::calcArea($object, $derived);

        return array_merge(
            $this->electrical($rooms, $rewire),
            $this->plumbing($rooms),
            $this->airConditioning($rooms),
            $this->lighting($rooms),
            $this->warmFloor($rooms),
            $this->doorsAndWindows($rooms),
            $this->demolition($rooms, $demolish, $rewire, $condition),
            $this->waste($area, $demolish),
            $this->services($modules['services'] ?? [], $object, $area),
        );
    }

    /**
     * THE area of the estimate (TZ §5): the sum of the rooms. The figure typed on the
     * object step only stands in until there are rooms — cleaning, delivery, lifting,
     * the design project and the waste all follow the rooms, like the header does.
     */
    public static function calcArea(array $object, array $derived): float
    {
        $rooms = (float) ($derived['TOTAL_FLOOR_AREA'] ?? 0);

        return $rooms > 0 ? $rooms : max(0.0, (float) ($object['area'] ?? 0));
    }

    /** @return array<int, WorkRequest> */
    private function electrical(array $rooms, bool $rewire): array
    {
        $requests = [];
        $totalPoints = 0;

        foreach ($rooms as $room) {
            foreach (self::ELECTRICAL_POINTS as $key => $workId) {
                $count = (int) ($room->points[$key] ?? 0);

                if ($count > 0) {
                    $requests[] = new WorkRequest($workId, $count, $room->id, $room->name);
                    $totalPoints += $count;
                }
            }
        }

        // One thermostat per room that actually has a warm floor — the visitor never
        // has to count them, and they cannot be forgotten either.
        $thermostats = count(array_filter($rooms, fn (RoomGeometry $room) => $room->warmFloorType !== WarmFloorType::None));

        if ($thermostats > 0) {
            $requests[] = new WorkRequest('ELEC_FLOOR_HEAT_CONTROL', $thermostats);
        }

        // A rewired object needs a panel and the cable to every point; the cable
        // length is a norm per point, not a guess per m².
        if ($rewire && $totalPoints > 0) {
            $requests[] = new WorkRequest('ELEC_PANEL_SMALL', 1);
            $requests[] = new WorkRequest(
                workId: 'ELEC_CABLE',
                quantity: round($totalPoints * $this->config->float('CABLE_M_PER_POINT', 7), 2),
                meta: ['points' => $totalPoints],
            );
        }

        return $requests;
    }

    /** @return array<int, WorkRequest> */
    private function plumbing(array $rooms): array
    {
        $requests = [];
        $water = 0;
        $sewer = 0;

        foreach ($rooms as $room) {
            foreach (self::PLUMBING_POINTS as $key => $workId) {
                $count = (int) ($room->points[$key] ?? 0);

                if ($count > 0) {
                    $requests[] = new WorkRequest($workId, $count, $room->id, $room->name);
                }
            }

            $water += (int) ($room->points['water_points'] ?? 0);
            $sewer += (int) ($room->points['sewer_points'] ?? 0);

            foreach (self::FIXTURES as $key => $workId) {
                $count = (int) ($room->fixtures[$key] ?? 0);

                if ($count > 0) {
                    $requests[] = new WorkRequest($workId, $count, $room->id, $room->name);
                }
            }
        }

        // Pipe runs follow the points, with a norm per point the admin controls.
        $perPoint = $this->config->float('PIPE_M_PER_POINT', 3);

        if ($water > 0) {
            $requests[] = new WorkRequest('PLUMB_PIPE_WATER', round($water * $perPoint, 2), meta: ['points' => $water]);
        }

        if ($sewer > 0) {
            $requests[] = new WorkRequest('PLUMB_PIPE_SEWER', round($sewer * $perPoint, 2), meta: ['points' => $sewer]);
        }

        return $requests;
    }

    /**
     * One split unit per air-conditioner point. The outlets — refrigerant line and
     * condensate drain — are White Box; hanging the unit is the finishing stage
     * (TZ §17).
     *
     * @return array<int, WorkRequest>
     */
    private function airConditioning(array $rooms): array
    {
        $requests = [];
        $route = $this->config->float('AC_ROUTE_M_PER_UNIT', 4);
        $drain = $this->config->float('AC_DRAIN_M_PER_UNIT', 4);

        foreach ($rooms as $room) {
            $units = (int) ($room->points['ac_power'] ?? 0);

            if ($units <= 0) {
                continue;
            }

            if ($route > 0) {
                $requests[] = new WorkRequest('HVAC_SPLIT_EXTRA_ROUTE', round($units * $route, 2), $room->id, $room->name, meta: ['units' => $units]);
            }

            if ($drain > 0) {
                $requests[] = new WorkRequest('HVAC_SPLIT_DRAIN', round($units * $drain, 2), $room->id, $room->name, meta: ['units' => $units]);
            }

            $requests[] = new WorkRequest('HVAC_SPLIT_STD', $units, $room->id, $room->name);
        }

        return $requests;
    }

    /**
     * A fitting hangs on every light point, and each kind has its own installation
     * rate and its own material.
     *
     * @return array<int, WorkRequest>
     */
    private function lighting(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            foreach (self::LIGHT_FITTINGS as $key => $workId) {
                $count = (int) ($room->points[$key] ?? 0);

                if ($count > 0) {
                    $requests[] = new WorkRequest($workId, $count, $room->id, $room->name);
                }
            }
        }

        return $requests;
    }

    /** @return array<int, WorkRequest> */
    private function warmFloor(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            $workId = $room->warmFloorType->workId();

            if ($workId && $room->warmFloorArea > 0) {
                $requests[] = new WorkRequest($workId, $room->warmFloorArea, $room->id, $room->name);
            }
        }

        return $requests;
    }

    /**
     * Doors and windows (TZ §14, §15):
     *   - every interior door opening is prepared (White Box); the entrance door is not;
     *   - a door flagged installNewDoor is installed (finishing stage);
     *   - window reveals Σ (w + 2h), window sills Σ w.
     *
     * @return array<int, WorkRequest>
     */
    private function doorsAndWindows(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            if (($openings = $room->interiorDoorCount()) > 0) {
                $requests[] = new WorkRequest('DOOR_OPENING_PREP', $openings, $room->id, $room->name);
            }

            if (($doors = $room->newDoorCount()) > 0) {
                $requests[] = new WorkRequest('DOOR_INSTALL_INTERIOR', $doors, $room->id, $room->name);
            }

            if (($slopes = round($room->windowSlopesLength(), 2)) > 0) {
                $requests[] = new WorkRequest('WINDOW_SLOPES', $slopes, $room->id, $room->name);
            }

            if (($sills = round($room->windowSillLength(), 2)) > 0) {
                $requests[] = new WorkRequest('WINDOW_SILL', $sills, $room->id, $room->name);
            }
        }

        return $requests;
    }

    /**
     * Stripping out an old flat (master spec §10.1). The switch on the object step
     * governs this whole block; what a chosen finish strips from its own surface is
     * the dependency engine's business and is not governed by it.
     *
     * Old doors, old fixtures and old points exist only in a flat with an old
     * renovation, and they are the ones being replaced — so their counts are the new
     * counts and nothing has to be entered twice.
     *
     * @return array<int, WorkRequest>
     */
    private function demolition(array $rooms, bool $demolish, bool $rewire, ObjectCondition $condition): array
    {
        if (! $demolish || $condition !== ObjectCondition::OldRenovation) {
            return [];
        }

        $requests = [];
        $doors = array_sum(array_map(fn (RoomGeometry $room) => $room->newDoorCount(), $rooms));

        if ($doors > 0) {
            $requests[] = new WorkRequest('DEM_DOOR', $doors);
        }

        $fixtures = 0;
        $points = 0;

        foreach ($rooms as $room) {
            foreach (self::FIXTURES as $key => $workId) {
                $fixtures += (int) ($room->fixtures[$key] ?? 0);
            }

            foreach (array_keys(self::ELECTRICAL_POINTS) as $key) {
                $points += (int) ($room->points[$key] ?? 0);
            }
        }

        if ($fixtures > 0) {
            $requests[] = new WorkRequest('DEM_PLUMB_FIXTURE', $fixtures);
        }

        if ($rewire && $points > 0) {
            $requests[] = new WorkRequest('DEM_ELECTRIC_POINT', $points);
        }

        return $requests;
    }

    /**
     * Construction waste is carried out of every job, not only after demolition
     * (TZ §16): area × CONSTRUCTION_WASTE_M3_PER_M2, plus the demolition debris when
     * the flat is stripped out. Trips are CEIL(volume / vehicle).
     *
     * @return array<int, WorkRequest>
     */
    private function waste(float $area, bool $demolish): array
    {
        $construction = $area * $this->config->float('CONSTRUCTION_WASTE_M3_PER_M2', 0.05);
        $debris = $demolish ? $area * $this->config->float('DEBRIS_VOLUME_PER_M2', 0.05) : 0.0;
        $volume = round($construction + $debris, 2);

        if ($volume <= 0) {
            return [];
        }

        $capacity = max(0.1, $this->config->float('DEBRIS_VEHICLE_VOLUME_CAPACITY', 6));

        return [
            new WorkRequest('DEM_DEBRIS_LOADING', $volume, meta: [
                'constructionVolume' => round($construction, 2),
                'demolitionVolume' => round($debris, 2),
            ]),
            new WorkRequest(
                workId: 'DEM_DEBRIS_DISPOSAL',
                quantity: (float) ceil(round($volume / $capacity, 6)),
                meta: ['debrisVolume' => $volume, 'vehicleCapacity' => $capacity],
            ),
        ];
    }

    /**
     * Additional services. The design project is deliberately outside the building
     * stages (TZ §2) and is excluded from the contingency base by its rate flag.
     *
     * @return array<int, WorkRequest>
     */
    private function services(array $input, array $object, float $area): array
    {
        $requests = [];

        if (($object['designProject'] ?? false) && $area > 0) {
            $requests[] = new WorkRequest('SERVICE_DESIGN', $area);
        }

        if (($input['finalCleaning'] ?? true) && $area > 0) {
            $requests[] = new WorkRequest('LOG_FINAL_CLEANING', $area);
        }

        return $requests;
    }
}
