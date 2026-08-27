<?php

namespace App\Enums\Calculator;

/**
 * Room types drive the editable default point sets of the electrical / plumbing
 * modules (master spec 8 and 11.1) and the wet-zone default.
 */
enum RoomType: string
{
    case Living = 'LIVING';
    case Bedroom = 'BEDROOM';
    case Kitchen = 'KITCHEN';
    case Bathroom = 'BATHROOM';
    case Wc = 'WC';
    case Corridor = 'CORRIDOR';
    case Office = 'OFFICE';
    case Commercial = 'COMMERCIAL';
    case Other = 'OTHER';

    /**
     * A wet room for the purposes of floor waterproofing. A kitchen counts: a leak
     * there reaches the neighbours below exactly like a bathroom leak does.
     */
    public function isWet(): bool
    {
        return in_array($this, [self::Bathroom, self::Wc, self::Kitchen], true);
    }

    /**
     * Walls are a narrower case. A bathroom is tanked wall-to-wall; a kitchen is not,
     * and defaulting its whole wall area to waterproofing added hundreds of manats of
     * membrane nobody asked for. The visitor can still mark a kitchen wall zone wet.
     */
    public function hasWetWalls(): bool
    {
        return in_array($this, [self::Bathroom, self::Wc], true);
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.room_types.'.strtolower($this->value);
    }

    /**
     * Editable defaults, never hard automation (master spec 20). The keys match the
     * electrical / plumbing module input names.
     */
    public function defaultPoints(): array
    {
        return match ($this) {
            self::Living => ['sockets' => 8, 'switches' => 2, 'lights' => 3, 'wall_lights' => 2, 'tv' => 1, 'data' => 1, 'ac_power' => 1],
            self::Bedroom => ['sockets' => 6, 'switches' => 2, 'lights' => 2, 'wall_lights' => 2, 'tv' => 1, 'data' => 0, 'ac_power' => 1],
            self::Kitchen => ['sockets' => 10, 'switches' => 2, 'lights' => 3, 'appliance' => 3, 'water_points' => 2, 'sewer_points' => 2],
            self::Bathroom => ['sockets' => 2, 'switches' => 1, 'lights' => 2, 'wall_lights' => 1, 'water_points' => 4, 'sewer_points' => 3],
            self::Wc => ['sockets' => 1, 'switches' => 1, 'lights' => 1, 'water_points' => 2, 'sewer_points' => 2],
            self::Corridor => ['sockets' => 2, 'switches' => 2, 'lights' => 3],
            self::Office => ['sockets' => 8, 'switches' => 1, 'lights' => 4, 'data' => 2, 'ac_power' => 1],
            self::Commercial => ['sockets' => 6, 'switches' => 2, 'lights' => 6, 'data' => 2, 'ac_power' => 1],
            self::Other => ['sockets' => 4, 'switches' => 1, 'lights' => 1],
        };
    }
}
