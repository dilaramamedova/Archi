<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\PrecisionClass;

/**
 * A resolved "this work, this much of it, here" request, before a price is attached.
 *
 * Separating resolution from pricing is what keeps the engine to one formula: the
 * dependency engine and the modules only ever produce these, and a single pass turns
 * them into EstimateLine objects.
 */
class WorkRequest
{
    /** @param array<int, string> $coefficientIds */
    public function __construct(
        public readonly string $workId,
        public readonly float $quantity,
        public readonly ?string $roomId = null,
        public readonly ?string $roomName = null,
        public readonly array $coefficientIds = [],
        public readonly bool $suggested = true,
        public readonly ?PrecisionClass $precisionClass = null,
        public readonly array $meta = [],
    ) {}

    /** Consolidation key — the same work in the same room is one line (master spec §19). */
    public function key(): string
    {
        return $this->workId.'|'.($this->roomId ?? '-').'|'.implode(',', $this->coefficientIds);
    }

    public function withQuantity(float $quantity): self
    {
        return new self(
            $this->workId, $quantity, $this->roomId, $this->roomName,
            $this->coefficientIds, $this->suggested, $this->precisionClass, $this->meta,
        );
    }
}
