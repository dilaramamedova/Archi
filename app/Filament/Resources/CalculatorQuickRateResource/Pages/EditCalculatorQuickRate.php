<?php

namespace App\Filament\Resources\CalculatorQuickRateResource\Pages;

use App\Filament\Resources\CalculatorQuickRateResource;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorQuickRate extends EditRecord
{
    protected static string $resource = CalculatorQuickRateResource::class;

    /** TZ §34: who changed a price and when has to be recoverable. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();

        return $data;
    }
}
