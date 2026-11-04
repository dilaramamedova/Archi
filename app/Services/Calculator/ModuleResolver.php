<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\WarmFloorType;

/**
 * Everything that is not a surface: electrics, plumbing, air conditioning, light
 * fittings, doors, warm floors, demolition extras and the additional services
 * (master spec §11).
 *
 * Nothing here has its own input block any more. The rooms already say how many
 * sockets, lights, water points, fixtures and air conditioners they have, and the
 * object step says whether the flat is stripped out and rewired — the engine derives
 * the rest (cable, pipes, the panel, old fixtures, debris) from those counts with
 * norms the admin controls. The rule from the specification still holds: object
 * area is never a quantity source for electrics or plumbing.
 */
class ModuleResolver
{
    /** Room point key => rough electrical installation, per point. */
    private const ELECTRICAL_POINTS = [
        'sockets' => 'ELEC_SOCKET_POINT',
        'switches' => 'ELEC_SWITCH_POINT',
        'lights' => 'ELEC_LIGHT_POINT',
        'wall_lights' => 'ELEC_WALL_LIGHT_POINT',
        'tv' => 'ELEC_TV_POINT',
        'data' => 'ELEC_DATA_POINT',
        'ac_power' => 'ELEC_AC_POINT',
        'appliance' => 'ELEC_APPLIANCE_POINT',
    ];

    /** Rough plumbing, per point. */
    private const PLUMBING_POINTS = [
        'water_points' => 'PLUMB_WATER_POINT',
        'sewer_points' => 'PLUMB_SEWER_POINT',
    ];

    /** Room fixture key => finish plumbing: every fixture is its own rate. */
    private const FIXTURES = [
        'toilet' => 'PLUMB_TOILET_INSTALL',
        'basin' => 'PLUMB_BASIN_INSTALL',
        'shower' => 'PLUMB_SHOWER_INSTALL',
        'bathtub' => 'PLUMB_BATHTUB_INSTALL',
        'kitchen_sink' => 'PLUMB_KITCHEN_SINK',
        'mixer' => 'PLUMB_MIXER_INSTALL',
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

        return array_merge(
            $this->electrical($rooms, $rewire),
            $this->plumbing($rooms),
            $this->airConditioning($rooms),
            $this->lighting($rooms),
            $this->warmFloor($rooms),
            $this->doors($rooms),
            $this->demolition($rooms, $object, $derived, $demolish, $rewire, $condition),
            $this->services($modules['services'] ?? [], $object, $derived),
        );
    }

    /**
     * The object's area, from one place: the stated area wins over the sum of the
     * rooms, because that is the figure the visitor answered and the figure the
     * AREA_MISMATCH warning is measured against.
     */
    private function objectArea(array $object, array $derived): float
    {
        return (float) ($object['area'] ?? 0) ?: (float) ($derived['TOTAL_FLOOR_AREA'] ?? 0);
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
     * One split unit per air-conditioner point: the point in the room is where the
     * unit hangs, so the count is already known.
     *
     * @return array<int, WorkRequest>
     */
    private function airConditioning(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            $units = (int) ($room->points['ac_power'] ?? 0);

            if ($units > 0) {
                $requests[] = new WorkRequest('HVAC_SPLIT_STD', $units, $room->id, $room->name);
            }
        }

        return $requests;
    }

    /**
     * A fitting hangs on every light point — ceiling and wall — so the fittings are
     * counted where the points are.
     *
     * @return array<int, WorkRequest>
     */
    private function lighting(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            $fittings = (int) ($room->points['lights'] ?? 0) + (int) ($room->points['wall_lights'] ?? 0);

            if ($fittings > 0) {
                $requests[] = new WorkRequest('LIGHT_FIXTURE_INSTALL', $fittings, $room->id, $room->name);
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
     * A door opening flagged installNewDoor produces its installation line
     * automatically (master spec audit §2.6).
     *
     * @return array<int, WorkRequest>
     */
    private function doors(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            $count = $room->newDoorCount();

            if ($count > 0) {
                $requests[] = new WorkRequest('DOOR_INSTALL_INTERIOR', $count, $room->id, $room->name);
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
     * counts and nothing has to be entered twice. A bare shell someone switches
     * demolition on in has none of them to remove. Debris follows the object area,
     * and the number of trips is always CEIL.
     *
     * @return array<int, WorkRequest>
     */
    private function demolition(array $rooms, array $object, array $derived, bool $demolish, bool $rewire, ObjectCondition $condition): array
    {
        if (! $demolish) {
            return [];
        }

        $requests = [];

        if ($condition === ObjectCondition::OldRenovation) {
            $doors = array_sum(array_map(fn (RoomGeometry $room) => $room->newDoorCount(), $rooms));

            if ($doors > 0) {
                $requests[] = new WorkRequest('DEM_DOOR', $doors);
            }

            $fixtures = 0;
            $points = 0;

            foreach ($rooms as $room) {
                $fixtures += array_sum(array_map('intval', $room->fixtures));

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
        }

        $volume = round($this->objectArea($object, $derived) * $this->config->float('DEBRIS_VOLUME_PER_M2', 0.05), 2);

        if ($volume > 0) {
            $requests[] = new WorkRequest('DEM_DEBRIS_LOADING', $volume);

            $capacity = max(0.1, $this->config->float('DEBRIS_VEHICLE_VOLUME_CAPACITY', 6));
            $requests[] = new WorkRequest(
                workId: 'DEM_DEBRIS_DISPOSAL',
                quantity: (float) ceil($volume / $capacity),
                meta: ['debrisVolume' => $volume, 'vehicleCapacity' => $capacity],
            );
        }

        return $requests;
    }

    /**
     * Additional services. The design project is deliberately outside the building
     * formulas (master spec §7) and is excluded from the contingency base by its
     * work rate flag.
     *
     * @return array<int, WorkRequest>
     */
    private function services(array $input, array $object, array $derived): array
    {
        $requests = [];
        $objectArea = $this->objectArea($object, $derived);

        if (($object['designProject'] ?? false) && $objectArea > 0) {
            $requests[] = new WorkRequest('SERVICE_DESIGN', $objectArea);
        }

        if (($input['finalCleaning'] ?? true) && $objectArea > 0) {
            $requests[] = new WorkRequest('LOG_FINAL_CLEANING', $objectArea);
        }

        return $requests;
    }
}
