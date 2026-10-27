<?php

declare(strict_types=1);

namespace App\Filament\Resources\DictionaryRequestResource\Pages;

use App\Filament\Resources\DictionaryRequestResource;
use Filament\Resources\Pages\ListRecords;

class ListDictionaryRequests extends ListRecords
{
    protected static string $resource = DictionaryRequestResource::class;
}
