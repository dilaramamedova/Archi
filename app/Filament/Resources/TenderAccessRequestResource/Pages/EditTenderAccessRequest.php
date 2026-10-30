<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderAccessRequestResource\Pages;

use App\Filament\Resources\TenderAccessRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTenderAccessRequest extends EditRecord
{
    protected static string $resource = TenderAccessRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TenderAccessRequestResource::grantAction()->after(fn () => $this->refreshFormData(['status', 'processed_at'])),
            Actions\DeleteAction::make(),
        ];
    }
}
