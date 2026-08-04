<?php

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'İstifadəçilər';

    protected static ?string $modelLabel = 'İstifadəçi';

    protected static ?string $pluralModelLabel = 'İstifadəçilər';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) User::where('status', UserStatus::Pending)->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Schemas\Components\Section::make('Əsas məlumat')->schema([
                Forms\Components\TextInput::make('first_name')
                    ->label('Ad')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('last_name')
                    ->label('Soyad')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('email')
                    ->label('E-poçt')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')
                    ->label('Hesab növü')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()]))
                    ->required()
                    ->disabled(fn (?User $record) => $record !== null),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()]))
                    ->required(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Rədd səbəbi')
                    ->visible(fn (Schemas\Components\Utilities\Get $get) => $get('status') === 'rejected'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Soyad')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('E-poçt')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Telefon')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Növ')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state) => $state->label())
                    ->color(fn (UserRole $state) => match ($state) {
                        UserRole::Buyer => 'info',
                        UserRole::Seller => 'primary',
                        UserRole::Master => 'success',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state) => $state->label())
                    ->color(fn (UserStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Qeydiyyat tarixi')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Hesab növü')
                    ->options(collect(UserRole::cases())->mapWithKeys(fn ($r) => [$r->value => $r->label()])),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->label()])),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Təsdiq et')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record) => $record->status === UserStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('İstifadəçini təsdiq et')
                    ->modalDescription(fn (User $record) => "{$record->first_name} {$record->last_name} ({$record->email}) hesabını aktiv etmək istəyirsiniz?")
                    ->action(function (User $record) {
                        $record->update([
                            'status' => UserStatus::Active,
                            'approved_at' => now(),
                        ]);
                        Notification::make()
                            ->title('İstifadəçi təsdiqləndi')
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('reject')
                    ->label('Rədd et')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (User $record) => $record->status === UserStatus::Pending)
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rədd səbəbi')
                            ->required(),
                    ])
                    ->action(function (User $record, array $data) {
                        $record->update([
                            'status' => UserStatus::Rejected,
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                        Notification::make()
                            ->title('İstifadəçi rədd edildi')
                            ->danger()
                            ->send();
                    }),
                Actions\Action::make('block')
                    ->label('Blokla')
                    ->icon('heroicon-o-no-symbol')
                    ->color('gray')
                    ->visible(fn (User $record) => $record->status === UserStatus::Active)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update(['status' => UserStatus::Blocked]);
                        Notification::make()
                            ->title('İstifadəçi bloklandı')
                            ->warning()
                            ->send();
                    }),
                Actions\Action::make('unblock')
                    ->label('Bloku aç')
                    ->icon('heroicon-o-lock-open')
                    ->color('success')
                    ->visible(fn (User $record) => $record->status === UserStatus::Blocked)
                    ->requiresConfirmation()
                    ->action(function (User $record) {
                        $record->update([
                            'status' => UserStatus::Active,
                            'approved_at' => now(),
                        ]);
                        Notification::make()
                            ->title('İstifadəçi bloku açıldı')
                            ->success()
                            ->send();
                    }),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkAction::make('approve_selected')
                    ->label('Seçilənləri təsdiq et')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        $records->each(fn (User $r) => $r->update([
                            'status' => UserStatus::Active,
                            'approved_at' => now(),
                        ]));
                        Notification::make()
                            ->title($records->count() . ' istifadəçi təsdiqləndi')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist->schema([
            Schemas\Components\Section::make('Əsas məlumat')->schema([
                Infolists\Components\TextEntry::make('first_name')->label('Ad'),
                Infolists\Components\TextEntry::make('last_name')->label('Soyad'),
                Infolists\Components\TextEntry::make('email')->label('E-poçt'),
                Infolists\Components\TextEntry::make('phone')->label('Telefon'),
                Infolists\Components\TextEntry::make('role')
                    ->label('Hesab növü')
                    ->formatStateUsing(fn (UserRole $state) => $state->label()),
                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state) => $state->label())
                    ->color(fn (UserStatus $state) => $state->color()),
                Infolists\Components\TextEntry::make('created_at')
                    ->label('Qeydiyyat tarixi')
                    ->dateTime('d.m.Y H:i'),
                Infolists\Components\TextEntry::make('approved_at')
                    ->label('Təsdiq tarixi')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Hələ təsdiqlənməyib'),
                Infolists\Components\TextEntry::make('rejection_reason')
                    ->label('Rədd səbəbi')
                    ->placeholder('—')
                    ->visible(fn (User $record) => $record->status === UserStatus::Rejected),
            ])->columns(2),

            Schemas\Components\Section::make('Satıcı profili')
                ->visible(fn (User $record) => $record->role === UserRole::Seller && $record->sellerProfile)
                ->schema([
                    Infolists\Components\TextEntry::make('sellerProfile.brand_name')->label('Marka adı'),
                    Infolists\Components\TextEntry::make('sellerProfile.legal_name')->label('Hüquqi ad'),
                    Infolists\Components\TextEntry::make('sellerProfile.tax_id')->label('VÖEN'),
                    Infolists\Components\TextEntry::make('sellerProfile.city')->label('Şəhər'),
                    Infolists\Components\TextEntry::make('sellerProfile.address')->label('Ünvan'),
                    Infolists\Components\TextEntry::make('sellerProfile.about')->label('Haqqında')->columnSpanFull(),
                ])->columns(2),

            Schemas\Components\Section::make('Usta profili')
                ->visible(fn (User $record) => $record->role === UserRole::Master && $record->specialistProfile)
                ->schema([
                    Infolists\Components\TextEntry::make('specialistProfile.craft')->label('İxtisas'),
                    Infolists\Components\TextEntry::make('specialistProfile.city')->label('Şəhər'),
                    Infolists\Components\TextEntry::make('specialistProfile.experience_years')->label('Təcrübə (il)'),
                    Infolists\Components\TextEntry::make('specialistProfile.phone')->label('Telefon'),
                    Infolists\Components\TextEntry::make('specialistProfile.about')->label('Haqqında')->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
