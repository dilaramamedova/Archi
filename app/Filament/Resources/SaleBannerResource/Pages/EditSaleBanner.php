<?php

namespace App\Filament\Resources\SaleBannerResource\Pages;

use App\Filament\Resources\SaleBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaleBanner extends EditRecord
{
    protected static string $resource = SaleBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
