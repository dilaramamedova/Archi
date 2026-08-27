<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\GeometryMode;
use App\Enums\Calculator\RoomType;
use App\Enums\Calculator\WarmFloorType;

/**
 * A room after the geometry engine has run (master spec §6). Everything except the
 * three entered dimensions is derived here, once, so no work formula ever recomputes
 * an area for itself.
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
        public readonly float $length,
        public readonly float $width,
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
            'length' => $this->length,
            'width' => $this->width,
            'height' => $this->height,
            'floorArea' => round($this->floorArea, 4),
            'ceilingArea' => round($this->ceilingArea, 4),
            'perimeter' => round($this->perimeter, 4),
            'grossWallArea' => round($this->grossWallArea, 4),
            'doorArea' => round($this->doorArea, 4),
            'windowArea' => round($this->windowArea, 4),
            'netWallArea' => round($this->netWallArea, 4),
            'skirtingLength' => round($this->skirtingLength, 4),
            'openings' => $this->openings,
            'zones' => array_map(fn (SurfaceZone $z) => $z->toArray(), $this->zones),
            'warmFloorType' => $this->warmFloorType->value,
            'warmFloorArea' => round($this->warmFloorArea, 4),
            'points' => $this->points,
            'wetZone' => $this->wetZone,
            'warnings' => $this->warnings,
        ];
    }
}
