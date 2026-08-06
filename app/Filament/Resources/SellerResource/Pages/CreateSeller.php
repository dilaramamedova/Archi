<?php

namespace App\Filament\Resources\SellerResource\Pages;

use App\Filament\Resources\SellerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSeller extends CreateRecord
{
    protected static string $resource = SellerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return SellerResource::prepareUserData($data);
    }
}
