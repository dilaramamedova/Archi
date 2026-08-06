<?php

declare(strict_types=1);

namespace App\Filament\Resources\PortfolioApprovalResource\Pages;

use App\Filament\Resources\PortfolioApprovalResource;
use Filament\Resources\Pages\ListRecords;

final class ListPortfolioApprovals extends ListRecords
{
    protected static string $resource = PortfolioApprovalResource::class;
}
