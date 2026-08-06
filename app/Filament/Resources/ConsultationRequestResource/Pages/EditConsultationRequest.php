<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsultationRequestResource\Pages;

use App\Filament\Resources\ConsultationRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditConsultationRequest extends EditRecord
{
    protected static string $resource = ConsultationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
