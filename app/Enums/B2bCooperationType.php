<?php

declare(strict_types=1);

namespace App\Enums;

/** "Əməkdaşlıq növü" from the Figma B2B onboarding (step 3). */
enum B2bCooperationType: string
{
    case DirectSupply = 'direct_supply';
    case Distributor = 'distributor';
    case Dealer = 'dealer';
    case OfficialRep = 'official_rep';
    case ExclusiveDistributor = 'exclusive_distributor';
    case NonExclusiveDistributor = 'non_exclusive_distributor';
    case Showroom = 'showroom';
    case Partnership = 'partnership';
    case ProjectCooperation = 'project_cooperation';
    case Oem = 'oem';
    case Odm = 'odm';

    /** Admin-facing Azerbaijani label; the public page uses the translation keys below. */
    public function label(): string
    {
        return match ($this) {
            self::DirectSupply => 'Birbaşa təchizat',
            self::Distributor => 'Distribütor',
            self::Dealer => 'Diler',
            self::OfficialRep => 'Rəsmi nümayəndə',
            self::ExclusiveDistributor => 'Eksklüziv distribütor',
            self::NonExclusiveDistributor => 'Qeyri-eksklüziv distribütor',
            self::Showroom => 'Showroom',
            self::Partnership => 'Tərəfdaşlıq',
            self::ProjectCooperation => 'Layihə əməkdaşlığı',
            self::Oem => 'OEM',
            self::Odm => 'ODM',
        };
    }

    /** Translation key under the `b2b` group, e.g. b2b.cooperation.distributor. */
    public function translationKey(): string
    {
        return 'b2b.cooperation.'.$this->value;
    }

    public function translated(): string
    {
        $value = t($this->translationKey());

        return is_string($value) && $value !== $this->translationKey() ? $value : $this->label();
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
