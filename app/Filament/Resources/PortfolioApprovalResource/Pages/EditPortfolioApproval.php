<?php

declare(strict_types=1);

namespace App\Filament\Resources\PortfolioApprovalResource\Pages;

use App\Enums\PortfolioStatus;
use App\Filament\Resources\PortfolioApprovalResource;
use Filament\Resources\Pages\EditRecord;

final class EditPortfolioApproval extends EditRecord
{
    protected static string $resource = PortfolioApprovalResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['status'] ?? null) === PortfolioStatus::Approved->value) {
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        } else {
            $data['approved_at'] = null;
            $data['approved_by'] = null;
        }

        return $data;
    }
}
