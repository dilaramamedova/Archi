<?php

namespace App\Filament\Resources\CalculatorWorkRateResource\Pages;

use App\Filament\Resources\CalculatorWorkRateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorWorkRates extends ListRecords
{
    protected static string $resource = CalculatorWorkRateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    /**
     * The screen explains itself where the admin actually lands — on the list, before
     * they have had to guess which row to open.
     */
    public function getSubheading(): string | Htmlable | null
    {
        return CalculatorWorkRateResource::listSubheading();
    }
}
