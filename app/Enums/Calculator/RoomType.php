<?php

namespace App\Enums\Calculator;

/**
 * Room types drive the editable default point sets of the electrical / plumbing
 * modules (master spec 8 and 11.1) and the wet-zone default.
 */
enum RoomType: string
{
    case Living = 'LIVING';
    case KitchenLiving = 'KITCHEN_LIVING';
    case Bedroom = 'BEDROOM';
    case Kitchen = 'KITCHEN';
    case Bathroom = 'BATHROOM';
    case Wc = 'WC';
    case Corridor = 'CORRIDOR';
    case Office = 'OFFICE';
    case Commercial = 'COMMERCIAL';
    case Other = 'OTHER';

    /** The room has a water line (TZ 17.11.2026 §9). */
    public function hasPlumbing(): bool
    {
        return in_array($this, [self::Bathroom, self::Wc, self::Kitchen, self::KitchenLiving], true);
    }

    /**
     * Waterproofing — the whole floor and every wall — belongs to the sanitary rooms
     * only (Archi's decision, TZ 17.11.2026 §9). A kitchen has plumbing but is not
     * tanked: that used to add ≈ 570 AZN of membrane to an ordinary kitchen.
     */
    public function needsWaterproofing(): bool
    {
        return in_array($this, [self::Bathroom, self::Wc], true);
    }

    /** @deprecated kept for old callers — a room with plumbing. */
    public function isWet(): bool
    {
        return $this->hasPlumbing();
    }

    /** @deprecated kept for old callers — a waterproofed room. */
    public function hasWetWalls(): bool
    {
        return $this->needsWaterproofing();
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
            self::KitchenLiving => ['sockets' => 12, 'switches' => 3, 'lights' => 2, 'tv' => 1, 'data' => 1, 'ac_power' => 1, 'appliance' => 3],
            self::Kitchen => ['sockets' => 10, 'switches' => 2, 'lights' => 3, 'appliance' => 3, 'water_points' => 2, 'sewer_points' => 2],
            self::Bathroom => ['sockets' => 2, 'switches' => 1, 'lights' => 2, 'wall_lights' => 1, 'water_points' => 4, 'sewer_points' => 3],
            self::Wc => ['sockets' => 1, 'switches' => 1, 'lights' => 1, 'water_points' => 2, 'sewer_points' => 2],
            self::Corridor => ['sockets' => 2, 'switches' => 2, 'lights' => 3],
            self::Office => ['sockets' => 8, 'switches' => 1, 'lights' => 4, 'data' => 2, 'ac_power' => 1],
            self::Commercial => ['sockets' => 6, 'switches' => 2, 'lights' => 6, 'data' => 2, 'ac_power' => 1],
            self::Other => ['sockets' => 4, 'switches' => 1, 'lights' => 1],
        };
    }

    /**
     * The sanitary fixtures a room of this type normally gets — editable on the room,
     * like the points. A fixture is installed and, in an old flat, the old one is
     * removed; both counts come from here, so neither can be forgotten.
     */
    public function defaultFixtures(): array
    {
        return match ($this) {
            self::Bathroom => ['toilet' => 1, 'basin' => 1, 'shower' => 1, 'bathtub' => 0, 'mixer' => 2],
            self::Wc => ['toilet' => 1, 'basin' => 1, 'mixer' => 1],
            self::Kitchen, self::KitchenLiving => ['kitchen_sink' => 1, 'mixer' => 1, 'dishwasher' => 1],
            default => [],
        };
    }
}
