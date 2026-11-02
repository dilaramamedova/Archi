<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderAccessRequestResource\Pages;

use App\Enums\TenderAccessRequestStatus;
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

    /**
     * Closing a request by hand (approved / rejected) stamps "Baxılma tarixi" the
     * way «Girişi aktivləşdir» does, unless the admin typed a date themselves.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] ?? null;
        $status = $status instanceof TenderAccessRequestStatus ? $status : TenderAccessRequestStatus::tryFrom((string) $status);

        if ($status !== null && ! $status->isOpen() && blank($data['processed_at'] ?? null)) {
            $data['processed_at'] = now();
        }

        return $data;
    }
}
