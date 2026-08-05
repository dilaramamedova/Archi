<?php

namespace App\Filament\Resources\AboutStatResource\Pages;

use App\Filament\Resources\AboutStatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAboutStats extends ListRecords
{
    protected static string $resource = AboutStatResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
