<?php

namespace App\Enums\Calculator;

/**
 * The technology behind a finish — the only classification the engine still owns.
 *
 * A finish itself ("Laminat", "Divar kafeli", "Gərilmə tavan") is a row of
 * calculator_finishes that an admin adds, renames or reprices without a release.
 * What the engine needs to know about it is far smaller: which preparation chain
 * the surface needs before it goes on, and which consumables it eats. That is the
 * family, and there are five of them.
 */
enum FinishFamily: string
{
    /** Paint, wallpaper, decorative plaster, microcement — needs a smooth, primed base. */
    case Coat = 'COAT';

    /** Tile, porcelain, stone, mosaic — adhesive, grout, waterproofing in wet rooms. */
    case Tile = 'TILE';

    /** Wall panels, wood cladding — a sound base, no adhesive chain. */
    case Panel = 'PANEL';

    /** Laminate, SPC, parquet, carpet — a screed underneath, skirting around. */
    case Floating = 'FLOATING';

    /** Plasterboard and stretch ceilings — hide the slab, so no smoothing chain at all. */
    case Suspended = 'SUSPENDED';

    /** @return array<int, SurfaceType> the surfaces a finish of this family may be put on */
    public function surfaces(): array
    {
        return match ($this) {
            self::Coat => [SurfaceType::Wall, SurfaceType::Ceiling],
            self::Tile => [SurfaceType::Wall, SurfaceType::Floor],
            self::Panel => [SurfaceType::Wall],
            self::Floating => [SurfaceType::Floor],
            self::Suspended => [SurfaceType::Ceiling],
        };
    }

    public function allows(SurfaceType $surface): bool
    {
        return in_array($surface, $this->surfaces(), true);
    }

    /** The estimate block the finish's own labour line belongs to. */
    public function workCategory(SurfaceType $surface): WorkCategory
    {
        if ($this === self::Tile) {
            return WorkCategory::Tiling;
        }

        return match ($surface) {
            SurfaceType::Floor => WorkCategory::Floor,
            SurfaceType::Wall => WorkCategory::Walls,
            SurfaceType::Ceiling => WorkCategory::Ceiling,
        };
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.families.'.strtolower($this->value);
    }

    /** @return array<string, string> value => label, for the admin panel */
    public static function options(): array
    {
        return [
            self::Coat->value => 'Boya tipli (boya, divar kağızı, dekorativ suvaq) — hamar əsas tələb edir',
            self::Tile->value => 'Kafel tipli (kafel, keramoqranit, daş, mozaika) — yapışqan, dərz, hidroizolyasiya',
            self::Panel->value => 'Panel (divar paneli, taxta üzlük) — möhkəm əsas, yapışqansız',
            self::Floating->value => 'Üzən döşəmə (laminat, SPC, parket, kovrolin) — stəjka və plintus',
            self::Suspended->value => 'Asma tavan (alçıpan, gərilmə) — tavanı örtür, hamarlama lazım deyil',
        ];
    }
}
