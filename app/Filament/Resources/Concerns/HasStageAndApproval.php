<?php

namespace App\Filament\Resources\Concerns;

use Filament\Actions\BulkAction;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * The two columns the calculator TZ of 17.11.2026 added to every price table:
 *
 *   stage        White Box or the finishing stage — which block of the estimate the
 *                row lands in (TZ §2);
 *   approved_at  «Təsdiqlənib» — Archi has checked this price. Every seeded price
 *                starts as a DRAFT; the filter shows what is still left (TZ §38).
 */
trait HasStageAndApproval
{
    public static function stageOptions(bool $withLast = false): array
    {
        return [
            'WHITE_BOX' => 'White Box (qaralama)',
            'FINISH' => 'Bitirmə mərhələsi',
        ] + ($withLast ? ['LAST' => 'Sonuncu seçilmiş mərhələ (logistika, təmizlik)'] : []);
    }

    public static function stageLabel(?string $stage): string
    {
        return match ($stage) {
            'WHITE_BOX' => 'White Box',
            'FINISH' => 'Bitirmə',
            'LAST' => 'Sonuncu mərhələ',
            null, '' => 'Mərhələdən kənar',
            default => $stage,
        };
    }

    public static function stageSelect(string $field, string $label, string $helper, bool $withLast = false): Forms\Components\Select
    {
        return Forms\Components\Select::make($field)
            ->label($label)
            ->options(self::stageOptions($withLast))
            ->placeholder('Mərhələdən kənar (dizayn, nəzarət)')
            ->helperText($helper);
    }

    public static function stageColumn(string $field = 'stage', string $label = 'Mərhələ'): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($field)
            ->label($label)
            ->formatStateUsing(fn ($state) => self::stageLabel($state))
            ->placeholder('Mərhələdən kənar')
            ->badge()
            ->color(fn ($state) => match ($state) {
                'WHITE_BOX' => 'gray',
                'FINISH' => 'warning',
                default => 'info',
            })
            ->sortable();
    }

    public static function stageFilter(string $field = 'stage', bool $withLast = false): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make($field)
            ->label('Mərhələ')
            ->options(self::stageOptions($withLast));
    }

    /**
     * «Təsdiqlənib» as a yes/no switch with its date. Switching it on stamps today;
     * the date stays editable for a price that was checked earlier.
     *
     * @return array<int, \Filament\Forms\Components\Field>
     */
    public static function approvalFields(): array
    {
        return [
            Forms\Components\Toggle::make('is_approved')
                ->label('Qiymət təsdiqlənib')
                ->helperText('Archi bu qiyməti yoxlayıb təsdiq edibsə, yandırın. Söndürülü qiymət «DRAFT» sayılır — '
                    .'siyahıda «Təsdiq» filtri ilə hələ yoxlanmamış qiymətləri tapa bilərsiniz.')
                ->formatStateUsing(fn (?Model $record) => filled($record?->approved_at))
                ->dehydrated(false)
                ->live()
                ->afterStateUpdated(fn ($state, Set $set) => $set('approved_at', $state ? now()->format('Y-m-d H:i:s') : null)),

            Forms\Components\DateTimePicker::make('approved_at')
                ->label('Təsdiq tarixi')
                ->seconds(false)
                ->helperText('Boş = təsdiqlənməyib.'),
        ];
    }

    public static function approvalColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('approved_at')
            ->label('Təsdiq')
            ->date('d.m.Y')
            ->placeholder('DRAFT')
            ->badge()
            ->color(fn ($state) => $state ? 'success' : 'gray')
            ->sortable();
    }

    public static function approvalFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('approved')
            ->label('Təsdiq')
            ->placeholder('Hamısı')
            ->trueLabel('Təsdiqlənmiş')
            ->falseLabel('Təsdiqlənməmiş (DRAFT)')
            ->queries(
                true: fn (Builder $query) => $query->whereNotNull('approved_at'),
                false: fn (Builder $query) => $query->whereNull('approved_at'),
            );
    }

    /** Approve the selected prices in one go — checking 125 rates one by one is no one's job. */
    public static function approveBulkAction(): BulkAction
    {
        return BulkAction::make('approve')
            ->label('Təsdiqlə')
            ->icon('heroicon-o-check-badge')
            ->requiresConfirmation()
            ->modalDescription('Seçilmiş qiymətlər bu günün tarixi ilə təsdiqlənmiş sayılacaq.')
            ->action(fn (Collection $records) => $records->each(fn (Model $record) => $record->forceFill(['approved_at' => now()])->save()))
            ->deselectRecordsAfterCompletion();
    }
}
