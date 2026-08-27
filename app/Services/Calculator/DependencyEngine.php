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
        }

        return $requests;
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

        // The visitor tells us what is on the floor now; without that we cannot
        // guess which demolition rate applies, so nothing is suggested.
        $existing = strtoupper((string) $zone->option('existingCover', ''));

        $steps = array_merge($steps, match ($existing) {
            'LAMINATE', 'SPC' => [['workId' => 'DEM_FLOOR_LAMINATE', 'quantity' => $area]],
            'PARQUET' => [['workId' => 'DEM_FLOOR_PARQUET', 'quantity' => $area]],
            'TILE', 'STONE' => [['workId' => 'DEM_FLOOR_TILE', 'quantity' => $area]],
            default => [],
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
            FinishCategory::WallTile => array_merge(
                [$this->tileInstallStep($zone)],
                [['workId' => 'TILE_GROUT', 'quantity' => $area]],
                $zone->wetZone ? [['workId' => 'TILE_SILICONE', 'quantity' => $room->perimeter]] : [],
            ),
            FinishCategory::FloorTile => array_merge(
                [[
                    'workId' => 'FLOOR_TILE_INSTALL',
                    'quantity' => $area,
                    'coefficients' => $this->tileFormatCoefficients($zone),
                ]],
                [['workId' => 'TILE_GROUT', 'quantity' => $area]],
                $zone->wetZone ? [['workId' => 'TILE_SILICONE', 'quantity' => $room->perimeter]] : [],
            ),
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

    /** Tile format decides both the work id and the coefficient (Coefficients sheet). */
    private function tileInstallStep(SurfaceZone $zone): array
    {
        $format = strtoupper((string) $zone->option('format', 'STANDARD'));

        return match ($format) {
            'LARGE' => ['workId' => 'TILE_INSTALL_LARGE', 'quantity' => $zone->area, 'coefficients' => ['K_TILE_LARGE']],
            'MOSAIC' => ['workId' => 'TILE_INSTALL_MOSAIC', 'quantity' => $zone->area],
            default => ['workId' => 'TILE_INSTALL_STANDARD', 'quantity' => $zone->area, 'coefficients' => $this->layoutCoefficients($zone)],
        };
    }

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
