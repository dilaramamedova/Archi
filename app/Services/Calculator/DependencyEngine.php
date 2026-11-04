<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\FinishFamily;
use App\Enums\Calculator\SurfaceState;
use App\Enums\Calculator\SurfaceType;

/**
 * "Current state → target finish" (master spec §9).
 *
 * The client says what the surface looks like now (bare / prepared / old finish)
 * and what they want on it; this class produces the technological chain — strip,
 * primer, plaster, putty, sanding, finishing primer, the finish itself, grout or
 * skirting — with the right quantity on each step.
 *
 * The chain is keyed by the finish's FAMILY, not by the finish: every coat-type
 * finish (paint, wallpaper, decorative plaster, whatever the admin adds next) needs
 * the same smooth base, every tile-type finish the same adhesive and grout. That is
 * what lets a finish be a database row instead of a branch in this file.
 *
 * The visitor may still delete a suggested step afterwards: that is a warning about
 * technological risk, never a hard block.
 */
class DependencyEngine
{
    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, RoomGeometry>  $rooms
     * @return array<int, WorkRequest>
     */
    public function suggest(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            $heightCoefficient = $this->config->heightCoefficientId($room->height);

            foreach ($room->zones as $zone) {
                foreach ($this->forZone($zone, $room, $heightCoefficient) as $request) {
                    $requests[] = $request;
                }
            }

            if ($sealant = $this->sealantStep($room)) {
                $requests[] = $sealant;
            }
        }

        return $requests;
    }

    /**
     * Silicone along the wet-room joints — once per room, the room's perimeter. The
     * sealed joint is a property of the room, not of a surface: a bathroom with a
     * tiled floor AND tiled walls is still one perimeter (master spec §6.3).
     */
    private function sealantStep(RoomGeometry $room): ?WorkRequest
    {
        $tiled = array_filter(
            $room->zones,
            fn (SurfaceZone $zone) => $zone->wetZone && $zone->finish?->family === FinishFamily::Tile,
        );

        if ($tiled === [] || $room->perimeter <= 0) {
            return null;
        }

        return new WorkRequest(
            workId: 'TILE_SILICONE',
            quantity: $room->perimeter,
            roomId: $room->id,
            roomName: $room->name,
            meta: ['source' => 'ROOM_PERIMETER'],
        );
    }

    /** @return array<int, WorkRequest> */
    private function forZone(SurfaceZone $zone, RoomGeometry $room, ?string $heightCoefficient): array
    {
        $finish = $zone->finish;

        if (! $finish) {
            return [];
        }

        $steps = array_merge(
            $this->preparationSteps($zone),
            [[
                'workId' => $finish->finishId,
                'quantity' => $zone->area,
                'meta' => ['finish' => $finish->finishId, 'family' => $finish->family->value, 'source' => 'FINISH'],
            ]],
            $this->followUpSteps($zone, $room),
        );

        $requests = [];

        foreach ($steps as $step) {
            $coefficients = [];

            // Wall and ceiling labour is what a tall room actually costs more of;
            // floor work is unaffected by ceiling height.
            if ($heightCoefficient && $zone->surface !== SurfaceType::Floor) {
                $coefficients[] = $heightCoefficient;
            }

            $requests[] = new WorkRequest(
                workId: $step['workId'],
                quantity: $step['quantity'],
                roomId: $room->id,
                roomName: $room->name,
                coefficientIds: $coefficients,
                suggested: true,
                meta: ($step['meta'] ?? []) + ['zone' => $zone->surface->value, 'finish' => $finish->finishId],
            );
        }

        return array_values(array_filter($requests, fn (WorkRequest $r) => $r->quantity > 0));
    }

    /**
     * What has to happen to the surface before the finish can go on it.
     *
     * @return array<int, array{workId: string, quantity: float}>
     */
    private function preparationSteps(SurfaceZone $zone): array
    {
        return match ($zone->surface) {
            SurfaceType::Wall => $this->wallPreparation($zone),
            SurfaceType::Floor => $this->floorPreparation($zone),
            SurfaceType::Ceiling => $this->ceilingPreparation($zone),
        };
    }

    private function wallPreparation(SurfaceZone $zone): array
    {
        $area = $zone->area;
        $family = $zone->finish->family;
        $steps = [];

        // Whatever is on the wall today comes off first. A wet room's walls are
        // assumed tiled — that is what a bathroom or WC wall carries in this market.
        if ($zone->currentState === SurfaceState::Old) {
            $steps[] = ['workId' => $zone->wetZone ? 'DEM_WALL_TILE' : 'DEM_WALL_FINISH', 'quantity' => $area];
        }

        // A bare wall is plastered whatever goes on it afterwards.
        if ($zone->currentState === SurfaceState::Bare) {
            $steps[] = ['workId' => 'WALL_PRIMER_BASE', 'quantity' => $area];
            $steps[] = ['workId' => 'WALL_PLASTER', 'quantity' => $area];
        }

        if ($family === FinishFamily::Coat) {
            // Paint, wallpaper and decorative plaster all want the same smooth base
            // and the same finishing primer.
            $steps[] = ['workId' => 'WALL_PUTTY', 'quantity' => $area];
            $steps[] = ['workId' => 'WALL_SANDING', 'quantity' => $area];
            $steps[] = ['workId' => 'WALL_PRIMER_FINISH', 'quantity' => $area];
        }

        if ($family === FinishFamily::Tile) {
            $steps[] = ['workId' => 'TILE_SURFACE_PREP', 'quantity' => $area];

            if ($zone->wetZone) {
                $steps[] = ['workId' => 'TILE_WATERPROOF_WALL', 'quantity' => $area];
            }
        }

        // Panels need nothing beyond a sound base.
        return $steps;
    }

    private function floorPreparation(SurfaceZone $zone): array
    {
        $area = $zone->area;
        $steps = [];

        // The old covering comes off. A wet room's floor is assumed tiled, a dry
        // room's a soft covering — the two removal rates differ.
        if ($zone->currentState === SurfaceState::Old) {
            $steps[] = ['workId' => $zone->wetZone ? 'DEM_FLOOR_TILE' : 'DEM_FLOOR_LAMINATE', 'quantity' => $area];
        }

        if ($zone->currentState === SurfaceState::Bare) {
            $steps[] = ['workId' => 'FLOOR_SCREED', 'quantity' => $area];
        }

        if ($zone->wetZone) {
            $steps[] = ['workId' => 'FLOOR_WATERPROOF', 'quantity' => $area];
        }

        if ($zone->finish->family === FinishFamily::Tile) {
            $steps[] = ['workId' => 'TILE_SURFACE_PREP', 'quantity' => $area];
        }

        return $steps;
    }

    private function ceilingPreparation(SurfaceZone $zone): array
    {
        $area = $zone->area;

        // A suspended or stretch ceiling hides the slab, so the smoothing chain is
        // pointless under it.
        if ($zone->finish->family === FinishFamily::Suspended) {
            return [];
        }

        $steps = [];

        if ($zone->currentState === SurfaceState::Bare) {
            $steps[] = ['workId' => 'CEIL_PRIMER_BASE', 'quantity' => $area];
            $steps[] = ['workId' => 'CEIL_PLASTER', 'quantity' => $area];
        }

        // An old painted ceiling is not stripped — it is filled and sanded over.
        $steps[] = ['workId' => 'CEIL_PUTTY', 'quantity' => $area];
        $steps[] = ['workId' => 'CEIL_SANDING', 'quantity' => $area];

        return $steps;
    }

    /**
     * What the finish needs after it is on: grout for tile, skirting for a floating
     * floor. The sealant is NOT here — it belongs to the room (sealantStep()).
     *
     * @return array<int, array{workId: string, quantity: float}>
     */
    private function followUpSteps(SurfaceZone $zone, RoomGeometry $room): array
    {
        return match ($zone->finish->family) {
            FinishFamily::Tile => [['workId' => 'TILE_GROUT', 'quantity' => $zone->area]],
            FinishFamily::Floating => [['workId' => 'SKIRTING_INSTALL', 'quantity' => $room->skirtingLength]],
            default => [],
        };
    }
}
