<?php

namespace App\Enums\Calculator;

/**
 * Master spec §5.1. An ESTIMATED line must never be presented with the same
 * confidence as a CALCULATED one — the UI shows a disclaimer for it.
 */
enum PrecisionClass: string
{
    case Exact = 'EXACT';
    case Calculated = 'CALCULATED';
    case Estimated = 'ESTIMATED';

    public function needsDisclaimer(): bool
    {
        return $this === self::Estimated;
    }
}
