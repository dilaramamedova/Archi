<?php

namespace App\Filament\Resources\SearchSynonymResource\Pages;

use App\Filament\Resources\SearchSynonymResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSearchSynonym extends EditRecord
{
    protected static string $resource = SearchSynonymResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
