<?php

namespace App\Enums\Calculator;

/** Master spec 11.3 - the system type decides both the inputs and the precision class. */
enum HvacSystemType: string
{
    case None = 'NONE';
    case Split = 'SPLIT';
    case MultiSplit = 'MULTI_SPLIT';
    case Ducted = 'DUCTED';
    case Vrf = 'VRF';
    case Central = 'CENTRAL';

    public function precisionClass(): PrecisionClass
    {
        return match ($this) {
            self::Ducted, self::Vrf, self::Central => PrecisionClass::Estimated,
            default => PrecisionClass::Calculated,
        };
    }

    public function labelKey(): string
    {
        return 'calculator-detailed.hvac.'.strtolower($this->value);
    }
}
