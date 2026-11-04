<?php

namespace App\Filament\Resources;

use App\Enums\Calculator\FinishFamily;
use App\Filament\Resources\CalculatorFinishResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorFinish;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The finishes a visitor chooses for a floor, a wall or a ceiling — and the whole
 * price of each: the labour per m² and the material per m² for the three segments.
 *
 * This is the table that replaced a code-level classification. Adding a finish
 * ("Mikrosement", "Qapı-pəncərə boyası") is a row here: pick the surface and the
 * technology family, type the prices, and the calculator offers it on the next
 * page load, with the right preparation chain already attached.
 */
class CalculatorFinishResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorFinish::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-paint-brush';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Üzlüklər';
    protected static ?string $modelLabel = 'üzlük';
    protected static ?string $pluralModelLabel = 'Üzlüklər';
    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name->az', 'name->ru', 'name->en', 'finish_id', 'search_keywords'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Səth' => self::SURFACES[$record->surface?->value ?? $record->surface] ?? '',
            'Usta' => $record->labour_rate !== null ? number_format((float) $record->labour_rate, 2).' AZN / '.$record->unit : 'dəqiqləşdirilir',
            'Material (standart)' => $record->material_standard !== null ? number_format((float) $record->material_standard, 2).' AZN / '.$record->unit : 'dəqiqləşdirilir',
        ];
    }

    public const SURFACES = [
        'FLOOR' => 'Döşəmə',
        'WALL' => 'Divar',
        'CEILING' => 'Tavan',
    ];

    /** Which families make sense on which surface — the form only offers those. */
    private static function familiesFor(?string $surface): array
    {
        $options = [];

        foreach (FinishFamily::cases() as $family) {
            $allowed = $surface === null || in_array($surface, array_map(fn ($s) => $s->value, $family->surfaces()), true);

            if ($allowed) {
                $options[$family->value] = FinishFamily::options()[$family->value];
            }
        }

        return $options;
    }

    public static function explanationParts(): array
    {
        return [
            'text' => 'Müştərinin otaq üçün seçdiyi <strong>döşəmə, divar və tavan üzlükləri</strong>. '
                .'Hər sətir üzlüyün tam qiymətidir: ustanın 1 m² üçün haqqı və materialın 1 m² qiyməti (Ekonom/Standart/Premium). '
                .'«Texnologiya» hansı hazırlıq işlərinin avtomatik əlavə olunacağını deyir — boya tipli üzlüyə suvaq-şpaklyovka, kafelə yapışqan-dərz-hidroizolyasiya, laminata stəjka-plintus. '
                .'Yeni üzlük əlavə etmək üçün sətir yaratmaq kifayətdir.',
            'shot' => 'finishes',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('Üzlük haqqında')
                ->schema([
                    Forms\Components\TextInput::make('finish_id')
                        ->label('Texniki ID')
                        ->required()
                        ->alphaDash()
                        ->maxLength(64)
                        ->disabledOn('edit')
                        ->unique(ignoreRecord: true)
                        ->helperText('Böyük hərflərlə, məs. MICROCEMENT. Yaradılandan sonra dəyişdirilmir.'),

                    Forms\Components\TextInput::make('name')
                        ->label('Üzlüyün adı')
                        ->required()
                        ->helperText('Müştəri otaq addımında və smetada bu adı görür.')
                        ->translatable(),

                    Forms\Components\Select::make('surface')
                        ->label('Hansı səthə')
                        ->options(self::SURFACES)
                        ->required()
                        ->live()
                        ->disabledOn('edit')
                        ->helperText('Yaradılandan sonra dəyişdirilmir — səth hazırlıq zəncirini müəyyən edir.'),

                    Forms\Components\Select::make('family')
                        ->label('Texnologiya')
                        ->options(fn (Get $get): array => self::familiesFor($get('surface')))
                        ->required()
                        ->helperText('Hansı hazırlıq işlərinin və sərf materiallarının avtomatik əlavə olunacağını deyir.'),

                    Forms\Components\TextInput::make('unit')
                        ->label('Ölçü vahidi')
                        ->default('m²')
                        ->required()
                        ->datalist(['m²', 'p.m'])
                        ->helperText('Aşağıdakı bütün qiymətlər bu vahidin biri üçündür.'),

                    Forms\Components\TextInput::make('waste_percent')
                        ->label('Texnoloji ehtiyat, %')
                        ->numeric()->minValue(0)->maxValue(50)->default(0)->suffix('%')
                        ->helperText('Kəsim itkisi: kafel üçün 10, laminat üçün 8, boya üçün 0. Material miqdarının içindədir — maliyyə ehtiyatı ilə qarışdırmayın.'),
                ])
                ->columns(2),

            Section::make('Usta haqqı')
                ->description('Üzlüyün özünün vurulması / döşənməsi. Hazırlıq işləri (suvaq, stəjka, qrunt…) «İş tarifləri» səhifəsindədir və avtomatik əlavə olunur.')
                ->schema([
                    Forms\Components\TextInput::make('labour_rate')
                        ->label('1 vahid üçün usta haqqı')
                        ->numeric()->minValue(0)->suffix('AZN')
                        ->helperText(self::emptyPriceHint()),

                    Forms\Components\TextInput::make('minimum_charge')
                        ->label('Minimum sifariş')
                        ->numeric()->minValue(0)->default(0)->suffix('AZN')
                        ->helperText('Bütün obyekt üzrə bu işin cəmi bundan az çıxsa, bu məbləğ yazılır. 0 = minimum yoxdur.'),
                ])
                ->columns(2),

            Section::make('Material qiyməti (səviyyəyə görə)')
                ->description('Müştəri kataloqdan konkret məhsul seçməyəndə işləyir; seçdiyi səviyyəyə uyğun sütun götürülür.')
                ->schema([
                    Forms\Components\TextInput::make('material_economy')->label('Ekonom')->numeric()->minValue(0)->suffix('AZN'),
                    Forms\Components\TextInput::make('material_standard')->label('Standart')->numeric()->minValue(0)->suffix('AZN')->helperText('Ən çox seçilən səviyyə.'),
                    Forms\Components\TextInput::make('material_premium')->label('Premium')->numeric()->minValue(0)->suffix('AZN'),
                ])
                ->columns(3),

            Section::make('Digər')
                ->schema([
                    Forms\Components\Toggle::make('is_default')
                        ->label('Bu səth üçün ilkin seçim')
                        ->helperText('Yeni otaq əlavə ediləndə bu üzlük seçilmiş gəlir (yaş otaqlarda döşəmə və divar üçün kafel tipli üzlük götürülür).'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')->default(true)
                        ->helperText('Söndürsəniz müştəriyə təklif olunmur; köhnə smetalar dəyişmir.'),

                    Forms\Components\TextInput::make('search_keywords')
                        ->label('Axtarış sözləri')
                        ->helperText('Nöqtəli vergüllə: müştərinin və adminin bu üzlüyü hansı sözlərlə axtara biləcəyi.')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Daxili qeyd')
                        ->rows(2)
                        ->helperText('Yalnız admin panelində görünür.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sıralama nömrəsi')->numeric()->default(0),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Hər sətir bir üzlüyün tam qiymətidir: usta haqqı + material (Ekonom/Standart/Premium), 1 m² üçün. '
                .'Hazırlıq işləri texnologiyaya görə avtomatik əlavə olunur.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Üzlük')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(
                        fn (Builder $q) => $q
                            ->where('name->az', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('finish_id', 'like', "%{$search}%")
                            ->orWhere('search_keywords', 'like', "%{$search}%")
                    ))
                    ->description(fn (CalculatorFinish $record): string => FinishFamily::options()[$record->family?->value] ?? '')
                    ->wrap(),

                Tables\Columns\TextColumn::make('surface')
                    ->label('Səth')
                    ->formatStateUsing(fn ($state) => self::SURFACES[$state?->value ?? $state] ?? $state)
                    ->badge(),

                Tables\Columns\TextInputColumn::make('labour_rate')
                    ->label('Usta, AZN')->rules(['nullable', 'numeric', 'min:0'])->placeholder('—'),

                Tables\Columns\TextInputColumn::make('material_economy')
                    ->label('Ekonom, AZN')->rules(['nullable', 'numeric', 'min:0'])->placeholder('—'),

                Tables\Columns\TextInputColumn::make('material_standard')
                    ->label('Standart, AZN')->rules(['nullable', 'numeric', 'min:0'])->placeholder('—'),

                Tables\Columns\TextInputColumn::make('material_premium')
                    ->label('Premium, AZN')->rules(['nullable', 'numeric', 'min:0'])->placeholder('—'),

                Tables\Columns\TextColumn::make('waste_percent')
                    ->label('Ehtiyat')
                    ->formatStateUsing(fn ($state) => (float) $state > 0 ? '+'.(float) $state.'%' : '—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_default')->label('İlkin')->boolean()->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('surface')->label('Səth')->options(self::SURFACES),
                Tables\Filters\SelectFilter::make('family')->label('Texnologiya')->options(FinishFamily::options()),
                Tables\Filters\Filter::make('missing_price')
                    ->label('Qiyməti doldurulmayanlar')
                    ->query(fn ($query) => $query->whereNull('labour_rate')->orWhereNull('material_standard')),
            ])
            ->defaultSort('sort_order')
            ->actions([Actions\EditAction::make()->label('Redaktə')])
            ->bulkActions([Actions\DeleteBulkAction::make()])
            ->paginated([25, 50, 'all'])
            ->defaultPaginationPageOption(50);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorFinishes::route('/'),
            'create' => Pages\CreateCalculatorFinish::route('/create'),
            'edit' => Pages\EditCalculatorFinish::route('/{record}/edit'),
        ];
    }
}
