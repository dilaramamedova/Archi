<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\TranslatesLabels;

/**
 * How the four specialist categories are grouped into the sections of the public
 * directory (/specialists).
 *
 * The directory used to be one undifferentiated wall of cards: an architect, a tiler
 * and a construction company sat side by side with nothing to tell a visitor that they
 * answer completely different questions. The sections restore that distinction — design
 * work (architects + interior designers) is chosen differently from a trade, so the two
 * share one heading while every other category keeps its own.
 *
 * The grouping lives here rather than in the blade so the SQL ordering and the rendered
 * headings can never drift apart: `rank()` feeds the ORDER BY, `label()` the heading.
 */
enum SpecialistCategoryGroup: string
{
    use TranslatesLabels;

    case Design = 'design';
    case Master = 'master';
    case Company = 'company';

    /**
     * Profiles whose specialty row was deleted (the FK is nullOnDelete) still have to
     * land somewhere — they are active people, not orphan data to hide.
     */
    case Other = 'other';

    /** Display order of the sections, top to bottom. */
    public static function ordered(): array
    {
        return [self::Design, self::Master, self::Company, self::Other];
    }

    public function rank(): int
    {
        return match ($this) {
            self::Design => 1,
            self::Master => 2,
            self::Company => 3,
            self::Other => 4,
        };
    }

    /** Resolve the rank `orderByCaseSql()` emits back to its section. */
    public static function fromRank(int $rank): self
    {
        foreach (self::cases() as $group) {
            if ($group->rank() === $rank) {
                return $group;
            }
        }

        return self::Other;
    }

    public function label(): string
    {
        return self::translatedLabel('specialists.group.'.$this->value, $this->fallbackLabel());
    }

    /** Short line under the heading explaining who belongs in the section. */
    public function tagline(): string
    {
        return self::translatedLabel('specialists.group.'.$this->value.'_tagline', $this->fallbackTagline());
    }

    /**
     * The categories this section collects.
     *
     * @return list<SpecialistCategory>
     */
    public function categories(): array
    {
        return match ($this) {
            self::Design => [SpecialistCategory::Architect, SpecialistCategory::Designer],
            self::Master => [SpecialistCategory::Master],
            self::Company => [SpecialistCategory::Company],
            self::Other => [],
        };
    }

    /**
     * `CASE specialist_specialties.category WHEN ... END` used to order the listing by
     * section before anything else. Built from the enum so adding a category cannot
     * leave a section silently unsorted; profiles with no specialty fall to Other.
     */
    public static function orderByCaseSql(string $column): string
    {
        $arms = '';

        foreach (SpecialistCategory::cases() as $category) {
            $arms .= " when '".$category->value."' then ".$category->group()->rank();
        }

        return '(case '.$column.$arms.' else '.self::Other->rank().' end)';
    }

    private function fallbackLabel(): string
    {
        return match ($this) {
            self::Design => 'Memarlar və dizaynerlər',
            self::Master => 'Ustalar',
            self::Company => 'Tikinti şirkətləri',
            self::Other => 'Digər mütəxəssislər',
        };
    }

    private function fallbackTagline(): string
    {
        return match ($this) {
            self::Design => 'Layihə, planlaşdırma və interyer həlləri',
            self::Master => 'Təmir və quraşdırma işlərini görən peşəkarlar',
            self::Company => 'Tam dövrlü tikinti və təmir şirkətləri',
            self::Other => 'İxtisası hələ təyin olunmamış mütəxəssislər',
        };
    }
}
