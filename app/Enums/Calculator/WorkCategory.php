<?php

namespace App\Enums\Calculator;

/**
 * Categories of the work catalog (master spec §5 / §10). The value is stored in
 * calculator_work_rates.category, so the strings are part of the admin data contract.
 */
enum WorkCategory: string
{
    case Demolition = 'DEMOLITION';
    case Walls = 'WALLS';
    case Floor = 'FLOOR';
    case Ceiling = 'CEILING';
    case Tiling = 'TILING';
    case Electrical = 'ELECTRICAL';
    case Plumbing = 'PLUMBING';
    case Hvac = 'HVAC';
    case Ventilation = 'VENTILATION';
    case Doors = 'DOORS';
    case Lighting = 'LIGHTING';
    case Logistics = 'LOGISTICS';
    case Services = 'SERVICES';
    case Materials = 'MATERIALS';
    /** Group E of the final estimate: the financial reserve and, when Archi turns it
     *  on, VAT. Kept apart from the services it is calculated over (master spec §16). */
    case Reserve = 'RESERVE';

    public function labelKey(): string
    {
        return 'calculator-detailed.category.'.strtolower($this->value);
    }

    /** Logistics and external services are their own subtotal in the final estimate (§16). */
    public function lineType(): LineType
    {
        return match ($this) {
            self::Logistics => LineType::Logistics,
            self::Services => LineType::Service,
            self::Materials => LineType::Material,
            self::Reserve => LineType::Contingency,
            default => LineType::Work,
        };
    }
}
