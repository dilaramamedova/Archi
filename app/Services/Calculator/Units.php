<?php

namespace App\Services\Calculator;

/**
 * The unit boundary of the calculator — and the only place in the codebase that
 * divides a millimetre by a thousand.
 *
 * THE RULE
 * --------
 * The visitor types millimetres, because that is how architects and builders in this
 * market specify a room: 4500, not 4.5. The engine prices in metres, because every
 * work tariff in `calculator_work_rates` is per m² or per running metre and every
 * material norm is per m². Rewriting the engine to mm² would turn every
 * admin-entered price into a meaningless number.
 *
 * So the payload carries millimetres and the engine converts once, here:
 *
 *   - every LINEAR dimension in the payload is an integer in millimetres and its key
 *     ends in `Mm` — `lengthMm`, `widthMm`, `heightMm`, an opening's `widthMm` and
 *     `heightMm`, manual geometry's `perimeterMm`;
 *   - every AREA stays in m² and keeps its plain name — `object.area`,
 *     `floorArea`, `netWallArea`, `warmFloor.area`, `hvac.conditionedArea`;
 *   - every quantity that is multiplied straight into a per-running-metre tariff
 *     keeps metres and keeps its `…Meters` name — `routeMeters`, `drainMeters`,
 *     `cableMeters`, `waterPipeMeters` and the rest. Those are not geometry; they
 *     are the quantity of a line item, in the unit its tariff is priced in.
 *
 * The `Mm` suffix is not decoration. It is what makes a metre-era payload
 * *detectable* instead of silently misread: a 4.5 m room saved before this change
 * carries `length`, never `lengthMm`, so it can never be read back as 4.5 mm.
 * upgradePayload() below converts those old payloads exactly once, on read.
 */
final class Units
{
    /**
     * Shape version of the `inputs` payload stored on calculator_sessions and in the
     * browser's localStorage.
     *
     *   1 — room and opening dimensions in METRES (`length`, `width`, `height`)
     *   2 — room and opening dimensions in MILLIMETRES (`lengthMm`, `widthMm`, …)
     */
    public const PAYLOAD_VERSION = 2;

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
     * is now stored in millimetres too (DEFAULT_ROOM_HEIGHT_MM and friends).
     */
    public static function dimension(array $node, string $key, float $defaultMm = 0.0): float
    {
        $mm = array_key_exists($key, $node) && is_numeric($node[$key])
            ? (float) $node[$key]
            : $defaultMm;

        return max(0.0, self::mmToM($mm));
    }

    /**
     * Bring a stored payload up to PAYLOAD_VERSION.
     *
     * A session saved before the switch holds metres. Re-reading it as millimetres
     * would turn a 4.5 m room into 4.5 mm and quote the client 0.00 AZN, so the
     * conversion happens here, once, keyed off the version stamp — never by guessing
     * from the magnitude of the number.
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

        if ($version >= self::PAYLOAD_VERSION) {
            $inputs['v'] = self::PAYLOAD_VERSION;

            return $inputs;
        }

        foreach (is_array($inputs['rooms'] ?? null) ? $inputs['rooms'] : [] as $index => $room) {
            if (! is_array($room)) {
                continue;
            }

            $inputs['rooms'][$index] = self::upgradeRoom($room);
        }

        $inputs['v'] = self::PAYLOAD_VERSION;

        return $inputs;
    }

    /** @param array<string, mixed> $room */
    private static function upgradeRoom(array $room): array
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
