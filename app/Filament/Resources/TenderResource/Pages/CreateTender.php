<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderResource\Pages;

use App\Filament\Resources\TenderResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTender extends CreateRecord
{
    protected static string $resource = TenderResource::class;
}
