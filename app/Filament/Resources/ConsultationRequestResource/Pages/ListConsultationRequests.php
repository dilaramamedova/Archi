<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsultationRequestResource\Pages;

use App\Filament\Resources\ConsultationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListConsultationRequests extends ListRecords
{
    protected static string $resource = ConsultationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
