<?php

declare(strict_types=1);

namespace App\Filament\Resources\B2bCompanyResource\Pages;

use App\Filament\Resources\B2bCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditB2bCompany extends EditRecord
{
    protected static string $resource = B2bCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
