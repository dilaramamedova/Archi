<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\LightingType;
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
        'sockets', 'switches', 'lights_spot', 'lights_track', 'track_rail_m', 'lights_pendant', 'lights_chandelier',
        'wall_lights', 'led_output', 'tv', 'data', 'intercom', 'ac_power', 'appliance', 'water_points', 'sewer_points',
    ];

    /**
     * Point keys that are a length, not a count: the track rail, in running metres
     * to one decimal (TZ 19.11.2026 §44). Every other key is a whole number.
     */
    public const LENGTH_KEYS = ['track_rail_m'];

    /**
     * Never a «Sadə» norm: LED outputs exist only where the visitor counts them
     * («Ətraflı» mode, TZ 19.11.2026 §45).
     */
    public const DETAILED_ONLY_KEYS = ['led_output'];

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
     * The ceiling lights follow the room's lighting type (TZ 19.11.2026 §43):
     *
     *   SPOT        one spot per SPOT_AREA_M2, on top of the chandeliers and pendants
     *               (never fewer than the type's own spot norm — a wardrobe's);
     *   TRACK       one track light per TRACK_LIGHT_AREA_M2 and TRACK_RAIL_M_PER_M2
     *               metres of rail per m² (to 0.1 m); the feed points follow the rail
     *               (GeometryEngine) and count as electrical points;
     *   CHANDELIER  only the room type's own norms (a painted slab).
     *
     * A room left with no ceiling light at all (a bathroom under a painted slab) gets
     * one chandelier, so no room is ever dark. LED outputs are never a norm.
     *
     * `$lighting` also takes the pre-2026-11-19 bool «suspended ceiling», and
     * `$lightNorms` a bare SPOT_AREA_M2, so an older caller keeps its meaning.
     *
     * @param  LightingType|bool  $lighting
     * @param  array{spotAreaM2?: float, trackLightAreaM2?: float, trackRailMPerM2?: float}|float  $lightNorms
     * @return array<string, int|float>  whole counts; `track_rail_m` in metres
     */
    public function points(float $area, LightingType|bool $lighting, array|float $lightNorms = []): array
    {
        if (is_bool($lighting)) {
            $lighting = LightingType::forCeiling($lighting);
        }

        if (! is_array($lightNorms)) {
            $lightNorms = ['spotAreaM2' => (float) $lightNorms];
        }

        $points = [];

        foreach ($this->pointNorms as $key => $norm) {
            if (in_array($key, self::DETAILED_ONLY_KEYS, true)) {
                continue;
            }

            $points[$key] = (int) ceil(round($norm['base'] + $norm['per_m2'] * $area, 2));
        }

        if ($this->acExtraAboveM2 !== null && $area > $this->acExtraAboveM2 && ($points['ac_power'] ?? 0) > 0) {
            $points['ac_power']++;
        }

        if ($this->tvMinAreaM2 !== null && $area < $this->tvMinAreaM2) {
            $points['tv'] = 0;
        }

        $spotArea = (float) ($lightNorms['spotAreaM2'] ?? 0);
        $trackArea = (float) ($lightNorms['trackLightAreaM2'] ?? 0);
        $railPerM2 = (float) ($lightNorms['trackRailMPerM2'] ?? 0);

        if ($lighting === LightingType::Spot && $spotArea > 0 && $area > 0) {
            $points['lights_spot'] = max($points['lights_spot'] ?? 0, (int) ceil(round($area / $spotArea, 2)));
        }

        if ($lighting === LightingType::Track && $area > 0) {
            $points['lights_track'] = $trackArea > 0 ? (int) ceil(round($area / $trackArea, 2)) : 0;
            $points['track_rail_m'] = round($area * $railPerM2, 1);
        }

        $ceilingLights = ($points['lights_spot'] ?? 0) + ($points['lights_chandelier'] ?? 0)
            + ($points['lights_pendant'] ?? 0) + ($points['lights_track'] ?? 0);

        if ($ceilingLights === 0 && $area > 0) {
            $points['lights_chandelier'] = 1;
        }

        return array_filter($points, fn ($v) => $v > 0);
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
            'defaultPoints' => $this->points(0.0, LightingType::Chandelier),
            'lengthKeys' => self::LENGTH_KEYS,
            'defaultOpenings' => RoomType::tryFrom($this->roomType)?->defaultOpenings() ?? ['DOOR' => 1, 'WINDOW' => 1],
            'defaultFloorFamily' => RoomType::tryFrom($this->roomType)?->defaultFloorFamily(),
            'defaultFixtures' => $this->defaultFixtures(),
            'pointNorms' => $this->pointNorms,
            'acExtraAboveM2' => $this->acExtraAboveM2,
            'tvMinAreaM2' => $this->tvMinAreaM2,
        ];
    }
}
