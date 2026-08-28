<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorCoefficientResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorCoefficient;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Multipliers applied to a line total: ceiling height, tile format and layout,
 * herringbone parquet, HVAC complexity.
 *
 * 1.00 means "no change". Deactivating a coefficient also resolves it to 1.00 — the
 * engine never lets a missing multiplier zero out a line.
 */
class CalculatorCoefficientResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorCoefficient::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-variable';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Çətinlik əmsalları';
    protected static ?string $modelLabel = 'çətinlik əmsalı';
    protected static ?string $pluralModelLabel = 'Çətinlik əmsalları';
    protected static ?int $navigationSort = 4;

    private const GROUPS = [
        'GLOBAL' => 'Ümumi',
        'HEIGHT' => 'Tavan hündürlüyü',
        'TILE' => 'Kafel',
        'FLOOR' => 'Döşəmə',
        'WALL' => 'Divar',
        'HVAC' => 'Kondisioner',
        'CITY' => 'Şəhər',
    ];

    /** What each coefficient actually switches on — the admin never guesses. */
    private const WHEN_APPLIED = [
        'K_BASE' => 'Heç vaxt tətbiq olunmur, sadəcə baza dəyəri kimi saxlanılır.',
        'K_HEIGHT_3_01_3_50' => 'Otağın hündürlüyü 3.01–3.50 m olduqda divar və tavan işlərinə.',
        'K_HEIGHT_3_51_4_00' => 'Otağın hündürlüyü 3.51–4.00 m olduqda divar və tavan işlərinə.',
        'K_HEIGHT_OVER_4' => 'Otağın hündürlüyü 4 m-dən çox olduqda divar və tavan işlərinə.',
        'K_TILE_LARGE' => 'Müştəri iriformatlı kafel seçdikdə kafel vurma işinə.',
        'K_TILE_MOSAIC' => 'Mozaika seçildikdə.',
        'K_TILE_DIAGONAL' => 'Kafel diaqonal düzülüşlə vurulduqda.',
        'K_PARQUET_HERRINGBONE' => 'Parket «yolka» (herringbone) düzülüşlə döşəndikdə.',
        'K_DECOR_COMPLEX' => 'Mürəkkəb dekorativ suvaq seçildikdə.',
        'K_PLASTER_THICK' => 'Suvağın qalınlığı hədddən çox olduqda (hədd «Parametrlər» səhifəsindədir).',
        'K_HVAC_ZONE' => 'Kanal/VRF sistemində birdən çox zona olduqda.',
        'K_HVAC_COMPLEX' => 'Kanal/VRF/mərkəzi sistem «mürəkkəb» işarələndikdə.',
        'K_CITY_BAKU' => 'Şəhər əmsalları artıq «Şəhər əmsalları» səhifəsindən idarə olunur.',
        'K_CITY_OTHER' => 'Şəhər əmsalları artıq «Şəhər əmsalları» səhifəsindən idarə olunur.',
    ];

    public static function explanationParts(): array
    {
        return [
            'text' => 'Çətin şəraitdə işin qiymətini artıran əmsallar — yüksək tavan, iriformatlı kafel, '
                .'diaqonal düzülüş. Müştəri otaqları doldurduqca <strong>avtomatik</strong> tətbiq olunur. '
                .'1.00 = dəyişiklik yoxdur.',
            'shot' => 'coefficients',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('Əmsal')
                ->schema([
                    Forms\Components\TextInput::make('coefficient_id')
                        ->label('Texniki ID')
                        ->required()
                        ->disabledOn('edit'),

                    Forms\Components\Select::make('group')
                        ->label('Hansı qrupdur')
                        ->options(self::GROUPS)
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label('Nə üçündür')
                        ->required()
                        ->helperText('Yalnız admin panelində görünür.')
                        ->translatable()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('value')
                        ->label('Əmsalın dəyəri')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->default(1)
                        ->required()
                        ->helperText('1.00 = qiymət dəyişmir · 1.15 = +15% · 1.30 = +30% · 0.90 = −10%'),

                    Forms\Components\Placeholder::make('when')
                        ->label('Nə vaxt işə düşür')
                        ->content(fn (?CalculatorCoefficient $record): string => $record
                            ? (self::WHEN_APPLIED[$record->coefficient_id] ?? 'Kalkulyator müvafiq şərt yarandıqda tətbiq edir.')
                            : 'Sətir yaradıldıqdan sonra görünəcək.'),

                    Forms\Components\TextInput::make('applies_to')
                        ->label('Texniki qeyd')
                        ->helperText('Proqramçı üçün: hansı işlərə aiddir.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true)
                        ->helperText('Söndürsəniz əmsal 1.00 kimi işləyir — qiymət dəyişmir.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('1.00 = qiymət dəyişmir. 1.15 = həmin şəraitdə iş 15% bahalaşır. '
                .'Əmsallar müştərinin seçimlərinə görə avtomatik tətbiq olunur.')
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->label('Nə üçündür')
                    ->description(fn (CalculatorCoefficient $record): string => self::WHEN_APPLIED[$record->coefficient_id] ?? '')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('group')
                    ->label('Qrup')
                    ->formatStateUsing(fn ($state) => self::GROUPS[$state] ?? $state)
                    ->badge(),

                Tables\Columns\TextInputColumn::make('value')
                    ->label('Əmsal')
                    ->rules(['numeric', 'min:0'])
                    ->tooltip('1.00 = dəyişiklik yoxdur'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Qiymətə təsiri')
                    ->state(function (CalculatorCoefficient $record): string {
                        $percent = round(((float) $record->value - 1) * 100);

                        return match (true) {
                            $percent > 0 => "+{$percent}% bahalaşır",
                            $percent < 0 => abs($percent).'% ucuzlaşır',
                            default => 'dəyişmir',
                        };
                    })
                    ->color(fn (CalculatorCoefficient $record) => (float) $record->value > 1 ? 'warning' : 'gray'),

                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->filters([Tables\Filters\SelectFilter::make('group')->label('Qrup')->options(self::GROUPS)])
            ->defaultSort('group')
            ->actions([Actions\EditAction::make()->label('Redaktə')])
            ->bulkActions([Actions\DeleteBulkAction::make()])
            ->paginated([25, 50, 'all']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorCoefficients::route('/'),
            'create' => Pages\CreateCalculatorCoefficient::route('/create'),
            'edit' => Pages\EditCalculatorCoefficient::route('/{record}/edit'),
        ];
    }
}
