<?php

namespace App\Filament\Resources\SpecialistResource\Pages;

use App\Filament\Resources\SpecialistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpecialist extends CreateRecord
{
    protected static string $resource = SpecialistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SpecialistResource::prepareUserData($data);
    }
}
