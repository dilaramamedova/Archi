<?php

namespace App\Filament\Resources\CalculatorRoomTypeResource\Pages;

use App\Filament\Resources\CalculatorRoomTypeResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorRoomTypes extends ListRecords
{
    protected static string $resource = CalculatorRoomTypeResource::class;

    public function getSubheading(): string | Htmlable | null
    {
        return CalculatorRoomTypeResource::listSubheading();
    }
}
