<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderCategoryResource\Pages;

use App\Filament\Resources\TenderCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTenderCategory extends EditRecord
{
    protected static string $resource = TenderCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
