<?php

namespace App\Filament\Resources\ServiceIconResource\Pages;

use App\Filament\Resources\ServiceIconResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServiceIcons extends ListRecords
{
    protected static string $resource = ServiceIconResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
