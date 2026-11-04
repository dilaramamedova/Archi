<?php

namespace App\Filament\Resources\CalculatorFinishResource\Pages;

use App\Filament\Resources\CalculatorFinishResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCalculatorFinish extends CreateRecord
{
    protected static string $resource = CalculatorFinishResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['finish_id'] = strtoupper(trim((string) ($data['finish_id'] ?? '')));

        return $data;
    }
}
