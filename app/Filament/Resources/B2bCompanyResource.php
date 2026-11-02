<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\B2bCooperationType;
use App\Enums\B2bPlan;
use App\Filament\Resources\B2bCompanyResource\Pages;
use App\Models\B2bCompany;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class B2bCompanyResource extends Resource
{
    protected static ?string $model = B2bCompany::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static string|\UnitEnum|null $navigationGroup = 'B2B';

    protected static ?string $navigationLabel = 'Şirkətlər';

    protected static ?string $modelLabel = 'B2B şirkəti';

    protected static ?string $pluralModelLabel = 'B2B şirkətləri';

    protected static ?int $navigationSort = 1;

    private const MARKETS = ['international' => 'Beynəlxalq (USD)', 'local' => 'Daxili (AZN)'];

    private static function pricingOn(): bool
    {
        return (bool) config('b2b.pricing');
    }

    /**
     * Telegram / Instagram / LinkedIn / Facebook: a handle or an http(s) link, never
     * another scheme — the profile turns the value into an absolute link.
     */
    private static function handleOrUrlRule(): \Closure
    {
        return fn (): \Closure => function (string $attribute, mixed $value, \Closure $fail): void {
            if (filled($value) && ! preg_match(B2bCompany::HANDLE_OR_URL_REGEX, trim((string) $value))) {
                $fail('Tam link (https://…) və ya istifadəçi adı (@ad) yazın.');
            }
        };
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Şirkət məlumatları')->schema([
                Forms\Components\TextInput::make('legal_name')->label('Rəsmi ad (Legal Company Name)')->required()->maxLength(255),
                Forms\Components\TextInput::make('brand_name')->label('Brend adı')->helperText('Boş qalsa, rəsmi ad göstərilir.')->maxLength(255),
                Forms\Components\TextInput::make('registration_no')->label('VÖEN / Qeydiyyat nömrəsi')->maxLength(255),
                Forms\Components\TextInput::make('country')->label('Ölkə')->maxLength(80)
                    ->datalist(fn (): array => B2bCompany::query()->whereNotNull('country')->distinct()->orderBy('country')->pluck('country')->all()),
                Forms\Components\TextInput::make('legal_address')->label('Hüquqi ünvan')->maxLength(255),
                Forms\Components\TextInput::make('phone')->label('Şirkət telefonu (ofis)')->helperText('Profildə «Əlaqə» siyahısında göstərilir.')->tel()->maxLength(40),
                Forms\Components\TextInput::make('website')->label('Veb-sayt')->placeholder('https://')->url()->rule('url:http,https')->maxLength(255),
                Forms\Components\FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('b2b/logos')->maxSize(4096),
                Forms\Components\Textarea::make('description')->label('Qısa təsvir')->rows(4)->translatable()->columnSpanFull(),
            ])->columns(2),

            Section::make('Kateqoriya və əməkdaşlıq')->schema([
                Forms\Components\Select::make('b2b_category_id')
                    ->label('Əsas kateqoriya')
                    ->relationship('category', 'name', fn (Builder $query) => $query->orderBy('sort_order'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->getTranslation('name', 'az'))
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('cooperation_type')
                    ->label('Əməkdaşlıq növü')
                    ->options(B2bCooperationType::options()),
                Forms\Components\TextInput::make('brands')->label('Şirkət brendləri')->placeholder('məs. Knauf, Caparol')->maxLength(255),
                Forms\Components\TextInput::make('moq')->label('Minimum sifariş (MOQ)')->placeholder('məs. 500 ədəd / 1 konteyner')->maxLength(255),
                Forms\Components\Textarea::make('catalog_description')->label('Kataloq təsviri')->rows(3)->translatable()->columnSpanFull(),
                Forms\Components\FileUpload::make('catalog_images')
                    ->label('Kataloq şəkilləri')
                    ->multiple()
                    ->reorderable()
                    ->image()
                    ->maxSize(8192)
                    ->disk('public')
                    ->directory('b2b/catalog')
                    ->columnSpanFull(),
            ])->columns(2),

            Section::make('Əlaqə')->schema([
                Forms\Components\TextInput::make('contact_person')->label('Əlaqələndirici şəxs')->maxLength(120),
                Forms\Components\TextInput::make('contact_position')->label('Vəzifə')->maxLength(120),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('Əlaqələndiricinin telefonu')
                    ->helperText('Birbaşa xətt. Profildə şəxsin adının altında göstərilir (ofis telefonundan fərqlidirsə).')
                    ->tel()->maxLength(40),
                Forms\Components\TextInput::make('whatsapp')->label('WhatsApp')->placeholder('+994 50 000 00 00')->maxLength(40),
                Forms\Components\TextInput::make('telegram')->label('Telegram')->placeholder('@istifadəçi və ya https://t.me/…')->rules([self::handleOrUrlRule()])->maxLength(80),
                Forms\Components\TextInput::make('email')->label('E-poçt')->email()->maxLength(255),
                Forms\Components\TextInput::make('working_hours')->label('İş saatları')->placeholder('B.e–Cümə 09:00–18:00')->maxLength(120),
                Forms\Components\TextInput::make('instagram')->label('Instagram')->placeholder('@ad və ya https://instagram.com/…')->rules([self::handleOrUrlRule()])->maxLength(255),
                Forms\Components\TextInput::make('linkedin')->label('LinkedIn')->placeholder('https://linkedin.com/company/…')
                    ->helperText('Tam link, «company/ad» (şirkət səhifəsi) və ya «in/ad» (şəxsi profil). Təkcə ad yazılsa, şirkət səhifəsi sayılır.')
                    ->rules([self::handleOrUrlRule()])->maxLength(255),
                Forms\Components\TextInput::make('facebook')->label('Facebook')->placeholder('https://facebook.com/…')->rules([self::handleOrUrlRule()])->maxLength(255),
                Forms\Components\CheckboxList::make('languages')->label('Ünsiyyət dilləri')->options(B2bCompany::LANGUAGES)->columns(3)->columnSpanFull(),
            ])->columns(2),

            Section::make('Yerləşdirmə')->schema([
                // The market only picks which pricing table a company belongs to —
                // hidden while pricing is off (config/b2b.php), the column keeps its value.
                Forms\Components\Select::make('market')->label('Bazar')
                    ->options(self::MARKETS)
                    ->default('international')
                    ->visible(fn (): bool => self::pricingOn())
                    ->required(),
                Forms\Components\Select::make('plan')->label('Plan')->options(B2bPlan::options())->default(B2bPlan::Free->value)->required(),
                Forms\Components\TextInput::make('slug')->label('Slug')->helperText('Boş qalsa, addan yaradılır.')->alphaDash()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_featured')->label('Önə çıxarılıb (Featured)'),
                Forms\Components\Toggle::make('is_verified')->label('Təsdiqlənib'),
                Forms\Components\Toggle::make('is_published')->label('Dərc edilib'),
                Forms\Components\DateTimePicker::make('published_at')->label('Dərc tarixi')->helperText('Boş qalsa, dərc edildiyi an yazılır.')->timezone('Asia/Baku')->seconds(false),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category'))
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->label('Logo')->circular()->disk('public')->defaultImageUrl(asset('assets/placeholder.svg'))->toggleable(),
                Tables\Columns\TextColumn::make('legal_name')
                    ->label('Şirkət')
                    ->formatStateUsing(fn (B2bCompany $record): string => $record->displayName())
                    ->description(fn (B2bCompany $record): ?string => $record->brand_name ? $record->legal_name : null)
                    ->searchable(['legal_name', 'brand_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')->label('Ölkə')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Kateqoriya')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('cooperation_type')
                    ->label('Əməkdaşlıq')
                    ->badge()
                    ->formatStateUsing(fn (?B2bCooperationType $state): string => $state?->label() ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('market')
                    ->label('Bazar')
                    ->visible(fn (): bool => self::pricingOn())
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'local' ? 'AZN' : 'USD')
                    ->color(fn (string $state): string => $state === 'local' ? 'gray' : 'info'),
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn (B2bPlan $state): string => $state->label())
                    ->color(fn (B2bPlan $state): string => $state === B2bPlan::Free ? 'gray' : 'warning'),
                Tables\Columns\IconColumn::make('is_featured')->label('Önə')->boolean()->toggleable(),
                Tables\Columns\IconColumn::make('is_published')->label('Dərc')->boolean(),
                Tables\Columns\TextColumn::make('views_count')->label('Baxış')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Dərc'),
                Tables\Filters\SelectFilter::make('market')->label('Bazar')->options(self::MARKETS)->visible(fn (): bool => self::pricingOn()),
                Tables\Filters\SelectFilter::make('cooperation_type')->label('Əməkdaşlıq növü')->options(B2bCooperationType::options()),
                Tables\Filters\SelectFilter::make('b2b_category_id')
                    ->label('Kateqoriya')
                    ->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->getTranslation('name', 'az')),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label('Saytda')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (B2bCompany $record): string => route('b2b.show', $record->slug))
                    ->openUrlInNewTab()
                    // Only when the profile is live — a draft or a future date would 404.
                    ->visible(fn (B2bCompany $record): bool => $record->is_published
                        && $record->published_at !== null
                        && $record->published_at->lte(now())),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2bCompanies::route('/'),
            'create' => Pages\CreateB2bCompany::route('/create'),
            'edit' => Pages\EditB2bCompany::route('/{record}/edit'),
        ];
    }
}
