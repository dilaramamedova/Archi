<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\City;
use App\Enums\SpecialistCategory;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Filament\Concerns\CachesNavigationBadge;
use App\Models\SpecialistSpecialty;
use App\Models\User;
use App\Notifications\AccountApprovedNotification;
use App\Notifications\AccountRejectedNotification;
use App\Support\SpecialistDetails;
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
    use CachesNavigationBadge;

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
        return static::cachedBadge(
            'users:pending:'.static::getManagedRole()->value,
            fn () => User::query()
                ->withRole(static::getManagedRole())
                ->where('status', UserStatus::Pending)
                ->count(),
        );
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
                    // Accounts are no longer vetted on creation — matching public
                    // registration, which signs every role in immediately. The admin can
                    // still pick pending/rejected/blocked from the select.
                    ->default(UserStatus::Active->value)
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
        $isSpecialist = static::getManagedRole() === UserRole::Master;

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Ad, soyad')->searchable(['first_name', 'last_name', 'name'])->sortable(),
                // Specialists only: which kind (Usta, Briqadir, Makler, …) — the specialty's
                // category, the same value the public ?type= filter reads.
                Tables\Columns\TextColumn::make('specialistProfile.specialty.category')
                    ->label('Növ')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof SpecialistCategory ? $state->singularLabel() : (string) $state)
                    ->color(fn ($state): string => $state instanceof SpecialistCategory && $state->hasOwnKind() ? 'warning' : 'gray')
                    ->placeholder('—')
                    ->visible($isSpecialist),
                Tables\Columns\TextColumn::make('email')->label('E-poçt')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('phone')->label('Telefon')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus $state): string => $state->label())
                    ->color(fn (UserStatus $state): string => $state->color()),
                Tables\Columns\TextColumn::make('created_at')->label('Qeydiyyat tarixi')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->filters(array_values(array_filter([
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
                $isSpecialist
                    ? Tables\Filters\SelectFilter::make('kind')
                        ->label('Növ')
                        ->options(SpecialistCategory::singularOptions())
                        ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                            ? $query->whereHas('specialistProfile.specialty', fn (Builder $q): Builder => $q->where('category', $data['value']))
                            : $query)
                    : null,
            ])))
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
                Infolists\Components\TextEntry::make('sellerProfile.city')->label('Şəhər')->formatStateUsing(fn (?string $state): ?string => City::display($state))->placeholder('—'),
                Infolists\Components\TextEntry::make('sellerProfile.address')->label('Ünvan')->placeholder('—'),
                Infolists\Components\TextEntry::make('sellerProfile.about')->label('Haqqında')->placeholder('—')->columnSpanFull(),
            ])->columns(2);
        }

        if (static::getManagedRole() === UserRole::Master) {
            $components[] = Schemas\Components\Section::make('Usta / mütəxəssis profili')->schema([
                Infolists\Components\TextEntry::make('specialistProfile.specialty.name')->label('İxtisas')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.city')->label('Şəhər')->formatStateUsing(fn (?string $state): ?string => City::display($state))->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.experience_years')->label('Təcrübə (il)')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.phone')->label('Əlaqə telefonu')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.whatsapp')->label('WhatsApp')->placeholder('—'),
                Infolists\Components\TextEntry::make('specialistProfile.skills')->label('Bacarıqlar')->badge()->placeholder('—')->columnSpanFull(),
                Infolists\Components\TextEntry::make('specialistProfile.about')->label('Haqqında')->placeholder('—')->columnSpanFull(),
                Infolists\Components\TextEntry::make('specialistProfile.available_slots')->label('Boş slotlar')->placeholder('0'),
                // Bəli / Xeyr as text: the boolean icon's fallback read «No» in an az panel.
                Infolists\Components\TextEntry::make('specialistProfile.is_on_vacation')->label('Məzuniyyət rejimi')
                    ->formatStateUsing(fn ($state): string => $state ? 'Bəli' : 'Xeyr')->placeholder('Xeyr'),
                Infolists\Components\TextEntry::make('specialistProfile.is_featured')->label('Seçilmiş')
                    ->formatStateUsing(fn ($state): string => $state ? 'Bəli' : 'Xeyr')->placeholder('Xeyr'),
                Infolists\Components\TextEntry::make('specialistProfile.is_verified')->label('Təsdiqlənmiş')
                    ->formatStateUsing(fn ($state): string => $state ? 'Bəli' : 'Xeyr')->placeholder('Xeyr'),
                Infolists\Components\TextEntry::make('kind_label')->label('Növ')->badge()
                    ->state(fn (User $record): ?string => $record->specialistProfile?->specialty?->category?->singularLabel())
                    ->placeholder('—'),
                ...static::specialistDetailsEntries(),
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
            ])->columns(2)->columnSpanFull();
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
                Forms\Components\Select::make('city')->label('Şəhər')->options(self::cityOptions())->native(false),
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
                    ->searchable()->preload()->required()
                    // Re-renders the Briqada / Makler fieldsets below when the kind changes.
                    ->live(),
                Forms\Components\TextInput::make('experience_years')->label('Təcrübə (il)')->numeric()->minValue(0)->maxValue(80),
                Forms\Components\Select::make('city')->label('Şəhər')->options(self::cityOptions())->native(false)->required(),
                Forms\Components\TextInput::make('phone')->label('Əlaqə telefonu')->tel()->maxLength(30),
                Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->tel()->maxLength(30),
                Forms\Components\TextInput::make('avatar_path')->label('Avatar yolu')->maxLength(255),
                Forms\Components\TagsInput::make('skills')->label('Bacarıqlar')->columnSpanFull(),
                Forms\Components\Textarea::make('about')->label('Haqqında')->columnSpanFull(),
                Forms\Components\TextInput::make('available_slots')->label('Həftəlik boş slot')->numeric()->minValue(0)->maxValue(20),
                Forms\Components\Toggle::make('is_on_vacation')->label('Məzuniyyət rejimi'),
                Forms\Components\Toggle::make('is_featured')->label('Seçilmiş mütəxəssis'),
                // Two different claims, deliberately two toggles: "featured" promotes a
                // profile, "verified" says the documents behind it were checked. The
                // public filter and the card badge read this one.
                Forms\Components\Toggle::make('is_verified')
                    ->label('Təsdiqlənmiş mütəxəssis')
                    ->helperText('Sənədləri yoxlanılıb — profil kartında "Təsdiqlənmiş" nişanı göstərilir.'),
                Forms\Components\Toggle::make('show_on_homepage')
                    ->label('Ana səhifədə göstər')
                    ->helperText('Yalnız seçilmiş mütəxəssislər üçün keçərlidir — ana səhifə bölməsində görünəcək.'),
                Forms\Components\Toggle::make('show_in_header')->label('Header-də göstər'),
                ...static::specialistDetailsFieldsets(),
            ])
            ->columns(2)
            // Full width: in the form's right-hand column the crew repeater and the
            // checkbox lists were squeezed until labels wrapped letter by letter.
            ->columnSpanFull()
            // details keeps only the keys of the saved specialty's kind (null for a plain
            // trade) — the same rule the cabinet applies. See App\Support\SpecialistDetails.
            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data): array => static::normalizeSpecialistDetails($data))
            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data): array => static::normalizeSpecialistDetails($data));
    }

    /**
     * Briqadir crew / Makler agency fields, shown for the specialty's kind only. Dot
     * names write into the `details` json column of specialist_profiles.
     *
     * @return list<Schemas\Components\Fieldset>
     */
    protected static function specialistDetailsFieldsets(): array
    {
        $kindIs = fn (SpecialistCategory $kind): \Closure => fn (Schemas\Components\Utilities\Get $get): bool => SpecialistDetails::kindOfSpecialtyId($get('specialist_specialty_id')) === $kind;

        return [
            Schemas\Components\Fieldset::make('Briqada')
                ->visible($kindIs(SpecialistCategory::Foreman))
                ->schema([
                    Forms\Components\TextInput::make('details.crew_size')->label('Dəstədə neçə nəfər')
                        ->numeric()->integer()->minValue(1)->maxValue(SpecialistDetails::MAX_CREW_SIZE),
                    Forms\Components\Toggle::make('details.takes_full_projects')->label('Tam layihə götürür')->inline(false),
                    Forms\Components\Select::make('details.crew_trades')->label('Dəstənin ixtisasları')
                        ->multiple()
                        ->options(fn (): array => SpecialistDetails::tradeOptions())
                        ->columnSpanFull(),
                    Forms\Components\Repeater::make('details.crew_members')->label('Dəstə üzvləri')
                        ->schema([
                            Forms\Components\TextInput::make('name')->label('Ad, soyad')->required()->maxLength(100),
                            Forms\Components\Select::make('specialty_id')->label('İxtisas')
                                ->options(fn (): array => SpecialistDetails::tradeOptions()),
                            Forms\Components\TextInput::make('experience_years')->label('Təcrübə (il)')
                                ->numeric()->integer()->minValue(0)->maxValue(80),
                        ])
                        ->columns(3)
                        ->maxItems(SpecialistDetails::MAX_CREW_MEMBERS)
                        ->defaultItems(0)
                        ->addActionLabel('Üzv əlavə et')
                        ->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Schemas\Components\Fieldset::make('Makler məlumatları')
                ->visible($kindIs(SpecialistCategory::Realtor))
                ->schema([
                    Forms\Components\TextInput::make('details.agency_name')->label('Agentlik')->maxLength(150),
                    Forms\Components\TextInput::make('details.license_no')->label('Lisenziya №')->maxLength(60),
                    Forms\Components\CheckboxList::make('details.deal_types')->label('Əməliyyat növləri')
                        ->options(fn (): array => SpecialistDetails::dealTypeOptions())
                        ->required()->columns(3)->columnSpanFull(),
                    Forms\Components\CheckboxList::make('details.property_types')->label('Əmlak növləri')
                        ->options(fn (): array => SpecialistDetails::propertyTypeOptions())
                        ->columns(3)->columnSpanFull(),
                    Forms\Components\TextInput::make('details.service_areas')->label('Xidmət göstərdiyi ərazilər')
                        ->maxLength(300)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function normalizeSpecialistDetails(array $data): array
    {
        $kind = SpecialistDetails::kindOfSpecialtyId($data['specialist_specialty_id'] ?? null);
        $data['details'] = SpecialistDetails::normalize($kind, $data['details'] ?? []);

        return $data;
    }

    /**
     * Read-only rows of the Briqada / Makler block for the view page.
     *
     * @return list<Infolists\Components\TextEntry>
     */
    protected static function specialistDetailsEntries(): array
    {
        $details = fn (User $record): array => SpecialistDetails::of($record->specialistProfile);
        $kindIs = fn (SpecialistCategory $kind): \Closure => fn (User $record): bool => SpecialistDetails::kindOf($record->specialistProfile) === $kind;
        $names = fn (array $ids): array => SpecialistSpecialty::query()->whereIn('id', $ids)->get()
            ->mapWithKeys(fn (SpecialistSpecialty $s): array => [$s->id => (string) $s->name])->all();

        return [
            Infolists\Components\TextEntry::make('kind_crew_size')->label('Briqada (nəfər)')
                ->state(fn (User $record) => $details($record)['crew_size'] ?? null)
                ->placeholder('—')->visible($kindIs(SpecialistCategory::Foreman)),
            Infolists\Components\TextEntry::make('kind_full_projects')->label('Tam layihə götürür')
                ->state(fn (User $record): string => ($details($record)['takes_full_projects'] ?? false) ? 'Bəli' : 'Xeyr')
                ->visible($kindIs(SpecialistCategory::Foreman)),
            Infolists\Components\TextEntry::make('kind_crew_trades')->label('Dəstənin ixtisasları')->badge()
                ->state(fn (User $record): array => array_values($names($details($record)['crew_trades'] ?? [])))
                ->placeholder('—')->columnSpanFull()->visible($kindIs(SpecialistCategory::Foreman)),
            Infolists\Components\TextEntry::make('kind_crew_members')->label('Dəstə üzvləri')->listWithLineBreaks()
                ->state(function (User $record) use ($details, $names): array {
                    $members = $details($record)['crew_members'] ?? [];
                    $trades = $names(array_filter(array_column($members, 'specialty_id')));

                    return array_map(fn (array $m): string => collect([
                        $m['name'],
                        $m['specialty_id'] ? ($trades[$m['specialty_id']] ?? null) : null,
                        $m['experience_years'] !== null ? $m['experience_years'].' il' : null,
                    ])->filter()->implode(' · '), $members);
                })
                ->placeholder('—')->columnSpanFull()->visible($kindIs(SpecialistCategory::Foreman)),
            Infolists\Components\TextEntry::make('kind_agency')->label('Agentlik')
                ->state(fn (User $record) => $details($record)['agency_name'] ?? null)
                ->placeholder('—')->visible($kindIs(SpecialistCategory::Realtor)),
            Infolists\Components\TextEntry::make('kind_license')->label('Lisenziya №')
                ->state(fn (User $record) => $details($record)['license_no'] ?? null)
                ->placeholder('—')->visible($kindIs(SpecialistCategory::Realtor)),
            Infolists\Components\TextEntry::make('kind_deal_types')->label('Əməliyyat növləri')->badge()
                ->state(fn (User $record): array => array_values(array_intersect_key(SpecialistDetails::dealTypeOptions(), array_flip($details($record)['deal_types'] ?? []))))
                ->placeholder('—')->visible($kindIs(SpecialistCategory::Realtor)),
            Infolists\Components\TextEntry::make('kind_property_types')->label('Əmlak növləri')->badge()
                ->state(fn (User $record): array => array_values(array_intersect_key(SpecialistDetails::propertyTypeOptions(), array_flip($details($record)['property_types'] ?? []))))
                ->placeholder('—')->visible($kindIs(SpecialistCategory::Realtor)),
            Infolists\Components\TextEntry::make('kind_service_areas')->label('Xidmət göstərdiyi ərazilər')
                ->state(fn (User $record) => $details($record)['service_areas'] ?? null)
                ->placeholder('—')->columnSpanFull()->visible($kindIs(SpecialistCategory::Realtor)),
        ];
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
                $record->notify(new AccountApprovedNotification);
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
                $record->notify(new AccountRejectedNotification($data['rejection_reason']));
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

    /**
     * Label => label: profile `city` columns store the Azerbaijani name itself.
     *
     * @return array<string, string>
     */
    private static function cityOptions(): array
    {
        return array_combine(City::labels(), City::labels());
    }

    private static function statusOptions(): array
    {
        return collect(UserStatus::cases())
            ->mapWithKeys(fn (UserStatus $status): array => [$status->value => $status->label()])
            ->all();
    }
}
