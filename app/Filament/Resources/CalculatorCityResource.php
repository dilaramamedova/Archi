<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorCityResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorCity;
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
 * City coefficients (master spec audit §2.10). Baku is the 1.00 base; a city that is
 * not listed resolves to 1.00 as well, so an unconfigured region never blocks a
 * calculation — it just costs the base rate until Archi says otherwise.
 */
class CalculatorCityResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorCity::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Şəhər əmsalları';
    protected static ?string $modelLabel = 'Şəhər əmsalı';
    protected static ?string $pluralModelLabel = 'Şəhər əmsalları';
    protected static ?int $navigationSort = 7;

    /** Top search bar: locale paths listed explicitly — see CalculatorWorkRateResource. */
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name->az', 'name->ru', 'name->en', 'city_key'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $percent = round(((float) $record->coefficient - 1) * 100);

        return [
            'İşlərə təsiri' => match (true) {
                $percent > 0 => "işlər +{$percent}% baha",
                $percent < 0 => 'işlər '.abs($percent).'% ucuz',
                default => 'Bakı ilə eyni',
            },
        ];
    }

    public static function explanationParts(): array
    {
        return [
            'text' => 'Müştərinin 1-ci addımda seçdiyi şəhər. Əmsal yalnız <strong>iş haqqına</strong> '
                .'tətbiq olunur (materiala yox). Bakı = 1.00 bazadır.',
            'shot' => 'cities',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('Şəhər')->schema([
                Forms\Components\TextInput::make('city_key')
                    ->label('Texniki açar')
                    ->required()
                    ->disabledOn('edit')
                    ->helperText('Proqramın daxili adı, məs. BAKU. Sonradan dəyişdirilmir.'),

                Forms\Components\TextInput::make('name')
                    ->label('Şəhərin adı')
                    ->required()
                    ->helperText('Müştəri siyahıda bu adı görür.')
                    ->translatable(),

                Forms\Components\TextInput::make('coefficient')
                    ->label('İş haqqı əmsalı')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0)
                    ->default(1)
                    ->required()
                    ->helperText('1.00 = Bakı ilə eyni · 1.10 = 10% baha · 0.95 = 5% ucuz'),

                Forms\Components\Toggle::make('is_default')
                    ->label('Kalkulyator açılanda seçili gəlsin')
                    ->helperText('Yalnız bir şəhərdə açıq olmalıdır.'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Siyahıda sırası')->numeric()->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')->default(true)
                    ->helperText('Söndürsəniz şəhər siyahıda görünmür.'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Şəhər əmsalı yalnız iş haqqına tətbiq olunur, material qiymətlərinə yox. '
                .'Bakı 1.00 bazadır; siyahıda olmayan şəhər də 1.00 sayılır.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Şəhər')
                    // Translated JSON column: search every locale path, or
                    // Azerbaijani letters never match.
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(
                        fn (Builder $q) => $q
                            ->where('name->az', 'like', "%{$search}%")
                            ->orWhere('name->ru', 'like', "%{$search}%")
                            ->orWhere('name->en', 'like', "%{$search}%")
                            ->orWhere('city_key', 'like', "%{$search}%")
                    )),

                Tables\Columns\TextInputColumn::make('coefficient')
                    ->label('Əmsal')
                    ->rules(['numeric', 'min:0'])
                    ->tooltip('1.00 = Bakı ilə eyni'),

                Tables\Columns\TextColumn::make('coefficient')
                    ->label('İşlərə təsiri')
                    ->state(function (CalculatorCity $record): string {
                        $percent = round(((float) $record->coefficient - 1) * 100);

                        return match (true) {
                            $percent > 0 => "işlər +{$percent}% baha",
                            $percent < 0 => 'işlər '.abs($percent).'% ucuz',
                            default => 'Bakı ilə eyni',
                        };
                    })
                    ->color(fn (CalculatorCity $record) => (float) $record->coefficient > 1 ? 'warning' : 'gray'),

                Tables\Columns\IconColumn::make('is_default')->label('Defolt')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),

                Tables\Columns\TextColumn::make('city_key')
                    ->label('Texniki açar')->size('xs')->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Actions\EditAction::make()->label('Redaktə')])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorCities::route('/'),
            'create' => Pages\CreateCalculatorCity::route('/create'),
            'edit' => Pages\EditCalculatorCity::route('/{record}/edit'),
        ];
    }
}
