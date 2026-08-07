<?php

namespace App\Filament\Resources\FaqTopicResource\Pages;

use App\Filament\Resources\FaqTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqTopics extends ListRecords
{
    protected static string $resource = FaqTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
