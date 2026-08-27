<?php

namespace App\Filament\Resources\CalculatorCoefficientResource\Pages;

use App\Filament\Resources\CalculatorCoefficientResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorCoefficient extends EditRecord
{
    protected static string $resource = CalculatorCoefficientResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
