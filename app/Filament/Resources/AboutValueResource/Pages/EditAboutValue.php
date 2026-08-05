<?php

namespace App\Filament\Resources\AboutValueResource\Pages;

use App\Filament\Resources\AboutValueResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAboutValue extends EditRecord
{
    protected static string $resource = AboutValueResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
