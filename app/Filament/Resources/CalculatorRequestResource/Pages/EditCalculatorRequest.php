<?php

namespace App\Filament\Resources\CalculatorRequestResource\Pages;

use App\Enums\Calculator\RequestStatus;
use App\Filament\Resources\CalculatorRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalculatorRequest extends EditRecord
{
    protected static string $resource = CalculatorRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    /** Record who closed a request and when, so the history is answerable later. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $closed = in_array($data['status'] ?? null, [RequestStatus::Done->value, RequestStatus::Rejected->value], true);

        if ($closed && $this->record->handled_at === null) {
            $data['handled_at'] = now();
            $data['handled_by'] = auth()->id();
        }

        return $data;
    }
}
