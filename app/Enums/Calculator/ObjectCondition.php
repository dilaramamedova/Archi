<?php

namespace App\Enums\Calculator;

/**
 * Object-level condition (master spec 7). It only seeds the default current state of
 * every new surface zone and the default of "demolition required" - it never applies
 * a price coefficient of its own.
 */
enum ObjectCondition: string
{
    case Shell = 'SHELL';
    case OldRenovation = 'OLD_RENOVATION';
    case Partial = 'PARTIAL';
    case ReadyBase = 'READY_BASE';

    public function defaultSurfaceState(SurfaceType $surface): SurfaceState
    {
        if ($surface === SurfaceType::Floor) {
            return $this === self::OldRenovation ? SurfaceState::OldFinish : SurfaceState::RoughFloor;
        }

        return match ($this) {
            self::Shell => SurfaceState::Shell,
            self::OldRenovation => SurfaceState::OldFinish,
            self::Partial => SurfaceState::PlasterReady,
            self::ReadyBase => SurfaceState::PaintReady,
        };
    }

    public function defaultDemolition(): bool
    {
        return $this === self::OldRenovation;
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.conditions.'.strtolower($this->value);
    }
}
