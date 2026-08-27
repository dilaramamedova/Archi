<?php

namespace App\Filament\Resources\CalculatorCityResource\Pages;

use App\Filament\Resources\CalculatorCityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorCities extends ListRecords
{
    protected static string $resource = CalculatorCityResource::class;

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
        return CalculatorCityResource::listSubheading();
    }
}
