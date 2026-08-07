<?php

namespace App\Filament\Resources\FaqQuestionResource\Pages;

use App\Filament\Resources\FaqQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFaqQuestion extends EditRecord
{
    protected static string $resource = FaqQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
