<?php

declare(strict_types=1);

namespace App\Filament\Resources\B2bCompanyResource\Pages;

use App\Filament\Resources\B2bCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListB2bCompanies extends ListRecords
{
    protected static string $resource = B2bCompanyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
