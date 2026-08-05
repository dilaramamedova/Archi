<?php

namespace App\Filament\Resources\ProductBadgeResource\Pages;

use App\Filament\Resources\ProductBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductBadges extends ListRecords
{
    protected static string $resource = ProductBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
