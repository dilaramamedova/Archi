<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\TranslatesLabels;

/**
 * The groups the header's "Mütəxəssislər" mega panel offers.
 *
 * The case VALUES are the `?type=` values those menu links already carried —
 * /specialists?type=architect and friends. The links were seeded long before
 * anything read the parameter, so every one of them silently landed on the full
 * directory; this enum is the contract that finally gives them meaning, which is
 * why the values are fixed by the existing menu rather than chosen freshly.
 *
 * A specialty belongs to exactly one category (specialist_specialties.category),
 * so the grouping is data an admin can edit, not a hardcoded map of ids that
 * would rot the first time someone adds a trade.
 */
enum SpecialistCategory: string
{
    use TranslatesLabels;

    case Architect = 'architect';
    case Designer = 'designer';
    case Master = 'master';
    case Company = 'company';

    /** «Briqadir» — a foreman who leads his own crew («usta dəstəsi») and takes whole jobs. */
    case Foreman = 'foreman';

    /** «Makler» — a real-estate agent (əmlakçı). */
    case Realtor = 'realtor';

    public function label(): string
    {
        return self::translatedLabel('specialists.category.'.$this->value, $this->fallbackLabel());
    }

    private function fallbackLabel(): string
    {
        return match ($this) {
            self::Architect => 'Arxitektorlar',
            self::Designer => 'İnteryer dizaynerlər',
            self::Master => 'Ustalar',
            self::Company => 'Tikinti şirkətləri',
            self::Foreman => 'Briqadirlər',
            self::Realtor => 'Maklerlər',
        };
    }

    /** One person of this kind («Briqadir», «Usta») — for a single row, e.g. the admin badge. */
    public function singularLabel(): string
    {
        return self::translatedLabel('specialists.kind.'.$this->value, match ($this) {
            self::Architect => 'Arxitektor',
            self::Designer => 'İnteryer dizayner',
            self::Master => 'Usta',
            self::Company => 'Tikinti şirkəti',
            self::Foreman => 'Briqadir',
            self::Realtor => 'Makler',
        });
    }

    /** @return array<string, string> value => singular label */
    public static function singularOptions(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->singularLabel()])
            ->all();
    }

    /**
     * Kinds that get their own registration card and their own profile details
     * (specialist_profiles.details) instead of being one trade in the master select.
     * Each is backed by a single specialty row which registration resolves from the
     * kind, so the visitor never picks it from a list. See docs/specialist-kinds.md.
     */
    public function hasOwnKind(): bool
    {
        return in_array($this, self::ownKinds(), true);
    }

    /** @return list<self> */
    public static function ownKinds(): array
    {
        return [self::Foreman, self::Realtor];
    }

    /** @return list<string> */
    public static function ownKindValues(): array
    {
        return array_map(static fn (self $kind): string => $kind->value, self::ownKinds());
    }

    /**
     * The directory section this category is rendered under. Architecture and interior
     * design answer the same visitor question ("who designs it?"), so they share a
     * section; every other category keeps its own.
     */
    public function group(): SpecialistCategoryGroup
    {
        return match ($this) {
            self::Architect, self::Designer => SpecialistCategoryGroup::Design,
            self::Master => SpecialistCategoryGroup::Master,
            self::Company => SpecialistCategoryGroup::Company,
            self::Foreman => SpecialistCategoryGroup::Foreman,
            self::Realtor => SpecialistCategoryGroup::Realtor,
        };
    }

    /** @return array<string, string> value => label, for Filament selects */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
