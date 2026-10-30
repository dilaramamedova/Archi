<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderSubscriptionResource\Pages;

use App\Filament\Resources\TenderSubscriptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTenderSubscription extends EditRecord
{
    protected static string $resource = TenderSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
