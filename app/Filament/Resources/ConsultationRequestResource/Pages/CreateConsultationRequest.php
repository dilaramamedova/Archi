<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsultationRequestResource\Pages;

use App\Filament\Resources\ConsultationRequestResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateConsultationRequest extends CreateRecord
{
    protected static string $resource = ConsultationRequestResource::class;
}
