<?php

namespace App\Filament\Resources\CalculatorMaterialNormResource\Pages;

use App\Filament\Resources\CalculatorMaterialNormResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorMaterialNorm extends EditRecord
{
    protected static string $resource = CalculatorMaterialNormResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
