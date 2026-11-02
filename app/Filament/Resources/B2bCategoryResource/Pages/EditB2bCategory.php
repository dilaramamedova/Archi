<?php

declare(strict_types=1);

namespace App\Filament\Resources\B2bCategoryResource\Pages;

use App\Filament\Resources\B2bCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditB2bCategory extends EditRecord
{
    protected static string $resource = B2bCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
