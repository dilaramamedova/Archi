<?php

namespace App\Filament\Resources\FaqQuestionResource\Pages;

use App\Filament\Resources\FaqQuestionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFaqQuestions extends ListRecords
{
    protected static string $resource = FaqQuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
