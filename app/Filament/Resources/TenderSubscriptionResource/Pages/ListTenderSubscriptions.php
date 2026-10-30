<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderSubscriptionResource\Pages;

use App\Filament\Resources\TenderSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListTenderSubscriptions extends ListRecords
{
    protected static string $resource = TenderSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
