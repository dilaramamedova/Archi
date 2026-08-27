<?php

namespace App\Filament\Resources\CalculatorMaterialFallbackResource\Pages;

use App\Filament\Resources\CalculatorMaterialFallbackResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorMaterialFallback extends EditRecord
{
    protected static string $resource = CalculatorMaterialFallbackResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
