<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderCategoryResource\Pages;

use App\Filament\Resources\TenderCategoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTenderCategory extends CreateRecord
{
    protected static string $resource = TenderCategoryResource::class;
}
