<?php

namespace App\Enums\Calculator;

/**
 * How a room is lit (TZ 19.11.2026 №2, §43) — it decides which ceiling lights the
 * «Sadə» mode norms put in the room:
 *
 *   SPOT        one spot per SPOT_AREA_M2 (plasterboard / stretch ceilings)
 *   TRACK       a track system: one track light per TRACK_LIGHT_AREA_M2, a rail of
 *               TRACK_RAIL_M_PER_M2 per m² and a feed point per TRACK_RAIL_FEED_M of rail
 *   CHANDELIER  only the room type's chandeliers / pendants (a painted slab)
 *
 * Chandeliers, pendants and wall lights follow the room-type norms in every mode.
 *
 * A room that states no type follows the object's; an object that states none
 * (AUTO) follows the ceiling — SPOT under a suspended ceiling, CHANDELIER under a
 * painted one. That is exactly the behaviour before this field existed, so an old
 * estimate prices the same.
 */
enum LightingType: string
{
    case Spot = 'SPOT';
    case Track = 'TRACK';
    case Chandelier = 'CHANDELIER';

    /** The object-level «follow the ceiling» choice; never a room's effective type. */
    public const AUTO = 'AUTO';

    public static function fromAny(mixed $value): ?self
    {
        return is_string($value) ? self::tryFrom(strtoupper(trim($value))) : null;
    }

    /** The pre-2026-11-19 behaviour: spots under a suspended ceiling, otherwise none. */
    public static function forCeiling(bool $suspended): self
    {
        return $suspended ? self::Spot : self::Chandelier;
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.lighting.'.strtolower($this->value);
    }
}
