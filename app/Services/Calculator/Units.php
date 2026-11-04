<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\SurfaceState;

/**
 * The unit boundary of the calculator — and the only place in the codebase that
 * divides a millimetre by a thousand — plus the payload version and its upgrades.
 *
 * THE RULE
 * --------
 * The visitor types millimetres, because that is how architects and builders in this
 * market specify a room: 4500, not 4.5. The engine prices in metres, because every
 * work tariff in `calculator_work_rates` is per m² or per running metre and every
 * material norm is per m². So the payload carries millimetres and the engine
 * converts once, here:
 *
 *   - every LINEAR dimension in the payload is an integer in millimetres and its key
 *     ends in `Mm` — `lengthMm`, `widthMm`, `heightMm`, an opening's `widthMm` and
 *     `heightMm`, manual geometry's `perimeterMm`;
 *   - every AREA stays in m² and keeps its plain name — `object.area`,
 *     `floorArea`, `netWallArea`, `warmFloor.area`.
 *
 * The `Mm` suffix is what makes a metre-era payload *detectable* instead of silently
 * misread; upgradePayload() converts those old payloads exactly once, on read.
 */
final class Units
{
    /**
     * Shape version of the `inputs` payload stored on calculator_sessions and in the
     * browser's localStorage.
     *
     *   1 — room and opening dimensions in METRES (`length`, `width`, `height`)
     *   2 — room and opening dimensions in MILLIMETRES (`lengthMm`, `widthMm`, …)
     *   3 — finishes are calculator_finishes rows; three surface states and three
     *       object conditions; materials and works keyed by finish id; the
     *       engineering modules derived from the rooms
     */
    public const PAYLOAD_VERSION = 3;

    /** v2 material category → v3 finish id (the finish now carries its own material). */
    private const LEGACY_MATERIALS = [
        'MAT_FLOOR_LAMINATE' => 'LAMINATE',
        'MAT_FLOOR_SPC' => 'SPC',
        'MAT_FLOOR_PARQUET' => 'PARQUET',
        'MAT_FLOOR_CARPET' => 'CARPET',
        'MAT_FLOOR_TILE' => 'FLOOR_TILE',
        'MAT_FLOOR_STONE' => 'FLOOR_STONE',
        'MAT_WALL_PAINT' => 'PAINT',
        'MAT_WALL_WALLPAPER' => 'WALLPAPER',
        'MAT_WALL_DECOR_PLASTER' => 'DECOR_PLASTER',
        'MAT_WALL_TILE' => 'WALL_TILE',
        'MAT_WALL_PANEL' => 'WALL_PANEL',
        'MAT_WALL_STONE' => 'WALL_STONE',
        'MAT_CEIL_PAINT' => 'CEILING_PAINT',
        'MAT_CEIL_GYPSUM' => 'GYPSUM_FLAT',
        'MAT_CEIL_STRETCH' => 'STRETCH_CEILING',
    ];

    /** v2 finish-installation work id → v3 finish id (the works map is keyed by it). */
    private const LEGACY_WORKS = [
        'FLOOR_LAMINATE_INSTALL' => 'LAMINATE',
        'FLOOR_SPC_INSTALL' => 'SPC',
        'FLOOR_PARQUET_INSTALL' => 'PARQUET',
        'FLOOR_TILE_INSTALL' => 'FLOOR_TILE',
        'FLOOR_STONE_INSTALL' => 'FLOOR_STONE',
        'TILE_INSTALL_STANDARD' => 'WALL_TILE',
        'TILE_INSTALL_LARGE' => 'WALL_TILE_LARGE',
        'TILE_INSTALL_MOSAIC' => 'MOSAIC',
        'WALL_PAINT' => 'PAINT',
        'WALL_WALLPAPER' => 'WALLPAPER',
        'WALL_DECOR_PLASTER' => 'DECOR_PLASTER',
        'WALL_PANEL_INSTALL' => 'WALL_PANEL',
        'WALL_STONE_INSTALL' => 'WALL_STONE',
        'CEIL_PAINT' => 'CEILING_PAINT',
        'CEIL_GYPSUM_FLAT' => 'GYPSUM_FLAT',
        'CEIL_GYPSUM_COMPLEX' => 'GYPSUM_COMPLEX',
        'CEIL_STRETCH' => 'STRETCH_CEILING',
    ];

    /** Millimetres to metres. The one division by 1000. */
    public static function mmToM(float $mm): float
    {
        return $mm / 1000;
    }

    /** Metres to millimetres — used only to upgrade a stored v1 payload. */
    public static function mToMm(float $m): float
    {
        return $m * 1000;
    }

    /**
     * Read a millimetre dimension out of a raw payload node, in metres.
     *
     * `$default` is in millimetres, because every default in `calculator_settings`
     * is stored in millimetres too (DEFAULT_ROOM_HEIGHT_MM and friends).
     */
    public static function dimension(array $node, string $key, float $defaultMm = 0.0): float
    {
        $mm = array_key_exists($key, $node) && is_numeric($node[$key])
            ? (float) $node[$key]
            : $defaultMm;

        return max(0.0, self::mmToM($mm));
    }

    /**
     * Bring a stored payload up to PAYLOAD_VERSION, one version at a time and keyed
     * off the version stamp — never by guessing from the shape of the data.
     *
     * Snapshots are not touched: they are frozen records and are rendered from their
     * stored lines and totals, never recalculated (master spec §22).
     *
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    public static function upgradePayload(array $inputs): array
    {
        $version = (int) ($inputs['v'] ?? 1);

        if ($version < 2) {
            $inputs = self::toMillimetres($inputs);
        }

        if ($version < 3) {
            $inputs = self::toFinishRows($inputs);
        }

        $inputs['v'] = self::PAYLOAD_VERSION;

        return $inputs;
    }

    /** v1 → v2: metres become millimetres. */
    private static function toMillimetres(array $inputs): array
    {
        foreach (is_array($inputs['rooms'] ?? null) ? $inputs['rooms'] : [] as $index => $room) {
            if (is_array($room)) {
                $inputs['rooms'][$index] = self::upgradeRoomUnits($room);
            }
        }

        return $inputs;
    }

    /**
     * v2 → v3: the six surface states and four object conditions fold into three
     * each, and the material / works maps are re-keyed by finish id. The finish ids
     * themselves did not change (LAMINATE, PAINT, …), so the rooms' finish fields
     * need nothing.
     */
    private static function toFinishRows(array $inputs): array
    {
        $object = is_array($inputs['object'] ?? null) ? $inputs['object'] : [];

        // The old new-build / second-hand question seeded the condition; keep that
        // answer when it was the only one given.
        $condition = ObjectCondition::fromAny($object['condition'] ?? null)
            ?? ObjectCondition::fromAny($object['marketType'] ?? null);

        if ($condition) {
            $object['condition'] = $condition->value;
        }

        unset($object['marketType'], $object['totalFloors']);
        $inputs['object'] = $object;

        foreach (is_array($inputs['rooms'] ?? null) ? $inputs['rooms'] : [] as $index => $room) {
            if (! is_array($room)) {
                continue;
            }

            if (array_key_exists('currentState', $room) && $room['currentState'] !== null) {
                $room['currentState'] = SurfaceState::fromAny($room['currentState'])?->value;
            }

            foreach (is_array($room['zones'] ?? null) ? $room['zones'] : [] as $z => $zone) {
                if (is_array($zone) && isset($zone['currentState'])) {
                    $room['zones'][$z]['currentState'] = SurfaceState::fromAny($zone['currentState'])?->value;
                }
            }

            $inputs['rooms'][$index] = $room;
        }

        $inputs['materials'] = self::rekey(is_array($inputs['materials'] ?? null) ? $inputs['materials'] : [], self::LEGACY_MATERIALS);
        $inputs['works'] = self::rekey(is_array($inputs['works'] ?? null) ? $inputs['works'] : [], self::LEGACY_WORKS);

        // The HVAC, lighting, demolition and logistics blocks are derived from the
        // rooms now; only the services switch survives.
        $modules = is_array($inputs['modules'] ?? null) ? $inputs['modules'] : [];
        $inputs['modules'] = array_intersect_key($modules, ['services' => true]);

        return $inputs;
    }

    /** @param array<string, string> $map */
    private static function rekey(array $selection, array $map): array
    {
        $rekeyed = [];

        foreach ($selection as $key => $value) {
            $rekeyed[$map[$key] ?? $key] = $value;
        }

        return $rekeyed;
    }

    /** @param array<string, mixed> $room */
    private static function upgradeRoomUnits(array $room): array
    {
        foreach (['length' => 'lengthMm', 'width' => 'widthMm', 'height' => 'heightMm', 'perimeter' => 'perimeterMm'] as $old => $new) {
            if (isset($room[$old]) && is_numeric($room[$old]) && ! isset($room[$new])) {
                $room[$new] = self::mToMm((float) $room[$old]);
            }

            unset($room[$old]);
        }

        foreach (is_array($room['openings'] ?? null) ? $room['openings'] : [] as $index => $opening) {
            if (! is_array($opening)) {
                continue;
            }

            foreach (['width' => 'widthMm', 'height' => 'heightMm'] as $old => $new) {
                if (isset($opening[$old]) && is_numeric($opening[$old]) && ! isset($opening[$new])) {
                    $opening[$new] = self::mToMm((float) $opening[$old]);
                }

                unset($opening[$old]);
            }

            $room['openings'][$index] = $opening;
        }

        return $room;
    }
}
