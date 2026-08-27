<?php

namespace App\Filament\Resources\CalculatorCoefficientResource\Pages;

use App\Filament\Resources\CalculatorCoefficientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorCoefficients extends ListRecords
{
    protected static string $resource = CalculatorCoefficientResource::class;

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
        return CalculatorCoefficientResource::listSubheading();
    }
}
