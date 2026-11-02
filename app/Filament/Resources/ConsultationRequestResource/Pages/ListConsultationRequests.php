<?php

declare(strict_types=1);

namespace App\Filament\Resources\ConsultationRequestResource\Pages;

use App\Enums\ConsultationRequestSource;
use App\Enums\ConsultationRequestStatus;
use App\Filament\Resources\ConsultationRequestResource;
use App\Models\ConsultationRequest;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

final class ListConsultationRequests extends ListRecords
{
    protected static string $resource = ConsultationRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('Hamısı')];

        foreach (ConsultationRequestSource::cases() as $source) {
            $pending = ConsultationRequest::query()
                ->where('source', $source)
                ->where('status', ConsultationRequestStatus::Pending)
                ->count();

            $tabs[$source->value] = Tab::make($source->label())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('source', $source))
                ->badge($pending ?: null)
                ->badgeColor('warning');
        }

        return $tabs;
    }
}
