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
     * @param  array<int, array{type: string, width: float, height: float, qty: int, subtype: ?string, installNewDoor: bool}>  $openings
     * @param  array<int, SurfaceZone>  $zones
     * @param  array<string, int>  $points  electrical / plumbing point counts
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly RoomType $roomType,
        public readonly GeometryMode $geometryMode,
        /**
         * Metres. The only entered dimension anything still reads, because the
         * ceiling-height coefficient bands are expressed in metres. Length and width
         * used to sit beside it and were read by nothing at all — they had already
         * done their job inside floorArea and perimeter.
         */
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
        public readonly bool $wetZone,
        /** @var array<int, string> non-blocking validation messages for this room */
        public readonly array $warnings = [],
    ) {}

    /** @return array<int, SurfaceZone> */
    public function zonesOf(string $surface): array
    {
        return array_values(array_filter($this->zones, fn (SurfaceZone $z) => $z->surface->value === $surface));
    }

    public function doorCount(): int
    {
        return (int) array_sum(array_map(
            fn (array $o) => $o['type'] === 'DOOR' ? $o['qty'] : 0,
            $this->openings,
        ));
    }

    /** Doors the visitor asked to have installed — master spec audit §2.6. */
    public function newDoorCounts(): array
    {
        $counts = [];

        foreach ($this->openings as $opening) {
            if ($opening['type'] !== 'DOOR' || ! $opening['installNewDoor']) {
                continue;
            }

            $subtype = $opening['subtype'] ?: 'INTERIOR';
            $counts[$subtype] = ($counts[$subtype] ?? 0) + $opening['qty'];
        }

        return $counts;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'roomType' => $this->roomType->value,
            'geometryMode' => $this->geometryMode->value,
            // The entered length, width and height are NOT echoed here: they are the
            // request's own data, in the request's own unit, and repeating them in
            // metres next to the millimetres the visitor typed is how a reader ends
            // up believing the wrong one. What follows is derived — metres and m²,
            // the units these quantities are actually tariffed in.
            'floorArea' => round($this->floorArea, 4),
            'ceilingArea' => round($this->ceilingArea, 4),
            'perimeter' => round($this->perimeter, 4),
            'grossWallArea' => round($this->grossWallArea, 4),
            'doorArea' => round($this->doorArea, 4),
            'windowArea' => round($this->windowArea, 4),
            'netWallArea' => round($this->netWallArea, 4),
            'skirtingLength' => round($this->skirtingLength, 4),
            // Millimetres, as they were entered — the one rule, everywhere.
            'openings' => array_map(fn (array $o) => [
                'type' => $o['type'],
                'widthMm' => round(Units::mToMm($o['width']), 1),
                'heightMm' => round(Units::mToMm($o['height']), 1),
                'qty' => $o['qty'],
                'subtype' => $o['subtype'],
                'installNewDoor' => $o['installNewDoor'],
            ], $this->openings),
            'zones' => array_map(fn (SurfaceZone $z) => $z->toArray(), $this->zones),
            'warmFloorType' => $this->warmFloorType->value,
            'warmFloorArea' => round($this->warmFloorArea, 4),
            'points' => $this->points,
            'wetZone' => $this->wetZone,
            'warnings' => $this->warnings,
        ];
    }
}
