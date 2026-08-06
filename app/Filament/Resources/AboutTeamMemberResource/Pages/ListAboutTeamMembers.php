<?php

namespace App\Filament\Resources\AboutTeamMemberResource\Pages;

use App\Filament\Resources\AboutTeamMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAboutTeamMembers extends ListRecords
{
    protected static string $resource = AboutTeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
