<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderPackageResource\Pages;

use App\Filament\Resources\TenderPackageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTenderPackages extends ListRecords
{
    protected static string $resource = TenderPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
