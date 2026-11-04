<?php

namespace App\Filament\Resources\CalculatorFinishResource\Pages;

use App\Filament\Resources\CalculatorFinishResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorFinishes extends ListRecords
{
    protected static string $resource = CalculatorFinishResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Yeni üzlük')];
    }

    /** The screen explains itself where the admin actually lands — on the list. */
    public function getSubheading(): string | Htmlable | null
    {
        return CalculatorFinishResource::listSubheading();
    }
}
