<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Actions\GrantTenderAccess;
use App\Enums\TenderAccessRequestStatus;
use App\Filament\Concerns\CachesNavigationBadge;
use App\Filament\Resources\TenderAccessRequestResource\Pages;
use App\Models\TenderAccessRequest;
use App\Models\TenderPackage;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class TenderAccessRequestResource extends Resource
{
    use CachesNavigationBadge;

    protected static ?string $model = TenderAccessRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static string|\UnitEnum|null $navigationGroup = 'Tenderlər';

    protected static ?string $navigationLabel = 'Qoşulma müraciətləri';

    protected static ?string $modelLabel = 'Qoşulma müraciəti';

    protected static ?string $pluralModelLabel = 'Qoşulma müraciətləri';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        // Requests come from the site; a manual grant is a TenderSubscription.
        return false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Müraciət')->schema([
                Forms\Components\Select::make('user_id')
                    ->label('İstifadəçi')
                    ->relationship('user', 'email')
                    ->disabled(),
                Forms\Components\Select::make('tender_package_id')
                    ->label('Seçilən paket')
                    ->relationship('package', 'name')
                    ->getOptionLabelFromRecordUsing(fn (TenderPackage $record): string => self::packageLabel($record)),
                Forms\Components\TextInput::make('full_name')->label('Ad və soyad')->required()->maxLength(120),
                Forms\Components\TextInput::make('company_name')->label('Şirkət')->maxLength(160),
                Forms\Components\TextInput::make('phone')->label('Telefon')->tel()->required()->maxLength(25),
                Forms\Components\TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                Forms\Components\Textarea::make('message')->label('Mesaj')->rows(4)->maxLength(2000)->columnSpanFull(),
            ])->columns(2),
            Section::make('İdarəetmə')->schema([
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(TenderAccessRequestStatus::options())
                    ->required(),
                Forms\Components\DateTimePicker::make('processed_at')->label('Baxılma tarixi')->timezone('Asia/Baku')->seconds(false),
                Forms\Components\Textarea::make('admin_note')->label('Admin qeydi')->rows(3)->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['package', 'user']))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('full_name')->label('Ad və soyad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('company_name')->label('Şirkət')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')->label('Telefon')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('package.name')->label('Paket')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (TenderAccessRequestStatus $state): string => $state->label())
                    ->color(fn (TenderAccessRequestStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('created_at')->label('Göndərilib')->dateTime('d.m.Y H:i', 'Asia/Baku')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(TenderAccessRequestStatus::options()),
            ])
            ->actions([
                self::grantAction(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    /** "Girişi aktivləşdir" — creates the subscription, closes the request, mails the user. */
    public static function grantAction(): Actions\Action
    {
        return Actions\Action::make('grantAccess')
            ->label('Girişi aktivləşdir')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->visible(fn (TenderAccessRequest $record): bool => $record->status->isOpen() && $record->user !== null)
            ->fillForm(fn (TenderAccessRequest $record): array => [
                'tender_package_id' => $record->tender_package_id,
                'months' => $record->package?->duration_months ?? 1,
            ])
            ->schema([
                Forms\Components\Select::make('tender_package_id')
                    ->label('Paket')
                    ->options(fn (): array => TenderPackage::query()->ordered()->get()
                        ->mapWithKeys(fn (TenderPackage $package): array => [$package->id => self::packageLabel($package)])
                        ->all())
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set): void {
                        if ($package = TenderPackage::query()->find($state)) {
                            $set('months', $package->duration_months);
                        }
                    }),
                Forms\Components\TextInput::make('months')
                    ->label('Müddət (ay)')
                    ->helperText('Aktiv giriş varsa, yeni müddət onun bitdiyi gündən başlayır.')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(36)
                    ->required(),
                Forms\Components\Textarea::make('note')->label('Qeyd (ödəniş məlumatı və s.)')->rows(2),
            ])
            ->action(function (TenderAccessRequest $record, array $data): void {
                $subscription = app(GrantTenderAccess::class)(
                    $record->user,
                    (int) $data['months'],
                    TenderPackage::query()->find($data['tender_package_id'] ?? null),
                    $record,
                    $data['note'] ?? null,
                );

                Notification::make()
                    ->title('Giriş aktivləşdirildi')
                    ->body('Bitmə tarixi: '.$subscription->ends_at->timezone('Asia/Baku')->format('d.m.Y').'. İstifadəçiyə email göndərildi.')
                    ->success()
                    ->send();
            });
    }

    public static function packageLabel(TenderPackage $package): string
    {
        return $package->getTranslation('name', 'az').' — '.$package->duration_months.' ay'
            .($package->priceLabel() ? ', '.$package->priceLabel() : '');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderAccessRequests::route('/'),
            'edit' => Pages\EditTenderAccessRequest::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return self::cachedBadge(
            'tender-access-requests:pending',
            fn () => TenderAccessRequest::query()->where('status', TenderAccessRequestStatus::Pending)->count(),
        );
    }
}
