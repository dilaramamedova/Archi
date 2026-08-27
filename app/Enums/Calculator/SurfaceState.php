<?php

namespace App\Enums\Calculator;

/**
 * Current state of a single surface zone - the left-hand side of the dependency
 * engine (master spec section 9). Per audit 2.1 this lives on the zone, not on the
 * object; the object-level condition only seeds the default.
 */
enum SurfaceState: string
{
    case Shell = 'SHELL';
    case PlasterReady = 'PLASTER_READY';
    case PaintReady = 'PAINT_READY';
    case OldFinish = 'OLD_FINISH';
    case OldTile = 'OLD_TILE';
    case RoughFloor = 'ROUGH_FLOOR';

    public function labelKey(): string
    {
        return 'calculator-detailed.states.'.strtolower($this->value);
    }
}
