<?php

namespace App\Filament\Resources\ServiceIconResource\Pages;

use App\Filament\Resources\ServiceIconResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServiceIcon extends EditRecord
{
    protected static string $resource = ServiceIconResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
