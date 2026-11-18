<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\RoomType;

/**
 * A room type's «Sadə» mode norms (TZ 17.11.2026 §6, §34) — a calculator_room_types
 * row as the engine sees it, or the enum's built-in fallback when the table has no
 * row for the type.
 *
 * A norm is `base + per_m2 × floor area`, rounded UP — a room with 7.08 sockets'
 * worth of floor gets 8. The figure is rounded to 0.01 first so 3 + 0.25 × 12.0012
 * (floating point for "12 m²") stays 6 and does not become 7.
 */
class RoomTypeNorm
{
    /** Fixture => [water points, sewer points] (TZ §6). */
    public const FIXTURE_POINTS = [
        'toilet' => [1, 1],
        'basin' => [1, 1],
        'shower' => [1, 1],
        'bathtub' => [1, 1],
        'hygienic_shower' => [1, 0],
        'washer' => [1, 1],
        'kitchen_sink' => [1, 1],
        'dishwasher' => [1, 1],
    ];

    /** Every point key the form and the engine know, in display order. */
    public const POINT_KEYS = [
        'sockets', 'switches', 'lights_spot', 'lights_pendant', 'lights_chandelier', 'wall_lights',
        'tv', 'data', 'intercom', 'ac_power', 'appliance', 'water_points', 'sewer_points',
    ];

    /** Every fixture key the form and the engine know, in display order. */
    public const FIXTURE_KEYS = [
        'toilet', 'basin', 'shower', 'bathtub', 'hygienic_shower', 'washer', 'kitchen_sink',
        'dishwasher', 'mixer', 'towel_rail', 'boiler',
    ];

    /**
     * @param  array<string, string>  $names  locale => name
     * @param  array<string, array{base: float, per_m2: float}>  $pointNorms
     * @param  array<string, int>  $fixtures
     */
    public function __construct(
        public readonly string $roomType,
        public readonly array $names,
        public readonly bool $hasPlumbing,
        public readonly bool $needsWaterproofing,
        public readonly array $pointNorms,
        public readonly array $fixtures,
        public readonly ?float $acExtraAboveM2 = null,
        public readonly ?float $tvMinAreaM2 = null,
    ) {}

    public static function fromArray(array $row): self
    {
        return new self(
            roomType: $row['room_type'],
            names: $row['names'] ?? [],
            hasPlumbing: (bool) $row['has_plumbing'],
            needsWaterproofing: (bool) $row['needs_waterproofing'],
            pointNorms: collect($row['point_norms'] ?? [])
                ->map(fn ($n) => ['base' => (float) ($n['base'] ?? 0), 'per_m2' => (float) ($n['per_m2'] ?? 0)])
                ->all(),
            fixtures: array_map('intval', $row['fixtures'] ?? []),
            acExtraAboveM2: isset($row['ac_extra_above_m2']) ? (float) $row['ac_extra_above_m2'] : null,
            tvMinAreaM2: isset($row['tv_min_area_m2']) ? (float) $row['tv_min_area_m2'] : null,
        );
    }

    /** The enum's built-in defaults — used only when the admin table has no row. */
    public static function fromEnum(RoomType $type): self
    {
        $points = [];

        foreach ($type->defaultPoints() as $key => $count) {
            $points[$key === 'lights' ? 'lights_chandelier' : $key] = ['base' => (float) $count, 'per_m2' => 0.0];
        }

        return new self(
            roomType: $type->value,
            names: [],
            hasPlumbing: $type->hasPlumbing(),
            needsWaterproofing: $type->needsWaterproofing(),
            pointNorms: $points,
            fixtures: $type->defaultFixtures(),
        );
    }

    public function name(?string $locale = null): ?string
    {
        return Translated::pick($this->names, $locale);
    }

    /**
     * The «Sadə» counts for a room of this type and size.
     *
     * Spots are not a room-type norm: they follow the ceiling. A plasterboard or
     * stretch ceiling gets one spot per SPOT_AREA_M2, on top of the chandeliers and
     * pendants; a painted slab gets none. A room left with no ceiling light at all
     * (a bathroom under a painted slab) gets one, so no room is ever dark.
     *
     * @return array<string, int>
     */
    public function points(float $area, bool $suspendedCeiling, float $spotAreaM2): array
    {
        $points = [];

        foreach ($this->pointNorms as $key => $norm) {
            $points[$key] = (int) ceil(round($norm['base'] + $norm['per_m2'] * $area, 2));
        }

        if ($this->acExtraAboveM2 !== null && $area > $this->acExtraAboveM2 && ($points['ac_power'] ?? 0) > 0) {
            $points['ac_power']++;
        }

        if ($this->tvMinAreaM2 !== null && $area < $this->tvMinAreaM2) {
            $points['tv'] = 0;
        }

        $points['lights_spot'] = $suspendedCeiling && $spotAreaM2 > 0 && $area > 0
            ? (int) ceil(round($area / $spotAreaM2, 2))
            : 0;

        $ceilingLights = ($points['lights_spot'] ?? 0) + ($points['lights_chandelier'] ?? 0) + ($points['lights_pendant'] ?? 0);

        if ($ceilingLights === 0 && $area > 0) {
            $points['lights_chandelier'] = 1;
        }

        return array_filter($points, fn (int $v) => $v > 0);
    }

    /** The fixtures at their norm — only the ones the room has. */
    public function defaultFixtures(): array
    {
        return array_filter($this->fixtures, fn (int $v) => $v > 0);
    }

    /**
     * Water and sewer points from the fixtures (TZ §6): a toilet is one of each, a
     * hygienic shower only water, a mixer none — it sits on a fixture's point.
     *
     * @param  array<string, int>  $fixtures
     * @return array{0: int, 1: int}
     */
    public static function pointsFromFixtures(array $fixtures): array
    {
        $water = 0;
        $sewer = 0;

        foreach (self::FIXTURE_POINTS as $key => [$w, $s]) {
            $count = (int) ($fixtures[$key] ?? 0);
            $water += $count * $w;
            $sewer += $count * $s;
        }

        return [$water, $sewer];
    }

    /** Norms at 0 m² — what the config endpoint shows as the type's defaults. */
    public function toConfigArray(): array
    {
        return [
            'value' => $this->roomType,
            'isWet' => $this->hasPlumbing,
            'hasPlumbing' => $this->hasPlumbing,
            'hasWetWalls' => $this->needsWaterproofing,
            'needsWaterproofing' => $this->needsWaterproofing,
            'pointKeys' => self::POINT_KEYS,
            'fixtureKeys' => self::FIXTURE_KEYS,
            'defaultPoints' => $this->points(0.0, false, 0.0),
            'defaultFixtures' => $this->defaultFixtures(),
            'pointNorms' => $this->pointNorms,
            'acExtraAboveM2' => $this->acExtraAboveM2,
            'tvMinAreaM2' => $this->tvMinAreaM2,
        ];
    }
}
