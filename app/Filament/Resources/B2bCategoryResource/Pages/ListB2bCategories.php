<?php

declare(strict_types=1);

namespace App\Filament\Resources\B2bCategoryResource\Pages;

use App\Filament\Resources\B2bCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListB2bCategories extends ListRecords
{
    protected static string $resource = B2bCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
