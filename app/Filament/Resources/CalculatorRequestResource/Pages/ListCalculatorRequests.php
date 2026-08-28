<?php

namespace App\Filament\Resources\CalculatorRequestResource\Pages;

use App\Filament\Resources\CalculatorRequestResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListCalculatorRequests extends ListRecords
{
    protected static string $resource = CalculatorRequestResource::class;

    public function getSubheading(): string | Htmlable | null
    {
        return CalculatorRequestResource::listSubheading();
    }
}
