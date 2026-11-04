<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\SurfaceState;
use App\Enums\Calculator\SurfaceType;

/**
 * One finished surface inside one room (master spec §6.2). The zone — not the room
 * — is what the dependency engine runs on and what a work line takes its quantity
 * from: a bathroom can still be tile up to a line and paint above it.
 */
class SurfaceZone
{
    public function __construct(
        public readonly string $roomId,
        public readonly SurfaceType $surface,
        public readonly float $area,
        public readonly ?Finish $finish,
        public readonly SurfaceState $currentState,
        public readonly bool $wetZone = false,
    ) {}

    public function toArray(): array
    {
        return [
            'roomId' => $this->roomId,
            'surface' => $this->surface->value,
            'area' => round($this->area, 4),
            'finish' => $this->finish?->finishId,
            'family' => $this->finish?->family->value,
            'currentState' => $this->currentState->value,
            'wetZone' => $this->wetZone,
        ];
    }
}
