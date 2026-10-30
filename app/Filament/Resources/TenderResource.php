<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TenderResource\Pages;
use App\Models\Tender;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TenderResource extends Resource
{
    protected static ?string $model = Tender::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|\UnitEnum|null $navigationGroup = 'Tenderlər';

    protected static ?string $navigationLabel = 'Tenderlər';

    protected static ?string $modelLabel = 'Tender';

    protected static ?string $pluralModelLabel = 'Tenderlər';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        $lines = 'Hər sətirdə bir bənd.';

        return $form->schema([
            Section::make('Əsas')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Başlıq')
                    ->maxLength(255)
                    ->translatable(modifyLocalizedFieldUsing: fn (Forms\Components\TextInput $field, string $locale) => $field->required($locale === 'az'))
                    ->columnSpanFull(),
                Forms\Components\Select::make('tender_category_id')
                    ->label('Kateqoriya')
                    ->relationship('category', 'name', fn (Builder $query) => $query->orderBy('sort_order'))
                    ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->getTranslation('name', 'az'))
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('number')
                    ->label('Tender №')
                    ->helperText('Boş qalsa, avtomatik verilir (T-'.now()->year.'-001).')
                    ->maxLength(32)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->helperText('Boş qalsa, başlıqdan yaradılır.')
                    ->maxLength(255)
                    ->alphaDash()
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('summary')
                    ->label('Qısa təsvir (kartda)')
                    ->rows(2)
                    ->maxLength(300)
                    ->translatable()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('description')
                    ->label('Tender haqqında')
                    ->rows(5)
                    ->translatable()
                    ->columnSpanFull(),
            ])->columns(3),

            Section::make('Məzmun bölmələri')->schema([
                Forms\Components\Textarea::make('scope')->label('İşin həcmi')->helperText($lines)->rows(5)->translatable(),
                Forms\Components\Textarea::make('requirements')->label('İştirakçıya tələblər')->helperText($lines)->rows(5)->translatable(),
                Forms\Components\Textarea::make('submission_documents')
                    ->label('Göndəriləcək sənədlər')
                    ->helperText($lines.' Məs.: Kommersiya təklifi — PDF')
                    ->rows(5)
                    ->translatable(),
            ]),

            Section::make('Sifarişçi və əlaqə')->schema([
                Forms\Components\TextInput::make('organizer_name')->label('Sifarişçi (şirkət)')->required()->maxLength(255),
                Forms\Components\FileUpload::make('organizer_logo')
                    ->label('Loqo')
                    ->helperText('Boş qalsa, şirkət adının baş hərfləri göstərilir.')
                    ->image()
                    ->disk('public')
                    ->directory('tenders/logos'),
                Forms\Components\TextInput::make('contact_name')->label('Əlaqə şəxsi / şöbə')->placeholder('Tender şöbəsi')->maxLength(120)->translatable(),
                Forms\Components\TextInput::make('contact_email')
                    ->label('Müraciət emaili')
                    ->helperText('«E-poçtla müraciət et» bu ünvana yazır.')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')->label('Telefon')->tel()->maxLength(40),
            ])->columns(2),

            Section::make('Əsas məlumatlar')->schema([
                Forms\Components\TextInput::make('city')->label('Şəhər')->maxLength(80)->datalist(fn (): array => Tender::query()->whereNotNull('city')->distinct()->orderBy('city')->pluck('city')->all()),
                Forms\Components\TextInput::make('area_sqm')->label('Sahə (m²)')->numeric()->minValue(0),
                Forms\Components\TextInput::make('duration')->label('İcra müddəti')->placeholder('8 həftə')->maxLength(60)->translatable(),
                Forms\Components\TextInput::make('budget_min')->label('Büdcə (min)')->helperText('Hər ikisi boş — «Sorğu əsasında».')->numeric()->minValue(0),
                Forms\Components\TextInput::make('budget_max')->label('Büdcə (maks)')->numeric()->minValue(0)->gte('budget_min'),
                Forms\Components\Select::make('currency')
                    ->label('Valyuta')
                    ->options(['AZN' => 'AZN', 'USD' => 'USD', 'EUR' => 'EUR'])
                    ->default('AZN')
                    ->required(),
                Forms\Components\DateTimePicker::make('deadline_at')
                    ->label('Son müraciət tarixi (Bakı vaxtı)')
                    ->helperText('Bu andan sonra tender «Bağlanıb» olur. Boş — müddətsiz.')
                    ->timezone('Asia/Baku')
                    ->seconds(false),
            ])->columns(3),

            Section::make('Tender faylları')->schema([
                Forms\Components\FileUpload::make('files')
                    ->label('Fayllar (PDF, DOC, XLS, DWG, ZIP)')
                    ->helperText('Yalnız aktiv paketi olan istifadəçilər yükləyə bilər.')
                    ->multiple()
                    ->reorderable()
                    ->storeFileNamesIn('file_names')
                    ->maxSize(51200)
                    ->disk(Tender::FILES_DISK)
                    ->directory('tenders')
                    ->visibility('private')
                    ->downloadable(),
            ]),

            Section::make('Dərc')->schema([
                Forms\Components\Toggle::make('is_published')->label('Dərc edilib'),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('Dərc tarixi')
                    ->helperText('Boş qalsa, dərc edildiyi an yazılır.')
                    ->timezone('Asia/Baku')
                    ->seconds(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('category')->withCount('savedBy'))
            ->columns([
                Tables\Columns\TextColumn::make('number')->label('№')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->searchable()->limit(50)->wrap(),
                Tables\Columns\TextColumn::make('organizer_name')->label('Sifarişçi')->searchable(),
                Tables\Columns\TextColumn::make('category.name')->label('Kateqoriya')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('city')->label('Şəhər')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('deadline_at')
                    ->label('Son tarix')
                    ->dateTime('d.m.Y H:i', 'Asia/Baku')
                    ->sortable()
                    ->placeholder('Müddətsiz'),
                Tables\Columns\TextColumn::make('state')
                    ->label('Vəziyyət')
                    ->badge()
                    ->state(fn (Tender $record): string => $record->isOpen() ? 'Aktiv' : 'Bağlanıb')
                    ->color(fn (string $state): string => $state === 'Aktiv' ? 'success' : 'gray'),
                Tables\Columns\IconColumn::make('is_published')->label('Dərc')->boolean(),
                Tables\Columns\TextColumn::make('views_count')->label('Baxış')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('saved_by_count')->label('Saxlanılıb')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('published_at')->label('Dərc tarixi')->dateTime('d.m.Y', 'Asia/Baku')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_published')->label('Dərc'),
                Tables\Filters\SelectFilter::make('tender_category_id')
                    ->label('Kateqoriya')
                    ->relationship('category', 'name')
                    ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->getTranslation('name', 'az')),
                Tables\Filters\SelectFilter::make('state')
                    ->label('Vəziyyət')
                    ->options(['open' => 'Aktiv', 'closed' => 'Bağlanıb'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'open' => $query->open(),
                        'closed' => $query->closed(),
                        default => $query,
                    }),
            ])
            ->actions([
                Actions\Action::make('view')
                    ->label('Saytda')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Tender $record): string => route('tenders.show', $record->slug))
                    ->openUrlInNewTab()
                    ->visible(fn (Tender $record): bool => $record->is_published),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenders::route('/'),
            'create' => Pages\CreateTender::route('/create'),
            'edit' => Pages\EditTender::route('/{record}/edit'),
        ];
    }
}
