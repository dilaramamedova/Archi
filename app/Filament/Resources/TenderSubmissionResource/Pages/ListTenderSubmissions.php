<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderSubmissionResource\Pages;

use App\Filament\Resources\TenderSubmissionResource;
use Filament\Resources\Pages\ListRecords;

final class ListTenderSubmissions extends ListRecords
{
    protected static string $resource = TenderSubmissionResource::class;
}
