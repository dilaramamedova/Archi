<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TenderSubscriptionResource\Pages;
use App\Models\TenderPackage;
use App\Models\TenderSubscription;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Paid access periods. Normally created by «Girişi aktivləşdir» on an access
 * request; created here by hand to grant or correct access directly.
 */
final class TenderSubscriptionResource extends Resource
{
    protected static ?string $model = TenderSubscription::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Tenderlər';

    protected static ?string $navigationLabel = 'Abunəliklər';

    protected static ?string $modelLabel = 'Abunəlik';

    protected static ?string $pluralModelLabel = 'Abunəliklər';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\Select::make('user_id')
                ->label('İstifadəçi')
                ->relationship('user', 'email')
                ->getOptionLabelFromRecordUsing(fn ($record): string => trim($record->name.' — '.$record->email))
                ->searchable(['name', 'email', 'phone'])
                ->required(),
            Forms\Components\Select::make('tender_package_id')
                ->label('Paket')
                ->relationship('package', 'name')
                ->getOptionLabelFromRecordUsing(fn (TenderPackage $record): string => TenderAccessRequestResource::packageLabel($record)),
            Forms\Components\DateTimePicker::make('starts_at')
                ->label('Başlama')
                ->timezone('Asia/Baku')
                ->seconds(false)
                ->default(now())
                ->required(),
            Forms\Components\DateTimePicker::make('ends_at')
                ->label('Bitmə')
                ->timezone('Asia/Baku')
                ->seconds(false)
                ->default(now()->addMonth())
                ->after('starts_at')
                ->required(),
            Forms\Components\Textarea::make('note')->label('Qeyd')->rows(2)->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user', 'package']))
            ->columns([
                Tables\Columns\TextColumn::make('user.name')->label('İstifadəçi')->searchable(),
                Tables\Columns\TextColumn::make('user.email')->label('Email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('package.name')->label('Paket')->placeholder('—'),
                Tables\Columns\TextColumn::make('starts_at')->label('Başlama')->dateTime('d.m.Y', 'Asia/Baku')->sortable(),
                Tables\Columns\TextColumn::make('ends_at')->label('Bitmə')->dateTime('d.m.Y', 'Asia/Baku')->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->label('Vəziyyət')
                    ->badge()
                    ->state(fn (TenderSubscription $record): string => match (true) {
                        $record->isActive() => 'Aktiv',
                        $record->starts_at->isFuture() => 'Gözləyir',
                        default => 'Bitib',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Aktiv' => 'success',
                        'Gözləyir' => 'info',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('ends_at', 'desc')
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->label('Yalnız aktiv')
                    ->query(fn (Builder $query): Builder => $query->where('starts_at', '<=', now())->where('ends_at', '>', now())),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderSubscriptions::route('/'),
            'create' => Pages\CreateTenderSubscription::route('/create'),
            'edit' => Pages\EditTenderSubscription::route('/{record}/edit'),
        ];
    }
}
