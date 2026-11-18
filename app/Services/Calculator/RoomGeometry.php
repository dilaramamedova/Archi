<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\GeometryMode;
use App\Enums\Calculator\RoomType;
use App\Enums\Calculator\WarmFloorType;

/**
 * A room after the geometry engine has run (master spec §6). Every quantity is
 * derived here, once, so no work formula ever recomputes an area for itself.
 *
 * Every figure on this object — and in toArray() — is METRES or m². Millimetres are
 * the unit of the request payload only; GeometryEngine converted them on the way in
 * (see Units). Nothing downstream of here ever sees a millimetre.
 */
class RoomGeometry
{
    /**
     * @param  array<int, array{type: string, width: float, height: float, qty: int, installNewDoor: bool, isEntrance: bool}>  $openings
     * @param  array<int, SurfaceZone>  $zones
     * @param  array<string, int>  $points  electrical / plumbing point counts — the EFFECTIVE ones
     * @param  array<string, int>  $fixtures  sanitary fixture counts — the EFFECTIVE ones
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly RoomType $roomType,
        public readonly GeometryMode $geometryMode,
        /** Metres — the ceiling-height coefficient bands are expressed in metres. */
        public readonly float $height,
        public readonly float $floorArea,
        public readonly float $ceilingArea,
        public readonly float $perimeter,
        public readonly float $grossWallArea,
        public readonly float $doorArea,
        public readonly float $windowArea,
        public readonly float $netWallArea,
        public readonly float $skirtingLength,
        public readonly array $openings,
        public readonly array $zones,
        public readonly WarmFloorType $warmFloorType,
        public readonly float $warmFloorArea,
        public readonly array $points,
        public readonly array $fixtures,
        /** The room has a water line (TZ 17.11.2026 §9). */
        public readonly bool $wetZone,
        /** @var array<int, string> non-blocking validation messages for this room */
        public readonly array $warnings = [],
        /** Floor and every wall are waterproofed — sanitary rooms only. */
        public readonly bool $needsWaterproofing = false,
        /** The counts above came from the room-type norms («Sadə» mode). */
        public readonly bool $pointsByNorm = false,
        /** Doorless openings (PASSAGE): subtracted from the walls and the skirting. */
        public readonly float $passageArea = 0.0,
        /** Kitchen furniture run, metres — subtracted from the skirting. */
        public readonly float $kitchenFurniture = 0.0,
    ) {}

    /** @return array<int, SurfaceZone> */
    public function zonesOf(string $surface): array
    {
        return array_values(array_filter($this->zones, fn (SurfaceZone $z) => $z->surface->value === $surface));
    }

    /** Every door opening, the entrance door included. */
    public function doorCount(): int
    {
        return $this->sumOpenings(fn (array $o) => $o['type'] === 'DOOR');
    }

    /** Door openings between rooms — the entrance door is not one (TZ §15). */
    public function interiorDoorCount(): int
    {
        return $this->sumOpenings(fn (array $o) => $o['type'] === 'DOOR' && ! $o['isEntrance']);
    }

    /**
     * Interior doors the visitor asked to have installed — master spec audit §2.6.
     * The entrance door is a different product and a different job.
     */
    public function newDoorCount(): int
    {
        return $this->sumOpenings(fn (array $o) => $o['type'] === 'DOOR' && $o['installNewDoor'] && ! $o['isEntrance']);
    }

    /** Window reveals: Σ (width + 2 × height) × qty, metres (TZ §14). */
    public function windowSlopesLength(): float
    {
        $length = 0.0;

        foreach ($this->openings as $o) {
            if ($o['type'] === 'WINDOW') {
                $length += ($o['width'] + 2 * $o['height']) * $o['qty'];
            }
        }

        return $length;
    }

    /** Window sills: Σ width × qty, metres (TZ §14). */
    public function windowSillLength(): float
    {
        $length = 0.0;

        foreach ($this->openings as $o) {
            if ($o['type'] === 'WINDOW') {
                $length += $o['width'] * $o['qty'];
            }
        }

        return $length;
    }

    private function sumOpenings(callable $filter): int
    {
        $count = 0;

        foreach ($this->openings as $o) {
            if ($filter($o)) {
                $count += $o['qty'];
            }
        }

        return $count;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'roomType' => $this->roomType->value,
            'geometryMode' => $this->geometryMode->value,
            // The entered length and width are NOT echoed here: they are the request's
            // own data, in the request's own unit. The height is, because it may have
            // come from the object's ceiling height rather than from the room.
            'heightMm' => round(Units::mToMm($this->height), 1),
            'floorArea' => round($this->floorArea, 4),
            'ceilingArea' => round($this->ceilingArea, 4),
            'perimeter' => round($this->perimeter, 4),
            'grossWallArea' => round($this->grossWallArea, 4),
            'doorArea' => round($this->doorArea, 4),
            'windowArea' => round($this->windowArea, 4),
            'passageArea' => round($this->passageArea, 4),
            'netWallArea' => round($this->netWallArea, 4),
            'skirtingLength' => round($this->skirtingLength, 4),
            // Millimetres, as they were entered — the one rule, everywhere.
            'openings' => array_map(fn (array $o) => [
                'type' => $o['type'],
                'widthMm' => round(Units::mToMm($o['width']), 1),
                'heightMm' => round(Units::mToMm($o['height']), 1),
                'qty' => $o['qty'],
                'installNewDoor' => $o['installNewDoor'],
                'isEntrance' => $o['isEntrance'],
            ], $this->openings),
            'kitchenFurnitureMm' => round(Units::mToMm($this->kitchenFurniture), 1),
            'zones' => array_map(fn (SurfaceZone $z) => $z->toArray(), $this->zones),
            'warmFloorType' => $this->warmFloorType->value,
            'warmFloorArea' => round($this->warmFloorArea, 4),
            'points' => $this->points,
            'fixtures' => $this->fixtures,
            'pointsByNorm' => $this->pointsByNorm,
            'wetZone' => $this->wetZone,
            'hasPlumbing' => $this->wetZone,
            'needsWaterproofing' => $this->needsWaterproofing,
            'warnings' => $this->warnings,
        ];
    }
}
