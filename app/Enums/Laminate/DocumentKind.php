<?php

declare(strict_types=1);

namespace App\Enums\Laminate;

use App\Enums\Concerns\TranslatesLabels;

/**
 * The documents a laminate card can carry (TZ §5).
 *
 * Photos are not here on purpose: the site already keeps them in product_images
 * with its own limits and main-image logic, and the TZ asks for the site's standard
 * upload rules to be reused.
 */
enum DocumentKind: string
{
    use TranslatesLabels;

    case TechnicalPassport = 'technical_passport';
    case Installation = 'installation';
    case WarrantyConditions = 'warranty_conditions';
    case WaterConditions = 'water_conditions';

    public function label(): string
    {
        return self::translatedLabel('laminate.document_kind.'.$this->value, match ($this) {
            self::TechnicalPassport => 'Texniki pasport',
            self::Installation => 'Quraşdırma təlimatı',
            self::WarrantyConditions => 'Zəmanət şərtləri',
            self::WaterConditions => 'Suya qarşı qoruma şərtləri',
        });
    }

    /**
     * Documents that only mean something while their parent answer is "yes". When
     * that answer is withdrawn they leave the active card (TZ §4) but stay on disk.
     */
    public function dependsOn(): ?string
    {
        return match ($this) {
            self::WaterConditions => 'water_protection',
            self::WarrantyConditions => 'warranty',
            default => null,
        };
    }
}
