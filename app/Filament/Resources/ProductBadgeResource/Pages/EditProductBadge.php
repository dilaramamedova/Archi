<?php

namespace App\Filament\Resources\ProductBadgeResource\Pages;

use App\Filament\Resources\ProductBadgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductBadge extends EditRecord
{
    protected static string $resource = ProductBadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
