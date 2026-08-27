<?php

namespace App\Filament\Resources\CalculatorWorkRateResource\Pages;

use App\Filament\Resources\CalculatorWorkRateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorWorkRate extends EditRecord
{
    protected static string $resource = CalculatorWorkRateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
