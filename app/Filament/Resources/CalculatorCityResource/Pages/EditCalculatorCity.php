<?php

namespace App\Filament\Resources\CalculatorCityResource\Pages;

use App\Filament\Resources\CalculatorCityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorCity extends EditRecord
{
    protected static string $resource = CalculatorCityResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
