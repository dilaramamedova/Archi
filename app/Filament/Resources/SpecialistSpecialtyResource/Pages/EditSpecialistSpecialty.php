<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecialistSpecialtyResource\Pages;

use App\Filament\Resources\SpecialistSpecialtyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditSpecialistSpecialty extends EditRecord
{
    protected static string $resource = SpecialistSpecialtyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
