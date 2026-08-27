<?php

namespace App\Filament\Resources\CalculatorSettingResource\Pages;

use App\Filament\Resources\CalculatorSettingResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorSettings extends ListRecords
{
    protected static string $resource = CalculatorSettingResource::class;

    /**
     * The screen explains itself where the admin actually lands — on the list, before
     * they have had to guess which row to open.
     */
    public function getSubheading(): string | Htmlable | null
    {
        return CalculatorSettingResource::listSubheading();
    }
}
