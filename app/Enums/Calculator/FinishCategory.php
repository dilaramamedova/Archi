<?php

namespace App\Enums\Calculator;

/**
 * Target finish of a surface zone - the right-hand side of the dependency engine.
 * Each case knows which surface it belongs to, which fallback material category
 * prices it when no catalog product is chosen, and which waste rule applies.
 */
enum FinishCategory: string
{
    case Paint = 'PAINT';
    case Wallpaper = 'WALLPAPER';
    case DecorativePlaster = 'DECOR_PLASTER';
    case WallTile = 'WALL_TILE';
    case WallPanel = 'WALL_PANEL';
    case WallStone = 'WALL_STONE';

    case FloorTile = 'FLOOR_TILE';
    case Laminate = 'LAMINATE';
    case Spc = 'SPC';
    case Parquet = 'PARQUET';
    case FloorStone = 'FLOOR_STONE';

    case CeilingPaint = 'CEILING_PAINT';
    case GypsumFlat = 'GYPSUM_FLAT';
    case GypsumComplex = 'GYPSUM_COMPLEX';
    case StretchCeiling = 'STRETCH_CEILING';

    public function surface(): SurfaceType
    {
        return match ($this) {
            self::Paint, self::Wallpaper, self::DecorativePlaster,
            self::WallTile, self::WallPanel, self::WallStone => SurfaceType::Wall,
            self::FloorTile, self::Laminate, self::Spc,
            self::Parquet, self::FloorStone => SurfaceType::Floor,
            self::CeilingPaint, self::GypsumFlat,
            self::GypsumComplex, self::StretchCeiling => SurfaceType::Ceiling,
        };
    }

    /** Fallback material category used when the visitor picked no catalog product. */
    public function materialCategoryId(): ?string
    {
        return match ($this) {
            self::Paint => 'MAT_WALL_PAINT',
            self::Wallpaper => 'MAT_WALL_WALLPAPER',
            self::DecorativePlaster => 'MAT_WALL_DECOR_PLASTER',
            self::WallTile => 'MAT_WALL_TILE',
            self::WallPanel => 'MAT_WALL_PANEL',
            self::WallStone => 'MAT_WALL_STONE',
            self::FloorTile => 'MAT_FLOOR_TILE',
            self::Laminate => 'MAT_FLOOR_LAMINATE',
            self::Spc => 'MAT_FLOOR_SPC',
            self::Parquet => 'MAT_FLOOR_PARQUET',
            self::FloorStone => 'MAT_FLOOR_STONE',
            self::CeilingPaint => 'MAT_CEIL_PAINT',
            self::GypsumFlat, self::GypsumComplex => 'MAT_CEIL_GYPSUM',
            self::StretchCeiling => 'MAT_CEIL_STRETCH',
        };
    }

    /** Waste rule id (calculator_material_norms) applied to this finish material. */
    public function wasteRuleId(): ?string
    {
        return match ($this) {
            self::FloorTile, self::WallTile => 'WASTE_TILE_STANDARD',
            self::Laminate => 'WASTE_LAMINATE',
            self::Spc => 'WASTE_SPC',
            self::Parquet => 'WASTE_PARQUET',
            default => null,
        };
    }

    /** True when the zone counts as a tiled surface for adhesive / grout quantities. */
    public function isTile(): bool
    {
        return in_array($this, [self::WallTile, self::FloorTile], true);
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.finishes.'.strtolower($this->value);
    }

    /**
     * Finishes that cannot be combined with this one as the covering of a single
     * zone. A zone carries exactly one finish, so this is what the picker uses to
     * grey out impossible pairs on the same surface (master spec 9.1).
     */
    public function incompatibleWith(): array
    {
        return array_values(array_map(
            fn (self $c) => $c->value,
            array_filter(
                self::cases(),
                fn (self $c) => $c !== $this && $c->surface() === $this->surface(),
            ),
        ));
    }
}
