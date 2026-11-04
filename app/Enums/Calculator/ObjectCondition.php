<?php

namespace App\Enums\Calculator;

/**
 * The object as a whole, answered once on the first step (master spec §7). It only
 * seeds the state of every surface and the demolition / rewiring defaults; a room
 * may still say otherwise.
 */
enum ObjectCondition: string
{
    /** Təmirsiz — mayak, qara döşəmə. */
    case Shell = 'SHELL';

    /** Qara təmir hazırdır — suvaq, stəjka var, üzlük yoxdur. */
    case Prepared = 'PREPARED';

    /** Köhnə təmir var — sökülüb yenidən edilir. */
    case OldRenovation = 'OLD_RENOVATION';

    private const LEGACY = [
        'PARTIAL' => self::Prepared,
        'READY_BASE' => self::Prepared,
        // The old new-build / second-hand question, when it was answered alone.
        'NEW_BUILD' => self::Shell,
        'SECONDARY' => self::OldRenovation,
    ];

    public static function fromAny(mixed $value): ?self
    {
        $value = strtoupper(trim((string) $value));

        return self::tryFrom($value) ?? self::LEGACY[$value] ?? null;
    }

    public function defaultSurfaceState(): SurfaceState
    {
        return match ($this) {
            self::Shell => SurfaceState::Bare,
            self::Prepared => SurfaceState::Prepared,
            self::OldRenovation => SurfaceState::Old,
        };
    }

    /** An old flat is stripped out; a bare or prepared one has nothing to demolish. */
    public function defaultDemolition(): bool
    {
        return $this === self::OldRenovation;
    }

    /** A bare shell has no wiring; an old flat's wiring is replaced; a prepared base already has it. */
    public function defaultRewiring(): bool
    {
        return $this !== self::Prepared;
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.conditions.'.strtolower($this->value);
    }
}
