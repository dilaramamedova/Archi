<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorSettingResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorSetting;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The scalar knobs: contingency percent, VAT mode, area limits, free lift floors,
 * vehicle capacities, default coat counts and opening sizes, the "-dən" prefix and
 * the CTA target of the quick calculator.
 *
 * Keys are referenced by name from the engine, so they can be edited but not created
 * or deleted here — an invented key would change nothing and a deleted one would
 * silently fall back to a default.
 */
class CalculatorSettingResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorSetting::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Ümumi parametrlər';
    protected static ?string $modelLabel = 'parametr';
    protected static ?string $pluralModelLabel = 'Ümumi parametrlər';
    protected static ?int $navigationSort = 6;

    private const GROUPS = [
        'QUICK' => 'Sürətli kalkulyator',
        'FINANCE' => 'Pul və faizlər',
        'VALIDATION' => 'Yoxlamalar',
        'LOGISTICS' => 'Çatdırılma və qaldırma',
        'HVAC' => 'Kondisioner',
        'MATERIAL' => 'Material üçün ilkin dəyərlər',
        'GEOMETRY' => 'Ölçülər üçün ilkin dəyərlər',
        'SNAPSHOT' => 'Smetanın saxlanması',
    ];

    /**
     * One sentence per key, written for the person who has to decide whether to
     * change it: what it does, where the visitor feels it, and what a wrong value
     * would look like on the site.
     */
    private const EXPLANATIONS = [
        'QUICK_MIN_AREA' => 'Sürətli kalkulyatorda icazə verilən ən kiçik sahə. Müştəri bundan az yazsa, qiymət yerinə «Minimum sahə … m²-dir» xəbərdarlığı çıxır.',
        'QUICK_MAX_AREA' => 'Ən böyük sahə. Bundan çox yazılsa qiymət göstərilmir, «Daha böyük obyekt üçün bizimlə əlaqə saxlayın» yazılır.',
        'QUICK_DEFAULT_AREA' => 'Səhifə açılanda sahə xanasında hazır duran rəqəm. Müştəri heç nə etmədən nümunə qiymət görsün deyə.',
        'QUICK_DEFAULT_TYPE' => 'Səhifə açılanda hansı təmir növü seçili gəlsin: WHITE_BOX və ya FULL_RENOVATION.',
        'QUICK_DEFAULT_SEGMENT' => 'Səhifə açılanda hansı kateqoriya seçili gəlsin: ECONOMY, STANDARD və ya PREMIUM.',
        'SHOW_FROM_PREFIX' => 'Qiymətin yanında «-dən» yazılsın? 1 = «36 000 AZN-dən», 0 = «36 000 AZN». Hesablama təxmini olduğu üçün 1 tövsiyə olunur.',
        'DETAILED_CALCULATOR_URL' => '«Dəqiq hesabla» düyməsi müştərini hara aparsın. Adətən ətraflı kalkulyatorun ünvanı.',

        'CONTINGENCY_PERCENT' => 'Smetanın sonuna əlavə olunan ehtiyat faizi — gözlənilməz xərclər üçün. 10 yazsanız, yekun məbləğə 10% əlavə olunur və ayrıca sətir kimi görünür. 0 yazsanız ehtiyat sətri ümumiyyətlə çıxmır.',
        'VAT_MODE' => 'ƏDV necə göstərilsin: INCLUDED = qiymətlərin içindədir, ayrıca sətir yoxdur. ADDED = smetaya ayrıca ƏDV sətri əlavə olunur.',
        'VAT_PERCENT' => 'ƏDV faizi. Yalnız yuxarıdakı rejim ADDED olduqda işləyir.',
        'ROUND_TOTALS_TO_INTEGER' => 'Yekun məbləğ tam ədədə yuvarlaqlaşsın? 1 = «27 093 AZN», 0 = «27 092.70 AZN».',

        'AREA_TOLERANCE_PERCENT' => 'Müştərinin 1-ci addımda yazdığı sahə ilə otaqların cəmi arasında icazə verilən fərq. Fərq bundan çox olsa xəbərdarlıq çıxır, amma hesablama dayanmır.',

        'FREE_LIFT_FLOORS' => 'Lift olmayanda neçə mərtəbəyə görə pul alınmasın. 1 yazsanız, 1-ci mərtəbə pulsuz, 5-ci mərtəbə üçün 4 mərtəbə hesablanır.',
        'DELIVERY_VEHICLE_WEIGHT_CAPACITY' => 'Bir maşının daşıya bildiyi ağırlıq. Materialın ümumi çəkisi buna bölünərək neçə reys lazım olduğu tapılır.',
        'DELIVERY_VEHICLE_VOLUME_CAPACITY' => 'Bir maşının həcmi. Çəki və həcmdən hansı çox reys tələb edirsə, o götürülür.',
        'DEBRIS_VOLUME_PER_M2' => 'Demontajda hər 1 m² sahədən nə qədər tullantı çıxır. 0.05 yazsanız, 100 m² mənzildən 5 m³ tullantı hesablanır.',
        'DEBRIS_VEHICLE_VOLUME_CAPACITY' => 'Tullantı maşınının bir reysdə apardığı həcm.',

        'SPLIT_INCLUDED_ROUTE_METERS' => 'Split kondisionerin standart montajına neçə metr trassa daxildir. Müştəri bundan çox yazsa, artıq metrlərə ayrıca pul hesablanır.',

        'DEFAULT_PAINT_COATS' => 'Boya neçə qat vurulur. Boyanın miqdarı buna görə hesablanır.',
        'DEFAULT_PRIMER_COATS' => 'Qrunt neçə qat vurulur.',
        'DEFAULT_WALL_PUTTY_LAYERS' => 'Şpaklyovka neçə qat vurulur.',
        'DEFAULT_WATERPROOF_LAYERS' => 'Hidroizolyasiya neçə qat vurulur.',
        'PLASTER_DEFAULT_THICKNESS_MM' => 'Suvağın orta qalınlığı. Suvaq qarışığının kiloqramı buna görə hesablanır.',
        'PLASTER_THICK_THRESHOLD_MM' => 'Suvaq bu qalınlıqdan çox olduqda «qalın suvaq» əmsalı işə düşür.',
        'SCREED_DEFAULT_THICKNESS_MM' => 'Stəjkanın orta qalınlığı.',
        'SELF_LEVEL_DEFAULT_THICKNESS_MM' => 'Özüyayılan döşəmənin orta qalınlığı.',

        'DEFAULT_ROOM_HEIGHT' => 'Yeni otaq əlavə ediləndə tavan hündürlüyü kimi hazır gələn rəqəm.',
        'DEFAULT_DOOR_WIDTH' => 'Qapının standart eni. Divar sahəsindən qapının yeri bu ölçü ilə çıxılır.',
        'DEFAULT_DOOR_HEIGHT' => 'Qapının standart hündürlüyü.',
        'DEFAULT_WINDOW_WIDTH' => 'Pəncərənin standart eni.',
        'DEFAULT_WINDOW_HEIGHT' => 'Pəncərənin standart hündürlüyü.',

        'ESTIMATE_VALID_DAYS' => 'Saxlanmış smeta neçə gün etibarlı sayılsın. 0 yazsanız müddət qoyulmur.',
    ];

    public static function explanationParts(): array
    {
        return [
            'text' => 'Qiymət deyil, <strong>hesablama qaydaları</strong>: ehtiyat faizi, ƏDV, sahə həddləri, '
                .'standart qapı-pəncərə ölçüləri. Hər sətrin altında nə etdiyi yazılıb; '
                .'açar/söndür parametrlərində 1 = açıq, 0 = bağlı.',
            'shot' => 'settings',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make()
                ->schema([
                    Forms\Components\Placeholder::make('what_it_does')
                        ->label('Bu parametr nə edir')
                        ->content(fn (?CalculatorSetting $record): string => $record
                            ? (self::EXPLANATIONS[$record->key] ?? 'İzah əlavə olunmayıb.')
                            : '')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('value')
                        ->label('Dəyər')
                        ->required()
                        ->suffix(fn (?CalculatorSetting $record) => $record?->unit)
                        ->helperText(fn (?CalculatorSetting $record): string => match ($record?->type) {
                            'boolean' => 'Açmaq üçün 1, söndürmək üçün 0 yazın.',
                            'string' => 'Mətn dəyəri.',
                            default => 'Rəqəm yazın.',
                        }),

                    Forms\Components\TextInput::make('key')
                        ->label('Texniki açar')
                        ->disabled()
                        ->helperText('Proqramın daxili adı — dəyişdirilmir.'),

                    Forms\Components\TextInput::make('description')
                        ->label('Qısa ad')
                        ->translatable()
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Daxili qeyd')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Kalkulyatorun hesablama qaydaları. Hər sətrin altında nə etdiyi yazılıb. '
                .'Dəyəri birbaşa cədvəldə dəyişə bilərsiniz; açar/söndür parametrlərində 1 = açıq, 0 = bağlı.')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Parametr')
                    ->description(fn (CalculatorSetting $record): string => self::EXPLANATIONS[$record->key] ?? '')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('group')
                    ->label('Bölmə')
                    ->formatStateUsing(fn ($state) => self::GROUPS[$state] ?? $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextInputColumn::make('value')
                    ->label('Dəyər')
                    ->rules(['required']),

                Tables\Columns\TextColumn::make('unit')->label('Vahid')->color('gray'),

                Tables\Columns\TextColumn::make('key')
                    ->label('Texniki açar')
                    ->searchable()
                    ->size('xs')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([Tables\Filters\SelectFilter::make('group')->label('Bölmə')->options(self::GROUPS)])
            ->defaultSort('group')
            ->actions([Actions\EditAction::make()->label('Redaktə')])
            ->paginated([25, 50, 'all'])
            ->defaultPaginationPageOption(50);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorSettings::route('/'),
            'edit' => Pages\EditCalculatorSetting::route('/{record}/edit'),
        ];
    }
}
