<?php

namespace App\Filament\Resources\AboutStatResource\Pages;

use App\Filament\Resources\AboutStatResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAboutStat extends EditRecord
{
    protected static string $resource = AboutStatResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
