<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderPackageResource\Pages;

use App\Filament\Resources\TenderPackageResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTenderPackage extends CreateRecord
{
    protected static string $resource = TenderPackageResource::class;
}
