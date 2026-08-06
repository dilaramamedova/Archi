<?php

namespace App\Filament\Resources\AboutStepResource\Pages;

use App\Filament\Resources\AboutStepResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAboutSteps extends ListRecords
{
    protected static string $resource = AboutStepResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
