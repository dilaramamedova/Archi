<?php

namespace App\Filament\Resources\SaleBannerResource\Pages;

use App\Filament\Resources\SaleBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSaleBanners extends ListRecords
{
    protected static string $resource = SaleBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
