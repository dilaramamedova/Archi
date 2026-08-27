<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\HvacSystemType;
use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\WarmFloorType;

/**
 * The engineering modules (master spec §11) plus the item-count works that are not
 * derived from a surface: electrics, plumbing, HVAC, doors, lighting, partitions,
 * demolition extras and the additional services.
 *
 * The rule this class exists to enforce: object area is NOT a quantity source for
 * electrics or plumbing. "80 m² × a rate" would be a made-up number — the sum comes
 * from points, lines, panels and fixtures, each with its own rate.
 */
class ModuleResolver
{
    /** Point input key => work id (rough electrical installation, per point). */
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

    /** Rough plumbing, per point. Finish fixture installation is a separate group. */
    private const PLUMBING_POINTS = [
        'water_points' => 'PLUMB_WATER_POINT',
        'sewer_points' => 'PLUMB_SEWER_POINT',
    ];

    /** Object-level electrical items, entered as counts. */
    private const ELECTRICAL_ITEMS = [
        'dedicatedLines' => 'ELEC_DEDICATED_LINE',
        'cableMeters' => 'ELEC_CABLE',
        'conduitMeters' => 'ELEC_CONDUIT',
        'smallPanels' => 'ELEC_PANEL_SMALL',
        'largePanels' => 'ELEC_PANEL_LARGE',
        'breakers' => 'ELEC_BREAKER',
        'rcds' => 'ELEC_RCD',
    ];

    /** Finish plumbing: every fixture is its own rate, never a share of a lump sum. */
    private const PLUMBING_FIXTURES = [
        'toilets' => 'PLUMB_TOILET_INSTALL',
        'concealedCisterns' => 'PLUMB_CONCEALED_CISTERN',
        'basins' => 'PLUMB_BASIN_INSTALL',
        'kitchenSinks' => 'PLUMB_KITCHEN_SINK',
        'showers' => 'PLUMB_SHOWER_INSTALL',
        'bathtubs' => 'PLUMB_BATHTUB_INSTALL',
        'bidets' => 'PLUMB_BIDET_INSTALL',
        'hygienicShowers' => 'PLUMB_HYGIENIC_SHOWER',
        'mixers' => 'PLUMB_MIXER_INSTALL',
        'boilers' => 'PLUMB_BOILER_INSTALL',
        'washers' => 'PLUMB_WASHER_CONNECT',
        'dishwashers' => 'PLUMB_DISHWASHER_CONNECT',
        'towelRails' => 'PLUMB_TOWEL_RAIL',
        'manifolds' => 'PLUMB_MANIFOLD',
        'waterPipeMeters' => 'PLUMB_PIPE_WATER',
        'sewerPipeMeters' => 'PLUMB_PIPE_SEWER',
    ];

    private const LIGHTING_ITEMS = [
        'spots' => 'LIGHT_SPOT_INSTALL',
        'pendants' => 'LIGHT_PENDANT_INSTALL',
        'chandeliers' => 'LIGHT_CHANDELIER_INSTALL',
        'ledStripMeters' => 'LIGHT_LED_STRIP_INSTALL',
        'ledProfileMeters' => 'LIGHT_LED_PROFILE_INSTALL',
    ];

    private const VENTILATION_ITEMS = [
        'ductMeters' => 'VENT_DUCT',
        'diffusers' => 'VENT_DIFFUSER',
        'grilles' => 'VENT_GRILLE',
        'fans' => 'VENT_FAN',
        'coreDrills' => 'VENT_CORE_DRILL',
    ];

    private const DOOR_WORKS = [
        'INTERIOR' => 'DOOR_INSTALL_INTERIOR',
        'HIDDEN' => 'DOOR_INSTALL_HIDDEN',
        'SLIDING' => 'DOOR_INSTALL_SLIDING',
    ];

    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, RoomGeometry>  $rooms
     * @param  array<string, mixed>  $modules
     * @return array<int, WorkRequest>
     */
    public function resolve(array $rooms, array $modules, array $object, array $derived): array
    {
        return array_merge(
            $this->electrical($rooms, $modules['electrical'] ?? []),
            $this->plumbing($rooms, $modules['plumbing'] ?? []),
            $this->hvac($modules['hvac'] ?? [], $derived),
            $this->warmFloor($rooms),
            $this->doors($rooms),
            $this->itemModule($modules['lighting'] ?? [], self::LIGHTING_ITEMS),
            $this->itemModule($modules['ventilation'] ?? [], self::VENTILATION_ITEMS),
            $this->partitions($modules['partitions'] ?? []),
            $this->demolition($modules['demolition'] ?? [], $rooms, $object),
            $this->services($modules['services'] ?? [], $object, $derived),
        );
    }

    /** @return array<int, WorkRequest> */
    private function electrical(array $rooms, array $input): array
    {
        if (($input['enabled'] ?? true) === false) {
            return [];
        }

        $requests = [];

        foreach ($rooms as $room) {
            foreach (self::ELECTRICAL_POINTS as $key => $workId) {
                $count = (int) ($room->points[$key] ?? 0);

                if ($count > 0) {
                    $requests[] = new WorkRequest($workId, $count, $room->id, $room->name);
                }
            }
        }

        foreach (self::ELECTRICAL_ITEMS as $key => $workId) {
            $qty = (float) ($input[$key] ?? 0);

            if ($qty > 0) {
                $requests[] = new WorkRequest($workId, $qty);
            }
        }

        // One thermostat per room that actually has a warm floor — the visitor never
        // has to count them, and they cannot be forgotten either.
        $thermostats = (int) ($input['thermostats'] ?? count(array_filter(
            $rooms,
            fn (RoomGeometry $room) => $room->warmFloorType !== WarmFloorType::None,
        )));

        if ($thermostats > 0) {
            $requests[] = new WorkRequest('ELEC_FLOOR_HEAT_CONTROL', $thermostats);
        }

        return $requests;
    }

    /** @return array<int, WorkRequest> */
    private function plumbing(array $rooms, array $input): array
    {
        if (($input['enabled'] ?? true) === false) {
            return [];
        }

        $requests = [];

        foreach ($rooms as $room) {
            foreach (self::PLUMBING_POINTS as $key => $workId) {
                $count = (int) ($room->points[$key] ?? 0);

                if ($count > 0) {
                    $requests[] = new WorkRequest($workId, $count, $room->id, $room->name);
                }
            }
        }

        foreach (self::PLUMBING_FIXTURES as $key => $workId) {
            $qty = (float) ($input[$key] ?? 0);

            if ($qty > 0) {
                $requests[] = new WorkRequest($workId, $qty);
            }
        }

        return $requests;
    }

    /**
     * Master spec §11.3. Split and multi-split are counted; ducted, VRF and central
     * systems have no project yet, so they are area-based and explicitly ESTIMATED —
     * an honest range beats false precision.
     *
     * @return array<int, WorkRequest>
     */
    private function hvac(array $input, array $derived): array
    {
        $type = HvacSystemType::tryFrom(strtoupper((string) ($input['systemType'] ?? 'NONE'))) ?? HvacSystemType::None;

        if ($type === HvacSystemType::None) {
            return [];
        }

        $conditionedArea = (float) ($input['conditionedArea'] ?? $derived['TOTAL_FLOOR_AREA'] ?? 0);
        $requests = [];

        if ($type === HvacSystemType::Split) {
            $units = (int) ($input['indoorUnits'] ?? 0);

            if ($units > 0) {
                $requests[] = new WorkRequest('HVAC_SPLIT_STD', $units);
            }

            // Only the metres beyond the included standard route are billed.
            $included = $this->config->float('SPLIT_INCLUDED_ROUTE_METERS', 3) * $units;
            $extra = max(0.0, (float) ($input['routeMeters'] ?? 0) - $included);

            if (($input['extraRouteMeters'] ?? null) !== null) {
                $extra = max(0.0, (float) $input['extraRouteMeters']);
            }

            if ($extra > 0) {
                $requests[] = new WorkRequest('HVAC_SPLIT_EXTRA_ROUTE', $extra);
            }

            if (($drain = (float) ($input['drainMeters'] ?? 0)) > 0) {
                $requests[] = new WorkRequest('HVAC_SPLIT_DRAIN', $drain);
            }

            if (($pumps = (int) ($input['condensatePumps'] ?? 0)) > 0) {
                $requests[] = new WorkRequest('HVAC_CONDENSATE_PUMP', $pumps);
            }

            return $requests;
        }

        if ($type === HvacSystemType::MultiSplit) {
            foreach ([
                'outdoorUnits' => 'HVAC_MULTISPLIT_OUTDOOR',
                'indoorUnits' => 'HVAC_MULTISPLIT_INDOOR',
                'routeMeters' => 'HVAC_MULTISPLIT_ROUTE',
            ] as $key => $workId) {
                $qty = (float) ($input[$key] ?? 0);

                if ($qty > 0) {
                    $requests[] = new WorkRequest($workId, $qty);
                }
            }

            return $requests;
        }

        if ($conditionedArea <= 0) {
            return [];
        }

        $coefficients = [];

        if ((int) ($input['zones'] ?? 0) > 1) {
            $coefficients[] = 'K_HVAC_ZONE';
        }

        if ($input['complex'] ?? false) {
            $coefficients[] = 'K_HVAC_COMPLEX';
        }

        $workId = match ($type) {
            HvacSystemType::Ducted => 'HVAC_DUCTED_ESTIMATE',
            HvacSystemType::Vrf => 'HVAC_VRF_ESTIMATE',
            default => 'HVAC_CENTRAL_ESTIMATE',
        };

        return [new WorkRequest(
            workId: $workId,
            quantity: $conditionedArea,
            coefficientIds: $coefficients,
            precisionClass: PrecisionClass::Estimated,
            meta: ['systemType' => $type->value],
        )];
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
     * Master spec audit §2.6: a door opening flagged installNewDoor produces its
     * installation line automatically, so the dimensions are never entered twice and
     * an opening can never exist without the work that fills it.
     *
     * @return array<int, WorkRequest>
     */
    private function doors(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            foreach ($room->newDoorCounts() as $subtype => $count) {
                $workId = self::DOOR_WORKS[$subtype] ?? self::DOOR_WORKS['INTERIOR'];
                $requests[] = new WorkRequest($workId, $count, $room->id, $room->name);
            }
        }

        return $requests;
    }

    /** @return array<int, WorkRequest> */
    private function itemModule(array $input, array $map): array
    {
        $requests = [];

        foreach ($map as $key => $workId) {
            $qty = (float) ($input[$key] ?? 0);

            if ($qty > 0) {
                $requests[] = new WorkRequest($workId, $qty);
            }
        }

        return $requests;
    }

    /** @return array<int, WorkRequest> */
    private function partitions(array $input): array
    {
        $requests = [];

        foreach ([
            'blockArea' => 'WALL_PARTITION_BLOCK',
            'gypsumArea' => 'WALL_PARTITION_GYPSUM',
        ] as $key => $workId) {
            $area = (float) ($input[$key] ?? 0);

            if ($area > 0) {
                $requests[] = new WorkRequest($workId, $area);
            }
        }

        return $requests;
    }

    /**
     * The demolition detail block (master spec §10.1) plus debris handling. Debris
     * volume is estimated from the demolished area when the visitor does not enter
     * it, and the number of trips is always CEIL — half a lorry does not exist.
     *
     * @return array<int, WorkRequest>
     */
    private function demolition(array $input, array $rooms, array $object): array
    {
        // "Demontaj lazımdır" on the object step is not an estimate line of its own —
        // it is the switch that opens this block (master spec §10.1). With it off, no
        // partition, ceiling, fixture or debris line is billed. The surface stripping
        // that a chosen finish requires — old wallpaper before paint, for instance —
        // is not governed by it: that belongs to the wall's own technological chain.
        $condition = ObjectCondition::tryFrom(strtoupper((string) ($object['condition'] ?? '')));
        $required = (bool) ($object['demolitionRequired'] ?? $condition?->defaultDemolition() ?? false);

        if (! $required) {
            return [];
        }

        $requests = [];

        foreach ([
            'wallTileArea' => 'DEM_WALL_TILE',
            'wallPlasterArea' => 'DEM_WALL_PLASTER',
            'masonryPartitionArea' => 'DEM_PARTITION_MASONRY',
            'gypsumPartitionArea' => 'DEM_PARTITION_GYPSUM',
            'ceilingGypsumArea' => 'DEM_CEILING_GYPSUM',
            'ceilingStretchArea' => 'DEM_CEILING_STRETCH',
            'doors' => 'DEM_DOOR',
            'plumbingFixtures' => 'DEM_PLUMB_FIXTURE',
            'electricPoints' => 'DEM_ELECTRIC_POINT',
        ] as $key => $workId) {
            $qty = (float) ($input[$key] ?? 0);

            if ($qty > 0) {
                $requests[] = new WorkRequest($workId, $qty);
            }
        }

        $volume = (float) ($input['debrisVolume'] ?? 0);

        if ($volume <= 0 && ($input['estimateDebris'] ?? true)) {
            $floorArea = array_sum(array_map(fn (RoomGeometry $r) => $r->floorArea, $rooms));
            $volume = $floorArea * $this->config->float('DEBRIS_VOLUME_PER_M2', 0.05);
        }

        if ($volume > 0) {
            $requests[] = new WorkRequest('DEM_DEBRIS_LOADING', round($volume, 2));

            $capacity = max(0.1, $this->config->float('DEBRIS_VEHICLE_VOLUME_CAPACITY', 6));
            $requests[] = new WorkRequest(
                workId: 'DEM_DEBRIS_DISPOSAL',
                quantity: (float) ceil($volume / $capacity),
                meta: ['debrisVolume' => round($volume, 2), 'vehicleCapacity' => $capacity],
            );
        }

        return $requests;
    }

    /**
     * Additional services. The design project is deliberately outside the building
     * formulas (master spec §7) and, like the other external services, is excluded
     * from the contingency base by its work rate flag.
     *
     * @return array<int, WorkRequest>
     */
    private function services(array $input, array $object, array $derived): array
    {
        $requests = [];
        $objectArea = (float) ($object['area'] ?? 0) ?: (float) ($derived['TOTAL_FLOOR_AREA'] ?? 0);

        if (($object['designProject'] ?? false) && $objectArea > 0) {
            $requests[] = new WorkRequest('SERVICE_DESIGN', $objectArea);
        }

        if (($input['finalCleaning'] ?? true) && $objectArea > 0) {
            $requests[] = new WorkRequest('LOG_FINAL_CLEANING', $objectArea);
        }

        if (($protected = (float) ($input['protectedArea'] ?? 0)) > 0) {
            $requests[] = new WorkRequest('LOG_SITE_PROTECTION', $protected);
        }

        foreach ([
            'techSupervisionMonths' => 'SERVICE_TECH_SUPERVISION',
            'authorSupervisionMonths' => 'SERVICE_AUTHOR_SUPERVISION',
        ] as $key => $workId) {
            $months = (float) ($input[$key] ?? 0);

            if ($months > 0) {
                $requests[] = new WorkRequest($workId, $months);
            }
        }

        return $requests;
    }
}
