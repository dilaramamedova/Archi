<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\FinishCategory;
use App\Enums\Calculator\SurfaceState;
use App\Enums\Calculator\SurfaceType;

/**
 * "Current state → target finish" (master spec §9).
 *
 * The client is not required to know construction technology. They say what the
 * surface looks like now and what they want it to look like; this class produces the
 * technological chain — primer, plaster, putty, sanding, finishing primer, paint —
 * with the right quantity already attached to each step. The visitor may delete a
 * suggested step afterwards: that is a warning about technological risk, never a
 * hard block.
 */
class DependencyEngine
{
    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, RoomGeometry>  $rooms
     * @return array<int, WorkRequest>
     */
    public function suggest(array $rooms): array
    {
        $requests = [];

        foreach ($rooms as $room) {
            $heightCoefficient = $this->config->heightCoefficientId($room->height);

            foreach ($room->zones as $zone) {
                foreach ($this->forZone($zone, $room, $heightCoefficient) as $request) {
                    $requests[] = $request;
                }
            }

            if ($sealant = $this->sealantStep($room)) {
                $requests[] = $sealant;
            }
        }

        return $requests;
    }

    /**
     * Silicone along the wet-room joints — once per room.
     *
     * It used to be a step of each tiled zone, so a bathroom with tiled walls AND a
     * tiled floor was billed the room's perimeter twice: a 2×2 m bathroom (8 m of
     * perimeter) came out at 16 p.m. The sealed joint is a property of the room, not
     * of a surface, which is exactly why it cannot be derived per zone (master spec
     * §6.3 — one quantity, one source).
     */
    private function sealantStep(RoomGeometry $room): ?WorkRequest
    {
        $tiled = array_filter(
            $room->zones,
            fn (SurfaceZone $zone) => $zone->wetZone && in_array(
                $zone->finish,
                [FinishCategory::WallTile, FinishCategory::FloorTile],
                true,
            ),
        );

        if ($tiled === [] || $room->perimeter <= 0) {
            return null;
        }

        return new WorkRequest(
            workId: 'TILE_SILICONE',
            quantity: $room->perimeter,
            roomId: $room->id,
            roomName: $room->name,
            meta: ['source' => 'ROOM_PERIMETER'],
        );
    }

    /** @return array<int, WorkRequest> */
    private function forZone(SurfaceZone $zone, RoomGeometry $room, ?string $heightCoefficient): array
    {
        if (! $zone->finish) {
            return [];
        }

        $steps = array_merge(
            $this->preparationSteps($zone),
            $this->finishSteps($zone, $room),
        );

        $requests = [];

        foreach ($steps as $step) {
            $coefficients = $step['coefficients'] ?? [];

            // Wall and ceiling labour is what a tall room actually costs more of;
            // floor work is unaffected by ceiling height.
            if ($heightCoefficient && $zone->surface !== SurfaceType::Floor) {
                $coefficients[] = $heightCoefficient;
            }

            $requests[] = new WorkRequest(
                workId: $step['workId'],
                quantity: $step['quantity'],
                roomId: $room->id,
                roomName: $room->name,
                coefficientIds: array_values(array_unique($coefficients)),
                suggested: true,
                meta: ['zone' => $zone->surface->value, 'finish' => $zone->finish->value],
            );
        }

        return array_values(array_filter($requests, fn (WorkRequest $r) => $r->quantity > 0));
    }

    /**
     * What has to happen to the surface before the chosen finish can go on it.
     *
     * @return array<int, array{workId: string, quantity: float, coefficients?: array<int, string>}>
     */
    private function preparationSteps(SurfaceZone $zone): array
    {
        return match ($zone->surface) {
            SurfaceType::Wall => $this->wallPreparation($zone),
            SurfaceType::Floor => $this->floorPreparation($zone),
            SurfaceType::Ceiling => $this->ceilingPreparation($zone),
        };
    }

    private function wallPreparation(SurfaceZone $zone): array
    {
        $area = $zone->area;
        $needsSmoothBase = in_array($zone->finish, [
            FinishCategory::Paint,
            FinishCategory::Wallpaper,
            FinishCategory::DecorativePlaster,
        ], true);

        $steps = [];

        // Whatever is on the wall today has to come off first.
        $steps = array_merge($steps, match ($zone->currentState) {
            SurfaceState::OldFinish => [['workId' => 'DEM_WALL_FINISH', 'quantity' => $area]],
            SurfaceState::OldTile => [['workId' => 'DEM_WALL_TILE', 'quantity' => $area]],
            default => [],
        });

        if (! $needsSmoothBase) {
            // Tile, panels and stone need a sound, flat base — not a painted one.
            if ($zone->currentState === SurfaceState::Shell) {
                $steps[] = ['workId' => 'WALL_PRIMER_BASE', 'quantity' => $area];
                $steps[] = ['workId' => 'WALL_PLASTER', 'quantity' => $area, 'coefficients' => $this->plasterCoefficients($zone)];
            }

            if ($zone->finish === FinishCategory::WallTile) {
                $steps[] = ['workId' => 'TILE_SURFACE_PREP', 'quantity' => $area];

                if ($zone->wetZone) {
                    $steps[] = ['workId' => 'TILE_WATERPROOF_WALL', 'quantity' => $area];
                }
            }

            return $steps;
        }

        $steps = array_merge($steps, match ($zone->currentState) {
            SurfaceState::Shell => [
                ['workId' => 'WALL_PRIMER_BASE', 'quantity' => $area],
                ['workId' => 'WALL_PLASTER', 'quantity' => $area, 'coefficients' => $this->plasterCoefficients($zone)],
                ['workId' => 'WALL_PUTTY', 'quantity' => $area],
                ['workId' => 'WALL_SANDING', 'quantity' => $area],
            ],
            SurfaceState::PlasterReady, SurfaceState::OldFinish, SurfaceState::OldTile => [
                ['workId' => 'WALL_PUTTY', 'quantity' => $area],
                ['workId' => 'WALL_SANDING', 'quantity' => $area],
            ],
            default => [],
        });

        // Decorative plaster and wallpaper are applied over the same finishing primer
        // as paint, so the last preparation step is shared by all three.
        $steps[] = ['workId' => 'WALL_PRIMER_FINISH', 'quantity' => $area];

        return $steps;
    }

    private function floorPreparation(SurfaceZone $zone): array
    {
        $area = $zone->area;
        $steps = [];

        // What is on the floor now decides which removal rate applies. The explicit
        // existingCover option is the most precise answer; without it the zone's own
        // state still says enough — an OLD_FINISH floor cannot take a new covering
        // until the old one comes off, and skipping that step silently priced every
        // old flat as if its floors were bare (the wall chain never had this gap).
        $existing = strtoupper((string) $zone->option('existingCover', ''));

        $steps = array_merge($steps, match ($existing) {
            'LAMINATE', 'SPC' => [['workId' => 'DEM_FLOOR_LAMINATE', 'quantity' => $area]],
            'PARQUET' => [['workId' => 'DEM_FLOOR_PARQUET', 'quantity' => $area]],
            'TILE', 'STONE' => [['workId' => 'DEM_FLOOR_TILE', 'quantity' => $area]],
            default => match ($zone->currentState) {
                // Soft coverings share the laminate rate; the exact material can be
                // corrected via existingCover, the step itself must not be missing.
                SurfaceState::OldFinish => [['workId' => 'DEM_FLOOR_LAMINATE', 'quantity' => $area]],
                SurfaceState::OldTile => [['workId' => 'DEM_FLOOR_TILE', 'quantity' => $area]],
                default => [],
            },
        });

        if ($zone->currentState === SurfaceState::RoughFloor || $zone->option('screed', false)) {
            $steps[] = ['workId' => 'FLOOR_SCREED', 'quantity' => $area];
        }

        if ($zone->option('selfLevel', false)) {
            $steps[] = ['workId' => 'FLOOR_SELF_LEVEL', 'quantity' => $area];
        }

        if ($zone->wetZone) {
            $steps[] = ['workId' => 'FLOOR_WATERPROOF', 'quantity' => $area];
        }

        if ($zone->finish === FinishCategory::FloorTile) {
            $steps[] = ['workId' => 'TILE_SURFACE_PREP', 'quantity' => $area];
        }

        return $steps;
    }

    private function ceilingPreparation(SurfaceZone $zone): array
    {
        $area = $zone->area;

        // A suspended or stretch ceiling hides the slab, so the smoothing chain is
        // pointless under it — only the old ceiling has to come down.
        if (in_array($zone->finish, [
            FinishCategory::GypsumFlat,
            FinishCategory::GypsumComplex,
            FinishCategory::StretchCeiling,
        ], true)) {
            return match (strtoupper((string) $zone->option('existingCeiling', ''))) {
                'GYPSUM' => [['workId' => 'DEM_CEILING_GYPSUM', 'quantity' => $area]],
                'STRETCH' => [['workId' => 'DEM_CEILING_STRETCH', 'quantity' => $area]],
                default => [],
            };
        }

        return match ($zone->currentState) {
            SurfaceState::Shell => [
                ['workId' => 'CEIL_PRIMER_BASE', 'quantity' => $area],
                ['workId' => 'CEIL_PLASTER', 'quantity' => $area],
                ['workId' => 'CEIL_PUTTY', 'quantity' => $area],
                ['workId' => 'CEIL_SANDING', 'quantity' => $area],
            ],
            SurfaceState::PlasterReady, SurfaceState::OldFinish => [
                ['workId' => 'CEIL_PUTTY', 'quantity' => $area],
                ['workId' => 'CEIL_SANDING', 'quantity' => $area],
            ],
            default => [],
        };
    }

    /**
     * The finishing work itself.
     *
     * @return array<int, array{workId: string, quantity: float, coefficients?: array<int, string>}>
     */
    private function finishSteps(SurfaceZone $zone, RoomGeometry $room): array
    {
        $area = $zone->area;

        return match ($zone->finish) {
            FinishCategory::Paint => [['workId' => 'WALL_PAINT', 'quantity' => $area]],
            FinishCategory::Wallpaper => [['workId' => 'WALL_WALLPAPER', 'quantity' => $area]],
            FinishCategory::DecorativePlaster => [[
                'workId' => 'WALL_DECOR_PLASTER',
                'quantity' => $area,
                'coefficients' => $zone->option('complex', false) ? ['K_DECOR_COMPLEX'] : [],
            ]],
            FinishCategory::WallPanel => [['workId' => 'WALL_PANEL_INSTALL', 'quantity' => $area]],
            FinishCategory::WallStone => [[
                'workId' => 'WALL_STONE_INSTALL',
                'quantity' => $area,
                'coefficients' => $this->tileFormatCoefficients($zone),
            ]],
            // The sealant is NOT here: it belongs to the room, and sealantStep()
            // emits it once. Grout is per square metre of tile, so it is.
            FinishCategory::WallTile => [
                $this->tileInstallStep($zone),
                ['workId' => 'TILE_GROUT', 'quantity' => $area],
            ],
            FinishCategory::FloorTile => [
                [
                    'workId' => 'FLOOR_TILE_INSTALL',
                    'quantity' => $area,
                    'coefficients' => $this->tileFormatCoefficients($zone),
                ],
                ['workId' => 'TILE_GROUT', 'quantity' => $area],
            ],
            FinishCategory::Laminate => [
                ['workId' => 'FLOOR_LAMINATE_INSTALL', 'quantity' => $area],
                ['workId' => 'SKIRTING_INSTALL', 'quantity' => $room->skirtingLength],
            ],
            FinishCategory::Spc => [
                ['workId' => 'FLOOR_SPC_INSTALL', 'quantity' => $area],
                ['workId' => 'SKIRTING_INSTALL', 'quantity' => $room->skirtingLength],
            ],
            FinishCategory::Parquet => [
                [
                    'workId' => 'FLOOR_PARQUET_INSTALL',
                    'quantity' => $area,
                    'coefficients' => $zone->option('herringbone', false) ? ['K_PARQUET_HERRINGBONE'] : [],
                ],
                ['workId' => 'SKIRTING_INSTALL', 'quantity' => $room->skirtingLength],
            ],
            FinishCategory::FloorStone => [
                [
                    'workId' => 'FLOOR_STONE_INSTALL',
                    'quantity' => $area,
                    'coefficients' => $this->tileFormatCoefficients($zone),
                ],
                ['workId' => 'SKIRTING_INSTALL', 'quantity' => $room->skirtingLength],
            ],
            FinishCategory::CeilingPaint => [['workId' => 'CEIL_PAINT', 'quantity' => $area]],
            FinishCategory::GypsumFlat => [['workId' => 'CEIL_GYPSUM_FLAT', 'quantity' => $area]],
            FinishCategory::GypsumComplex => [['workId' => 'CEIL_GYPSUM_COMPLEX', 'quantity' => $area]],
            FinishCategory::StretchCeiling => [['workId' => 'CEIL_STRETCH', 'quantity' => $area]],
            default => [],
        };
    }

    /** Wall-tile format => its own tariff row. */
    private const WALL_TILE_WORKS = [
        'LARGE' => 'TILE_INSTALL_LARGE',
        'MOSAIC' => 'TILE_INSTALL_MOSAIC',
        'STANDARD' => 'TILE_INSTALL_STANDARD',
    ];

    /**
     * Wall tiling: the format picks the tariff row, the layout picks the coefficient.
     *
     * The format must NOT also add K_TILE_LARGE. Large-format wall tile has its own
     * rate (18 ₼/m² against the standard 15) — that difference IS the surcharge, and
     * multiplying it by K_TILE_LARGE (1.25) again charged 22.50 ₼/m² for work the
     * admin priced at 18. Mosaic, on the same code path, never got a coefficient, so
     * the rule was not even applied consistently.
     *
     * The layout coefficient now reaches every format. A diagonal large-format wall
     * used to lose K_TILE_DIAGONAL (1.15) completely, because the LARGE branch
     * returned before the layout was ever looked at.
     */
    private function tileInstallStep(SurfaceZone $zone): array
    {
        $format = strtoupper((string) $zone->option('format', 'STANDARD'));

        return [
            'workId' => self::WALL_TILE_WORKS[$format] ?? self::WALL_TILE_WORKS['STANDARD'],
            'quantity' => $zone->area,
            'coefficients' => $this->layoutCoefficients($zone),
        ];
    }

    /**
     * Floors and stone have a single tariff row for every format, so there the
     * format has to come from the coefficient — the opposite of walls above.
     */
    private function tileFormatCoefficients(SurfaceZone $zone): array
    {
        $coefficients = strtoupper((string) $zone->option('format', 'STANDARD')) === 'LARGE'
            ? ['K_TILE_LARGE']
            : [];

        return array_merge($coefficients, $this->layoutCoefficients($zone));
    }

    private function layoutCoefficients(SurfaceZone $zone): array
    {
        return strtoupper((string) $zone->option('layout', '')) === 'DIAGONAL'
            ? ['K_TILE_DIAGONAL']
            : [];
    }

    private function plasterCoefficients(SurfaceZone $zone): array
    {
        $thickness = (float) $zone->option('plasterThicknessMm', $this->config->float('PLASTER_DEFAULT_THICKNESS_MM', 20));

        return $thickness > $this->config->float('PLASTER_THICK_THRESHOLD_MM', 30)
            ? ['K_PLASTER_THICK']
            : [];
    }
}
