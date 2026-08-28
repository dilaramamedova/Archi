<?php

namespace App\Filament\Resources;

use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\WorkCategory;
use App\Filament\Resources\CalculatorWorkRateResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorWorkRate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The work catalog and its rates — the numbers behind every "İşlər" line of an
 * estimate (master spec §10, §21).
 *
 * The work id is the technical contract between this table and the calculation
 * engine, so it is only editable while creating a row. Leaving the rate empty is a
 * deliberate state: the estimate then shows the work with "qiymət dəqiqləşdirilir"
 * instead of quietly pricing it at zero.
 */
class CalculatorWorkRateResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorWorkRate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'İş qiymətləri';
    protected static ?string $modelLabel = 'iş qiyməti';
    protected static ?string $pluralModelLabel = 'İş qiymətləri';
    protected static ?int $navigationSort = 2;

    /**
     * The admin panel's top search bar. The name is a translated JSON column, so
     * the locale paths are listed explicitly — a plain LIKE over the raw JSON
     * misses every Azerbaijani letter, because Laravel stores it \u-escaped.
     */
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name->az', 'name->ru', 'name->en', 'search_keywords', 'work_id'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Bölmə' => self::CATEGORY_LABELS[$record->category?->value ?? $record->category] ?? '',
            'Qiymət' => $record->rate !== null
                ? number_format((float) $record->rate, 2).' AZN / '.$record->unit
                : 'dəqiqləşdirilir',
        ];
    }

    private const CATEGORY_LABELS = [
        'DEMOLITION' => 'Demontaj (sökülmə)',
        'WALLS' => 'Divar işləri',
        'FLOOR' => 'Döşəmə işləri',
        'CEILING' => 'Tavan işləri',
        'TILING' => 'Kafel işləri',
        'ELECTRICAL' => 'Elektrik',
        'PLUMBING' => 'Santexnika',
        'HVAC' => 'Kondisioner',
        'VENTILATION' => 'Ventilyasiya',
        'DOORS' => 'Qapılar',
        'LIGHTING' => 'İşıqlandırma',
        'LOGISTICS' => 'Logistika (daşınma, qaldırma)',
        'SERVICES' => 'Əlavə xidmətlər',
        'MATERIALS' => 'Materiallar',
    ];

    /**
     * Plain-language answer to "how is the quantity of this work found?".
     *
     * The stored value is the engine's own variable name; a manager reading
     * "NetWallAreaSelected" learns nothing, while "qapı və pəncərə çıxılmaqla divar
     * sahəsi" tells them exactly which number their rate will be multiplied by — and
     * therefore whether the rate they are about to type is the right size.
     */
    private const QUANTITY_SOURCES = [
        'SelectedFloorArea' => 'Otağın döşəmə sahəsi (m²)',
        'SelectedCeilingArea' => 'Otağın tavan sahəsi (m²)',
        'SelectedWallArea' => 'Seçilmiş divar sahəsi (m²)',
        'SelectedWallTileArea' => 'Kafel vurulan divar sahəsi (m²)',
        'NetWallAreaSelected' => 'Qapı və pəncərə çıxılmaqla divar sahəsi (m²)',
        'NewPartitionArea' => 'Yeni arakəsmənin sahəsi (m²)',
        'PartitionArea' => 'Sökülən arakəsmənin sahəsi (m²)',
        'PaintWallArea' => 'Boyanan divar sahəsi (m²)',
        'PaintCeilingArea' => 'Boyanan tavan sahəsi (m²)',
        'WallpaperWallArea' => 'Divar kağızı vurulan sahə (m²)',
        'DecorWallArea' => 'Dekorativ suvaq sahəsi (m²)',
        'PanelWallArea' => 'Panel vurulan divar sahəsi (m²)',
        'StoneWallArea' => 'Daş vurulan divar sahəsi (m²)',
        'StoneFloorArea' => 'Daş döşənən sahə (m²)',
        'TileArea' => 'Ümumi kafel sahəsi (m²)',
        'TileAreaStandard' => 'Standart formatlı kafel sahəsi (m²)',
        'TileAreaLarge' => 'İriformatlı kafel sahəsi (m²)',
        'TileAreaMosaic' => 'Mozaika sahəsi (m²)',
        'TileFloorArea' => 'Döşəmə kafeli sahəsi (m²)',
        'WetWallArea' => 'Yaş zona divarlarının sahəsi (m²)',
        'WetFloorArea' => 'Yaş zona döşəməsinin sahəsi (m²)',
        'WarmFloorArea' => 'İsti döşəmə sahəsi (m²)',
        'LaminateArea' => 'Laminat döşənən sahə (m²)',
        'SPCArea' => 'SPC döşənən sahə (m²)',
        'ParquetArea' => 'Parket döşənən sahə (m²)',
        'GypsumCeilingArea' => 'Alçıpan tavanın sahəsi (m²)',
        'StretchCeilingArea' => 'Gərilmə tavanın sahəsi (m²)',
        'SkirtingLength' => 'Plintusun uzunluğu — otağın perimetri minus qapı yerləri (p.m)',
        'CorniceLength' => 'Karnizin uzunluğu (p.m)',
        'ShadowGapLength' => 'Kölgə profilinin uzunluğu (p.m)',
        'SealantLength' => 'Hermetiklənən birləşmələrin uzunluğu (p.m)',
        'Tile45Length' => '45° kəsilən kənarların uzunluğu (p.m)',
        'ObjectArea' => 'Obyektin ümumi sahəsi — 1-ci addımda yazılan (m²)',
        'ConditionedArea' => 'Kondisionerlənən sahə (m²)',
        'DoorCount' => 'Sökülən qapıların sayı (ədəd)',
        'InteriorDoorCount' => 'Quraşdırılan otaqarası qapıların sayı (ədəd)',
        'HiddenDoorCount' => 'Gizli qapıların sayı (ədəd)',
        'SlidingDoorCount' => 'Sürgülü qapıların sayı (ədəd)',
        'DoorOpeningPrepCount' => 'Hazırlanan qapı yerlərinin sayı (ədəd)',
        'FixtureCount' => 'Sökülən santexnika avadanlığının sayı (ədəd)',
        'ElectricalPointCount' => 'Sökülən elektrik nöqtələrinin sayı',
        'SocketCount' => 'Rozetka nöqtələrinin sayı — otaqlar addımında yazılır',
        'SwitchCount' => 'Açar (vıklyuçatel) nöqtələrinin sayı',
        'LightPointCount' => 'Tavan işıq nöqtələrinin sayı',
        'WallLightCount' => 'Divar işığı (bra) nöqtələrinin sayı',
        'TVPointCount' => 'TV nöqtələrinin sayı',
        'DataPointCount' => 'İnternet nöqtələrinin sayı',
        'ACPowerPointCount' => 'Kondisioner qidalanma nöqtələrinin sayı',
        'AppliancePointCount' => 'Məişət texnikası üçün güc nöqtələrinin sayı',
        'DedicatedLineCount' => 'Ayrıca güc xətlərinin sayı',
        'CableMeters' => 'Çəkilən kabelin uzunluğu (m)',
        'ConduitMeters' => 'Çəkilən qofranın uzunluğu (m)',
        'SmallPanelCount' => 'Kiçik elektrik şitlərinin sayı (ədəd)',
        'LargePanelCount' => 'Böyük elektrik şitlərinin sayı (ədəd)',
        'BreakerCount' => 'Avtomatların sayı (ədəd)',
        'RcdCount' => 'UZO/differensial avtomatların sayı (ədəd)',
        'ThermostatCount' => 'İsti döşəmə termostatlarının sayı (ədəd)',
        'WaterPointCount' => 'Su nöqtələrinin sayı',
        'SewerPointCount' => 'Kanalizasiya nöqtələrinin sayı',
        'WaterPipeMeters' => 'Su borusunun uzunluğu (m)',
        'SewerPipeMeters' => 'Kanalizasiya borusunun uzunluğu (m)',
        'ManifoldCount' => 'Kollektorların sayı (ədəd)',
        'ToiletCount' => 'Unitazların sayı (ədəd)',
        'ConcealedCisternCount' => 'İnstalyasiyaların sayı (ədəd)',
        'BasinCount' => 'Əlüzyuyanların sayı (ədəd)',
        'KitchenSinkCount' => 'Mətbəx qabyuyanlarının sayı (ədəd)',
        'ShowerCount' => 'Duş sistemlərinin sayı (ədəd)',
        'BathtubCount' => 'Vannaların sayı (ədəd)',
        'BidetCount' => 'Bidelərin sayı (ədəd)',
        'HygienicShowerCount' => 'Gigiyenik duşların sayı (ədəd)',
        'MixerCount' => 'Qarışdırıcıların sayı (ədəd)',
        'BoilerCount' => 'Boylerlərin sayı (ədəd)',
        'WasherCount' => 'Paltaryuyan maşınların sayı (ədəd)',
        'DishwasherCount' => 'Qabyuyan maşınların sayı (ədəd)',
        'TowelRailCount' => 'Dəsmalqurudanların sayı (ədəd)',
        'SplitUnitCount' => 'Split kondisionerlərin sayı (komplekt)',
        'SplitExtraRouteMeters' => 'Standartdan artıq trassa uzunluğu (m)',
        'CondensateDrainMeters' => 'Drenaj borusunun uzunluğu (m)',
        'CondensatePumpCount' => 'Drenaj nasoslarının sayı (ədəd)',
        'MultiOutdoorCount' => 'Multi-split xarici bloklarının sayı (ədəd)',
        'MultiIndoorCount' => 'Multi-split daxili bloklarının sayı (ədəd)',
        'MultiRouteMeters' => 'Multi-split trassasının uzunluğu (m)',
        'DuctMeters' => 'Hava kanalının uzunluğu (m)',
        'DiffuserCount' => 'Diffuzorların sayı (ədəd)',
        'GrilleCount' => 'Ventilyasiya barmaqlıqlarının sayı (ədəd)',
        'FanCount' => 'Ventilyatorların sayı (ədəd)',
        'CoreDrillCount' => 'Almaz deşiklərin sayı (ədəd)',
        'SpotLightCount' => 'Nöqtəvi işıqların sayı (ədəd)',
        'PendantCount' => 'Asma işıqların sayı (ədəd)',
        'ChandelierCount' => 'Çilçıraqların sayı (ədəd)',
        'LedStripMeters' => 'LED lentin uzunluğu (p.m)',
        'LedProfileMeters' => 'LED profilin uzunluğu (p.m)',
        'DebrisVolume' => 'Tikinti tullantısının həcmi (m³) — sistem sahəyə görə hesablayır',
        'DebrisTrips' => 'Tullantı daşıma reyslərinin sayı — həcmə görə hesablanır',
        'DeliveryTrips' => 'Çatdırılma reyslərinin sayı — məhsulların çəkisinə görə',
        'LiftKgFloors' => 'Əl ilə qaldırma: kiloqram × mərtəbə',
        'PlatformDays' => 'Asma platformanın iş günlərinin sayı',
        'ScaffoldQty' => 'İskelenin miqdarı (m²/gün)',
        'ProtectedArea' => 'Qorunan səthlərin sahəsi (m²)',
        'SupervisionMonths' => 'Texniki nəzarətin ayları',
        'AuthorSupervisionMonths' => 'Müəllif nəzarətinin ayları',
    ];

    public static function explanationParts(): array
    {
        return [
            'text' => 'Aşağıdakı işlərin <strong>1 vahidə düşən iş haqqı</strong> (material ayrıdır). '
                .'Müştəri bunları «İşlər» addımında seçir, qiymətini isə yekun smetada görür.',
            'shot' => 'work-rates',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('İş haqqında')
                ->description('Bu iş nədir və miqdarı haradan gəlir')
                ->schema([
                    Forms\Components\TextInput::make('work_id')
                        ->label('Texniki ID')
                        ->required()
                        ->disabledOn('edit')
                        ->helperText('Proqramın daxili açarı (məs. WALL_PAINT). Yaradılandan sonra dəyişdirilmir.'),

                    Forms\Components\Select::make('category')
                        ->label('Hansı bölmədir')
                        ->options(self::CATEGORY_LABELS)
                        ->required()
                        ->helperText('Smetada bu iş hansı başlığın altında görünəcək.'),

                    // helperText must come BEFORE translatable(): the plugin wraps the
                    // field in a Tabs component, and anything chained afterwards is
                    // applied to that wrapper, which has no helper text.
                    Forms\Components\TextInput::make('name')
                        ->label('İşin adı')
                        ->required()
                        ->helperText('Müştəri smetada məhz bu adı görür.')
                        ->translatable()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('unit')
                        ->label('Ölçü vahidi')
                        ->required()
                        ->datalist(['m²', 'p.m', 'm', 'm³', 'əd', 'nöqtə', 'xətt', 'komplekt', 'reys', 'gün', 'ay'])
                        ->helperText('Qiymət bu vahidin biri üçündür. Məs. «m²» seçsəniz, qiymət 1 m²-in qiymətidir.'),

                    Forms\Components\Select::make('quantity_source')
                        ->label('Miqdar haradan gəlir')
                        ->options(self::QUANTITY_SOURCES)
                        ->searchable()
                        ->required()
                        ->allowHtml(false)
                        ->helperText('Kalkulyator bu ölçünü müştərinin cavablarından özü hesablayır və sizin qiymətə vurur.'),
                ])
                ->columns(2),

            Section::make('Qiymət')
                ->description('Düstur: miqdar × qiymət (əmsallar varsa, onlara da vurulur)')
                ->schema([
                    Forms\Components\TextInput::make('rate')
                        ->label('1 vahidin qiyməti')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('AZN')
                        ->helperText(new \Illuminate\Support\HtmlString(
                            'Məsələn ölçü vahidi «m²» və qiymət «9» olsa, 50 m² divar üçün 450 AZN hesablanacaq.<br>'
                            .self::emptyPriceHint()
                        )),

                    Forms\Components\TextInput::make('minimum_charge')
                        ->label('Minimum məbləğ')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->suffix('AZN')
                        ->helperText('Kiçik iş üçün alt hədd. Məs. 150 yazsanız, 4 m² suvaq 36 AZN yox, 150 AZN olacaq. '
                            .'Alt hədd istəmirsinizsə 0 saxlayın.'),

                    Forms\Components\Select::make('precision_class')
                        ->label('Bu qiymət nə dərəcədə dəqiqdir')
                        ->options([
                            PrecisionClass::Calculated->value => 'Hesablanmış — ölçüyə görə dəqiq bilinir',
                            PrecisionClass::Exact->value => 'Dəqiq — real məhsulun qiyməti',
                            PrecisionClass::Estimated->value => 'Təxmini — layihəsiz dəqiq bilinmir',
                        ])
                        ->default(PrecisionClass::Calculated->value)
                        ->required()
                        ->helperText('«Təxmini» seçsəniz, müştəri smetada bu sətrin yanında ulduz və '
                            .'«dəqiq qiymət layihə əsasında müəyyən edilir» xəbərdarlığını görəcək. '
                            .'VRF kondisioner kimi işlər üçün istifadə olunur.'),

                    Forms\Components\Toggle::make('contingency_eligible')
                        ->label('Ehtiyat faizi bu işə də hesablansın')
                        ->default(true)
                        ->helperText('Smetanın sonundakı «Maliyyə ehtiyatı» faizi bu sətrin üstünə də gəlsin? '
                            .'Dizayn layihəsi, texniki nəzarət kimi kənar xidmətlərdə adətən söndürülür.'),
                ])
                ->columns(2),

            Section::make('Digər')
                ->schema([
                    Forms\Components\Textarea::make('search_keywords')
                        ->label('Axtarış sözləri')
                        ->rows(2)
                        ->helperText('Bu işi tapmaq üçün yaza biləcəyiniz digər sözlər — nöqtəli vergüllə ayırın. '
                            .'Məsələn «Yeni qapı quraşdırılsın; qapı montajı». Saytda gördüyünüz yazını buraya '
                            .'əlavə etsəniz, sonra axtarışda dərhal tapılır.')
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Daxili qeyd')
                        ->rows(2)
                        ->helperText('Yalnız admin panelində görünür, müştəriyə göstərilmir.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sıralama nömrəsi')
                        ->numeric()
                        ->default(0)
                        ->helperText('Bu siyahıda hansı ardıcıllıqla görünəcəyi. Kiçik rəqəm yuxarıda.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true)
                        ->helperText('Söndürsəniz, bu iş kalkulyatorda ümumiyyətlə təklif olunmur.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Hər sətir bir iş növünün 1 vahidə düşən qiymətidir. '
                .'Qiyməti birbaşa cədvəldə dəyişə bilərsiniz — xanaya klikləyin, rəqəmi yazın, Enter basın. '
                .'Boş qiymət «qiymət dəqiqləşdirilir» deməkdir; 0 isə «pulsuz» deməkdir.')
            ->columns([
                // Searching has to work from whatever the admin remembers: the name,
                // the label they saw on the site ("Yeni qapı quraşdırılsın"), or the
                // technical id. All three hit this one column.
                Tables\Columns\TextColumn::make('name')
                    ->label('İşin adı')
                    ->description(fn (CalculatorWorkRate $record): string => self::QUANTITY_SOURCES[$record->quantity_source] ?? $record->quantity_source)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(
                        fn (Builder $q) => $q
                            ->where('name->az', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('search_keywords', 'like', "%{$search}%")
                            ->orWhere('work_id', 'like', "%{$search}%")
                    ))
                    ->wrap(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Bölmə')
                    ->formatStateUsing(fn ($state) => self::CATEGORY_LABELS[$state?->value ?? $state] ?? (string) $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextInputColumn::make('rate')
                    ->label('Qiymət, AZN')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->placeholder('dəqiqləşdirilir')
                    ->tooltip('1 vahidin qiyməti'),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Nəyin biri üçün')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextInputColumn::make('minimum_charge')
                    ->label('Minimum, AZN')
                    ->rules(['numeric', 'min:0'])
                    ->tooltip('Kiçik iş üçün alt hədd. 0 = alt hədd yoxdur.'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktiv'),

                Tables\Columns\TextColumn::make('work_id')
                    ->label('Texniki ID')
                    ->searchable()
                    ->copyable()
                    ->size('xs')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Bölmə')
                    ->options(self::CATEGORY_LABELS),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Aktivlik'),

                Tables\Filters\Filter::make('missing_rate')
                    ->label('Yalnız qiyməti doldurulmayanlar')
                    ->query(fn ($query) => $query->whereNull('rate')),
            ])
            ->defaultSort('sort_order')
            // A search that finds nothing must not be a dead end: half the labels an
            // admin remembers from the site are interface text, not prices, and they
            // live in a different module entirely.
            ->emptyStateHeading('Tapılmadı')
            ->emptyStateDescription('Axtardığınız yazı burada yoxdursa, çox güman ki, o, qiymət deyil — '
                .'interfeys mətnidir (məsələn «Yeni tikili», «Obyektin vəziyyəti»). Belə yazılar '
                .'«Sistem → Tərcümələr» bölməsindən dəyişilir. Qiymət axtarırsınızsa, işin adını '
                .'və ya sadə sözlə yazmağa çalışın: «kafel», «boya», «qapı».')
            ->actions([Actions\EditAction::make()->label('Redaktə')])
            ->bulkActions([Actions\DeleteBulkAction::make()])
            ->paginated([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(50);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorWorkRates::route('/'),
            'create' => Pages\CreateCalculatorWorkRate::route('/create'),
            'edit' => Pages\EditCalculatorWorkRate::route('/{record}/edit'),
        ];
    }

    /** Categories are enum-backed; the select must never drift from them. */
    public static function categoryOptions(): array
    {
        return array_combine(
            array_map(fn (WorkCategory $c) => $c->value, WorkCategory::cases()),
            array_map(fn (WorkCategory $c) => self::CATEGORY_LABELS[$c->value] ?? $c->value, WorkCategory::cases()),
        );
    }
}
