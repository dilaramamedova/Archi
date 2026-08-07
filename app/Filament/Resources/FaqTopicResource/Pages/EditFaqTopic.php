<?php

namespace App\Filament\Resources\FaqTopicResource\Pages;

use App\Filament\Resources\FaqTopicResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaqTopic extends EditRecord
{
    protected static string $resource = FaqTopicResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
