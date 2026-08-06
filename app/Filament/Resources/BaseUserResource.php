<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Enums\UserStatus;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class BaseUserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'İstifadəçilər';

    abstract public static function getManagedRole(): UserRole;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'sellerProfile',
                'specialistProfile.services',
                'specialistProfile.specialty',
                'specialistProfile.schedules',
                'specialistProfile.portfolioItems',
            ])
            ->withRole(static::getManagedRole());
    }

    public static function getNavigationBadge(): ?string
    {
        $count = User::query()
            ->withRole(static::getManagedRole())
            ->where('status', UserStatus::Pending)
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        $components = [
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
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('phone')
                    ->label('Telefon')
                    ->tel()
                    ->maxLength(30)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('password')
                    ->label('Şifrə')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->minLength(8)
                    ->confirmed(),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Şifrənin təkrarı')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
                Forms\Components\Hidden::make('role')
                    ->default(static::getManagedRole()->value),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(self::statusOptions())
                    ->default(static::getManagedRole() === UserRole::Admin ? UserStatus::Active->value : UserStatus::Pending->value)
                    ->required()
                    ->disabled(fn (?User $record): bool => $record !== null
                        && static::getManagedRole() === UserRole::Admin
                        && ! static::canChangeStatus($record))
                    ->live(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->label('Rədd səbəbi')
                    ->required(fn (Schemas\Components\Utilities\Get $get): bool => $get('status') === UserStatus::Rejected->value)
                    ->visible(fn (Schemas\Components\Utilities\Get $get): bool => $get('status') === UserStatus::Rejected->value)
                    ->columnSpanFull(),
            ])->columns(2),
        ];

        if (static::getManagedRole() === UserRole::Seller) {
            $components[] = static::sellerProfileSection();
        }

        if (static::getManagedRole() === UserRole::Master) {
            $components[] = static::specialistProfileSection();
        }

        return $schema->components($components);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Ad, soyad')->searchable(['first_name', 'last_name', 'name'])->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-poçt')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('created_at')->label('Qeydiyyat tarixi')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
            ])
            ->recordActions([
                static::approveAction(),
                static::rejectAction(),
                static::blockAction(),
                static::unblockAction(),
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $components = [
            Schemas\Components\Section::make('Əsas məlumat')->schema([
                Infolists\Components\TextEntry::make('first_name')->label('Ad'),
                Infolists\Components\TextEntry::make('last_name')->label('Soyad'),
                Infolists\Components\TextEntry::make('email')->label('E-poçt'),
                Infolists\Components\TextEntry::make('phone')->label('Telefon')->placeholder('—'),
                Infolists\Components\TextEntry::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => $state->color()),
                Infolists\Components\TextEntry::make('created_at')->label('Qeydiyyat tarixi')->dateTime('d.m.Y H:i'),
                Infolists\Components\TextEntry::make('approved_at')->label('Təsdiq tarixi')->dateTime('d.m.Y H:i')->placeholder('—'),
                Infolists\Components\TextEntry::make('rejection_reason')->label('Rədd səbəbi')->placeholder('—')->columnSpanFull(),
            ])->columns(2),
        ];

        if (static::getManagedRole() === UserRole::Seller) {
            $components[] = Schemas\Components\Section::make('Satıcı profili')->schema([
                Infolists\Components\TextEntry::make('sellerProfile.brand_name')->label('Brend adı')->placeholder('—'),
                Infolists\Components\TextEntry::make('sellerProfile.legal_name')->label('Hüquqi ad')->placeholder('—'),
                Infolists\Components\TextEntry::make('sellerProfile.tax_id')->label('VÖEN')->placeholder('—'),
                Infolists\Components\TextEntry::make('sellerProfile.city')->label('Şəhər')->placeholder('—'),
                Infolists\Components\TextEntry::make('sellerProfile.address')->label('Ünvan')->placeholder('—'),
                Infolists\Components\TextEntry::make('sellerProfile.about')->label('Haqqında')->placeholder('—')->columnSpanFull(),
            ])->columns(2);
        }

        if (static::getManagedRole() === UserRole::Master) {
            $components[] = Schemas\Components\Section::make('Usta / mütəxəssis profili')->schema([
                Infolists\Components\TextEntry::make('specialistProfile.specialty.name')->label('İxtisas')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.city')->label('Şəhər')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.experience_years')->label('Təcrübə (il)')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.phone')->label('Əlaqə telefonu')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.whatsapp')->label('WhatsApp')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.skills')->label('Bacarıqlar')->badge()->placeholder('—')->columnSpanFull(),
                Infolists\Components\TextEntry::make('specialistProfile.about')->label('Haqqında')->placeholder('—')->columnSpanFull(),
                Infolists\Components\TextEntry::make('specialistProfile.available_slots')->label('Boş slotlar')->placeholder('0'),
                Infolists\Components\IconEntry::make('specialistProfile.is_on_vacation')->label('Məzuniyyət rejimi')->boolean(),
                Infolists\Components\RepeatableEntry::make('specialistProfile.services')
                    ->label('Xidmətlər')
                    ->schema([
                        Infolists\Components\TextEntry::make('name')->label('Xidmət'),
                        Infolists\Components\TextEntry::make('description')->label('Təsvir')->placeholder('—'),
                        Infolists\Components\TextEntry::make('price')->label('Qiymət')->money('AZN'),
                        Infolists\Components\TextEntry::make('unit')->label('Vahid'),
                        Infolists\Components\IconEntry::make('is_active')->label('Aktiv')->boolean(),
                    ])->columns(5)->columnSpanFull(),
                Infolists\Components\RepeatableEntry::make('specialistProfile.schedules')
                    ->label('İş qrafiki')
                    ->schema([
                        Infolists\Components\TextEntry::make('day_of_week')->label('Həftənin günü'),
                        Infolists\Components\TextEntry::make('start_time')->label('Başlama')->placeholder('—'),
                        Infolists\Components\TextEntry::make('end_time')->label('Bitmə')->placeholder('—'),
                        Infolists\Components\IconEntry::make('is_day_off')->label('İstirahət')->boolean(),
                    ])->columns(4)->columnSpanFull(),
                Infolists\Components\RepeatableEntry::make('specialistProfile.portfolioItems')
                    ->label('Portfolio')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')->label('Başlıq')->placeholder('—'),
                        Infolists\Components\TextEntry::make('image_path')->label('Şəkil yolu'),
                        Infolists\Components\IconEntry::make('is_cover')->label('Örtük')->boolean(),
                    ])->columns(3)->columnSpanFull(),
            ])->columns(2);
        }

        return $schema->components($components);
    }

    public static function canDelete(Model $record): bool
    {
        if (! $record instanceof User || $record->role !== static::getManagedRole()) {
            return false;
        }

        if (static::getManagedRole() !== UserRole::Admin) {
            return true;
        }

        return auth('admin')->id() !== $record->getKey()
            && ! ($record->status === UserStatus::Active && static::activeAdminCount() <= 1);
    }

    public static function prepareUserData(array $data): array
    {
        $data['name'] = trim("{$data['first_name']} {$data['last_name']}");
        $data['role'] = static::getManagedRole();

        if (($data['status'] ?? null) === UserStatus::Active->value && empty($data['approved_at'])) {
            $data['approved_at'] = now();
        }

        if (($data['status'] ?? null) !== UserStatus::Rejected->value) {
            $data['rejection_reason'] = null;
        }

        return $data;
    }

    protected static function sellerProfileSection(): Schemas\Components\Section
    {
        return Schemas\Components\Section::make('Satıcı profili')
            ->relationship('sellerProfile')
            ->schema([
                Forms\Components\TextInput::make('brand_name')->label('Brend adı')->required()->maxLength(255),
                Forms\Components\TextInput::make('legal_name')->label('Hüquqi ad')->maxLength(255),
                Forms\Components\TextInput::make('tax_id')->label('VÖEN')->maxLength(50),
                Forms\Components\Toggle::make('tax_id_verified')->label('VÖEN təsdiqlənib'),
                Forms\Components\TextInput::make('city')->label('Şəhər')->maxLength(100),
                Forms\Components\TextInput::make('address')->label('Ünvan')->maxLength(255),
                Forms\Components\TextInput::make('contact_person')->label('Əlaqələndirici şəxs')->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')->label('Əlaqə telefonu')->tel()->maxLength(30),
                Forms\Components\Textarea::make('about')->label('Haqqında')->columnSpanFull(),
            ])->columns(2);
    }

    protected static function specialistProfileSection(): Schemas\Components\Section
    {
        return Schemas\Components\Section::make('Usta / mütəxəssis profili')
            ->relationship('specialistProfile')
            ->schema([
                Forms\Components\Select::make('specialist_specialty_id')->label('İxtisas')
                    ->relationship('specialty', 'name', fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('sort_order'))
                    ->searchable()->preload()->required(),
                Forms\Components\TextInput::make('experience_years')->label('Təcrübə (il)')->numeric()->minValue(0)->maxValue(80),
                Forms\Components\TextInput::make('city')->label('Şəhər')->required()->maxLength(100),
                Forms\Components\TextInput::make('phone')->label('Əlaqə telefonu')->tel()->maxLength(30),
                Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->tel()->maxLength(30),
                Forms\Components\TextInput::make('avatar_path')->label('Avatar yolu')->maxLength(255),
                Forms\Components\TagsInput::make('skills')->label('Bacarıqlar')->columnSpanFull(),
                Forms\Components\Textarea::make('about')->label('Haqqında')->columnSpanFull(),
                Forms\Components\TextInput::make('available_slots')->label('Həftəlik boş slot')->numeric()->minValue(0)->maxValue(20),
                Forms\Components\Toggle::make('is_on_vacation')->label('Məzuniyyət rejimi'),
                Forms\Components\Toggle::make('is_featured')->label('Seçilmiş mütəxəssis'),
                Forms\Components\Toggle::make('show_on_homepage')
                    ->label('Ana səhifədə göstər')
                    ->helperText('Yalnız seçilmiş mütəxəssislər üçün keçərlidir — ana səhifə bölməsində görünəcək.'),
                Forms\Components\Toggle::make('show_in_header')->label('Header-də göstər'),
            ])->columns(2);
    }

    protected static function approveAction(): Actions\Action
    {
        return Actions\Action::make('approve')
            ->label('Təsdiq et')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->visible(fn (User $record): bool => $record->status === UserStatus::Pending && static::canChangeStatus($record))
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $record->approve();
                Notification::make()->title('Hesab təsdiqləndi')->success()->send();
            });
    }

    protected static function rejectAction(): Actions\Action
    {
        return Actions\Action::make('reject')
            ->label('Rədd et')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (User $record): bool => $record->status === UserStatus::Pending && static::canChangeStatus($record))
            ->schema([
                Forms\Components\Textarea::make('rejection_reason')->label('Rədd səbəbi')->required(),
            ])
            ->action(function (User $record, array $data): void {
                $record->reject($data['rejection_reason']);
                Notification::make()->title('Hesab rədd edildi')->danger()->send();
            });
    }

    protected static function blockAction(): Actions\Action
    {
        return Actions\Action::make('block')
            ->label('Blokla')
            ->icon('heroicon-o-no-symbol')
            ->color('gray')
            ->visible(fn (User $record): bool => $record->status === UserStatus::Active && static::canChangeStatus($record))
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $record->block();
                Notification::make()->title('Hesab bloklandı')->warning()->send();
            });
    }

    protected static function unblockAction(): Actions\Action
    {
        return Actions\Action::make('unblock')
            ->label('Bloku aç')
            ->icon('heroicon-o-lock-open')
            ->color('success')
            ->visible(fn (User $record): bool => $record->status === UserStatus::Blocked)
            ->requiresConfirmation()
            ->action(function (User $record): void {
                $record->approve();
                Notification::make()->title('Hesabın bloku açıldı')->success()->send();
            });
    }

    protected static function canChangeStatus(User $record): bool
    {
        if (static::getManagedRole() !== UserRole::Admin) {
            return true;
        }

        return auth('admin')->id() !== $record->getKey()
            && ! ($record->status === UserStatus::Active && static::activeAdminCount() <= 1);
    }

    protected static function activeAdminCount(): int
    {
        return User::query()->withRole(UserRole::Admin)->where('status', UserStatus::Active)->count();
    }

    private static function statusOptions(): array
    {
        return collect(UserStatus::cases())
            ->mapWithKeys(fn (UserStatus $status): array => [$status->value => $status->label()])
            ->all();
    }
}
