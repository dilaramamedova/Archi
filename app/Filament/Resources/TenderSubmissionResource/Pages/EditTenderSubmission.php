<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenderSubmissionResource\Pages;

use App\Enums\TenderSubmissionStatus;
use App\Filament\Resources\TenderSubmissionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditTenderSubmission extends EditRecord
{
    protected static string $resource = TenderSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            TenderSubmissionResource::convertAction(),
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Opening a new submission marks it «Baxılır»; closing it by hand stamps the time.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (($data['status'] ?? null) === TenderSubmissionStatus::New->value || ($data['status'] ?? null) === TenderSubmissionStatus::New) {
            $this->record->forceFill(['status' => TenderSubmissionStatus::InReview])->save();
            $data['status'] = TenderSubmissionStatus::InReview->value;
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $status = $data['status'] ?? null;
        $status = $status instanceof TenderSubmissionStatus ? $status : TenderSubmissionStatus::tryFrom((string) $status);

        if ($status !== null && ! $status->isOpen() && $this->record->processed_at === null) {
            $data['processed_at'] = now();
        }

        return $data;
    }
}
