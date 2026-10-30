<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderSubscriptionResource\Pages;

use App\Filament\Resources\TenderSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateTenderSubscription extends CreateRecord
{
    protected static string $resource = TenderSubscriptionResource::class;
}
