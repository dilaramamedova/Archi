<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorQuickRateResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorQuickRate;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The six approved quick-calculator prices (TZ §33).
 *
 * The rows are fixed — one per renovation type and segment — so the resource only
 * edits them: creating a seventh combination or deleting one would leave the
 * calculator with a hole it can only report as "qiymət dəqiqləşdirilir".
 */
class CalculatorQuickRateResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorQuickRate::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-calculator';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Sürətli kalkulyator qiymətləri';
    protected static ?string $modelLabel = 'sürətli kalkulyator qiyməti';
    protected static ?string $pluralModelLabel = 'Sürətli kalkulyator qiymətləri';
    protected static ?int $navigationSort = 1;

    private const TYPES = [
        'WHITE_BOX' => 'White Box',
        'FULL_RENOVATION' => 'Tam təmir',
    ];

    private const SEGMENTS = [
        'ECONOMY' => 'Ekonom',
        'STANDARD' => 'Standart',
        'PREMIUM' => 'Premium',
    ];

    public static function explanationParts(): array
    {
        return [
            'what' => 'Sürətli kalkulyatorun altı qiyməti. İki təmir növü (White Box, Tam təmir) × '
                .'üç kateqoriya (Ekonom, Standart, Premium). Hər biri <strong>1 m²-in qiymətidir</strong>.',
            'where' => 'Saytın <strong>/calculator</strong> səhifəsində və ana səhifədə açılan '
                .'kiçik kalkulyator pəncərəsində. Hesablama sadədir: müştərinin yazdığı sahə × bu qiymət.',
            'rules' => [
                'Müştəri 80 m² yazıb «White Box + Standart» seçsə və burada 450 yazılıbsa, '
                    .'ona <strong>36 000 AZN</strong> göstərilir (80 × 450).',
                'Bu altı sətir sabitdir: yenisini yaratmaq və ya silmək olmaz, çünki '
                    .'kalkulyatorda onlara uyğun düymə yoxdur.',
                self::emptyPriceHint(),
            ],
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('Qiymət')
                ->schema([
                    Forms\Components\TextInput::make('renovation_type')
                        ->label('Təmir növü')
                        ->formatStateUsing(fn ($state) => self::TYPES[$state?->value ?? $state] ?? $state)
                        ->disabled()
                        ->helperText('Müştərinin seçdiyi düymə.'),

                    Forms\Components\TextInput::make('segment')
                        ->label('Qiymət kateqoriyası')
                        ->formatStateUsing(fn ($state) => self::SEGMENTS[$state?->value ?? $state] ?? $state)
                        ->disabled()
                        ->helperText('Müştərinin seçdiyi material səviyyəsi.'),

                    Forms\Components\TextInput::make('price_per_m2')
                        ->label('1 m²-in qiyməti')
                        ->numeric()
                        ->minValue(0)
                        ->suffix('AZN/m²')
                        ->helperText('Bütün təmirin deyil, yalnız bir kvadratmetrin qiyməti.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true)
                        ->helperText('Söndürsəniz, bu variant kalkulyatorda seçilə bilməz.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Altı sətrin hamısı 1 m²-in qiymətidir. Qiyməti birbaşa cədvəldə dəyişə bilərsiniz. '
                .'Nümunə: 80 m² × 450 AZN = 36 000 AZN.')
            ->columns([
                Tables\Columns\TextColumn::make('renovation_type')
                    ->label('Təmir növü')
                    ->formatStateUsing(fn ($state) => self::TYPES[$state?->value ?? $state] ?? (string) $state)
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('segment')
                    ->label('Kateqoriya')
                    ->formatStateUsing(fn ($state) => self::SEGMENTS[$state?->value ?? $state] ?? (string) $state)
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextInputColumn::make('price_per_m2')
                    ->label('1 m²-in qiyməti, AZN')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->placeholder('dəqiqləşdirilir'),

                Tables\Columns\TextColumn::make('price_per_m2')
                    ->label('80 m² üçün nümunə')
                    ->state(fn (CalculatorQuickRate $record): string => $record->price_per_m2 === null
                        ? 'qiymət dəqiqləşdirilir'
                        : number_format((float) $record->price_per_m2 * 80, 0, '.', ' ').' AZN')
                    ->color('gray')
                    ->tooltip('Müştəri 80 m² yazsa nə görəcək'),

                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Son dəyişiklik')
                    ->dateTime('d.m.Y H:i')
                    ->color('gray'),
            ])
            ->defaultSort('id')
            ->paginated(false)
            ->actions([Actions\EditAction::make()->label('Redaktə')]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorQuickRates::route('/'),
            'edit' => Pages\EditCalculatorQuickRate::route('/{record}/edit'),
        ];
    }
}
