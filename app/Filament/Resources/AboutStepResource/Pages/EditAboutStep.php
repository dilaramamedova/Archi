<?php

namespace App\Filament\Resources\AboutStepResource\Pages;

use App\Filament\Resources\AboutStepResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAboutStep extends EditRecord
{
    protected static string $resource = AboutStepResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
