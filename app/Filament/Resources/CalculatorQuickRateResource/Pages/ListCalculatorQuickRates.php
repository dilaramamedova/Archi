<?php

namespace App\Filament\Resources\CalculatorQuickRateResource\Pages;

use App\Filament\Resources\CalculatorQuickRateResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorQuickRates extends ListRecords
{
    protected static string $resource = CalculatorQuickRateResource::class;

    /**
     * The screen explains itself where the admin actually lands — on the list, before
     * they have had to guess which row to open.
     */
    public function getSubheading(): string | Htmlable | null
    {
        return CalculatorQuickRateResource::listSubheading();
    }
}
