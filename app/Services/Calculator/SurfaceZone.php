<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\FinishCategory;
use App\Enums\Calculator\SurfaceState;
use App\Enums\Calculator\SurfaceType;

/**
 * One finished surface inside one room (master spec §6.2). A room is not tied to a
 * single wall finish: a bathroom can be tile up to a line and paint above it, so the
 * zone — not the room — is what the dependency engine runs on and what a work line
 * takes its quantity from.
 */
class SurfaceZone
{
    public function __construct(
        public readonly string $roomId,
        public readonly SurfaceType $surface,
        public readonly float $area,
        public readonly ?FinishCategory $finish,
        public readonly SurfaceState $currentState,
        public readonly bool $wetZone = false,
        /** @var array<string, mixed> tile format, layout, layer counts, complexity */
        public readonly array $options = [],
    ) {}

    public function option(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    public function toArray(): array
    {
        return [
            'roomId' => $this->roomId,
            'surface' => $this->surface->value,
            'area' => round($this->area, 4),
            'finish' => $this->finish?->value,
            'currentState' => $this->currentState->value,
            'wetZone' => $this->wetZone,
            'options' => $this->options,
        ];
    }
}
