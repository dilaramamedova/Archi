<?php

namespace App\Filament\Resources\AboutValueResource\Pages;

use App\Filament\Resources\AboutValueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAboutValues extends ListRecords
{
    protected static string $resource = AboutValueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
