<?php

namespace App\Filament\Resources\CalculatorFinishResource\Pages;

use App\Filament\Resources\CalculatorFinishResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorFinish extends EditRecord
{
    protected static string $resource = CalculatorFinishResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
