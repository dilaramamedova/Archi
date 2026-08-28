<?php

namespace App\Filament\Resources;

use App\Enums\Calculator\RequestStatus;
use App\Enums\Calculator\RequestType;
use App\Filament\Resources\CalculatorRequestResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorRequest;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Everything a visitor sent from the finished estimate.
 *
 * The row shows the sum as it was frozen at that moment, not a recalculation — a
 * manager calling back must quote the figure the client actually saw.
 */
class CalculatorRequestResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorRequest::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox-arrow-down';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Kalkulyator müraciətləri';
    protected static ?string $modelLabel = 'müraciət';
    protected static ?string $pluralModelLabel = 'Kalkulyator müraciətləri';
    protected static ?int $navigationSort = 0;

    /** Top search bar: a manager looks a request up by the caller, not the id. */
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'phone', 'email'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'Telefon' => $record->phone,
            'Məbləğ' => $record->total_amount !== null ? number_format((float) $record->total_amount, 2).' AZN' : null,
        ]);
    }

    /** Unhandled requests are a to-do list, so the count belongs in the sidebar. */
    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::needsAttention()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function explanationParts(): array
    {
        return [
            'text' => 'Müştərilərin smetanın sonundakı düymələrdən göndərdiyi müraciətlər — '
                .'«Açar təslim sifariş et», «Yadda saxla», PDF və WhatsApp. '
                .'<strong>Açar təslim</strong> olanlar zəng gözləyir.',
            'shot' => 'requests',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('Müraciət')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Status')
                        ->options(fn () => collect(RequestStatus::cases())
                            ->mapWithKeys(fn (RequestStatus $s) => [$s->value => $s->label()])
                            ->all())
                        ->required(),

                    Forms\Components\TextInput::make('name')->label('Ad, soyad')->disabled(),
                    Forms\Components\TextInput::make('phone')->label('Telefon')->disabled(),
                    Forms\Components\TextInput::make('email')->label('E-poçt')->disabled(),
                    Forms\Components\Textarea::make('message')->label('Mesaj')->disabled()->rows(2)->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Smeta')
                ->description('Müştərinin gördüyü rəqəmlər — sonradan tarif dəyişsə də bunlar dəyişmir')
                ->schema([
                    Forms\Components\TextInput::make('total_amount')->label('Yekun məbləğ')->suffix('AZN')->disabled(),
                    Forms\Components\TextInput::make('area')->label('Sahə')->suffix('m²')->disabled(),
                    Forms\Components\TextInput::make('room_count')->label('Otaq sayı')->disabled(),
                    Forms\Components\TextInput::make('city_key')->label('Şəhər')->disabled(),
                ])
                ->columns(2),

            Section::make('Daxili qeyd')
                ->schema([
                    Forms\Components\Textarea::make('admin_note')
                        ->label('Qeyd')
                        ->rows(3)
                        ->helperText('Yalnız komanda görür. Müştəriyə göstərilmir.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Müştərilərin kalkulyatordan göndərdiyi müraciətlər. '
                .'«Açar təslim» olanlar zəng gözləyir — status sütununu yeniləyin.')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tarix')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Növ')
                    ->formatStateUsing(fn (RequestType $state) => $state->label())
                    ->badge()
                    ->color(fn (RequestType $state) => $state->needsCallback() ? 'warning' : 'gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Müştəri')
                    ->description(fn (CalculatorRequest $record): ?string => $record->phone)
                    ->placeholder('ad qeyd olunmayıb')
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Smeta')
                    ->money('AZN', locale: 'az')
                    ->sortable(),

                Tables\Columns\TextColumn::make('area')
                    ->label('Sahə')
                    ->suffix(' m²')
                    ->sortable(),

                Tables\Columns\SelectColumn::make('status')
                    ->label('Status')
                    ->options(fn () => collect(RequestStatus::cases())
                        ->mapWithKeys(fn (RequestStatus $s) => [$s->value => $s->label()])
                        ->all()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Növ')
                    ->options(fn () => collect(RequestType::cases())
                        ->mapWithKeys(fn (RequestType $t) => [$t->value => $t->label()])
                        ->all()),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn () => collect(RequestStatus::cases())
                        ->mapWithKeys(fn (RequestStatus $s) => [$s->value => $s->label()])
                        ->all()),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Actions\EditAction::make()->label('Aç')])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorRequests::route('/'),
            'edit' => Pages\EditCalculatorRequest::route('/{record}/edit'),
        ];
    }
}
