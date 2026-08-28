<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorMaterialFallbackResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorMaterialFallback;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Category prices used while the visitor has not chosen a real product yet
 * (master spec §12). As soon as a catalog product is picked, its price replaces
 * these and the estimate line is promoted from "hesablanmış" to "dəqiq".
 */
class CalculatorMaterialFallbackResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorMaterialFallback::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-swatch';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Material qiymətləri';
    protected static ?string $modelLabel = 'material qiyməti';
    protected static ?string $pluralModelLabel = 'Material qiymətləri';
    protected static ?int $navigationSort = 3;

    private const GROUPS = [
        'FLOOR' => 'Döşəmə materialları',
        'WALL' => 'Divar materialları',
        'CEILING' => 'Tavan materialları',
        'DOORS' => 'Qapılar',
        'PLUMBING' => 'Santexnika',
        'LIGHTING' => 'İşıqlandırma',
        'ELECTRICAL' => 'Elektrik',
        'ROUGH' => 'Qaba materiallar (qrunt, suvaq, yapışqan…)',
    ];

    public static function explanationParts(): array
    {
        return [
            'text' => 'Müştəri kataloqdan konkret məhsul seçmədikdə istifadə olunan <strong>orta '
                .'material qiymətləri</strong>. Seçdiyi səviyyəyə (Ekonom/Standart/Premium) uyğun sütun işləyir.',
            'shot' => 'material-fallbacks',
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('Material haqqında')
                ->schema([
                    Forms\Components\TextInput::make('material_category_id')
                        ->label('Texniki ID')
                        ->required()
                        ->disabledOn('edit')
                        ->helperText('Proqramın daxili açarı. Yaradılandan sonra dəyişdirilmir.'),

                    Forms\Components\Select::make('surface_group')
                        ->label('Hansı qrupdur')
                        ->options(self::GROUPS)
                        ->required(),

                    Forms\Components\TextInput::make('name')
                        ->label('Materialın adı')
                        ->required()
                        ->helperText('Müştəri smetada bu adı görür.')
                        ->translatable()
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('unit')
                        ->label('Ölçü vahidi')
                        ->required()
                        ->datalist(['m²', 'p.m', 'əd', 'kq', 'L', 'nöqtə'])
                        ->helperText('Aşağıdakı qiymətlər bu vahidin biri üçündür.'),
                ])
                ->columns(2),

            Section::make('Səviyyəyə görə qiymətlər')
                ->description('Müştərinin seçdiyi material səviyyəsinə uyğun sütun işləyir')
                ->schema([
                    Forms\Components\TextInput::make('economy_price')
                        ->label('Ekonom')
                        ->numeric()->minValue(0)->suffix('AZN')
                        ->helperText('Ən sadə variant.'),

                    Forms\Components\TextInput::make('standard_price')
                        ->label('Standart')
                        ->numeric()->minValue(0)->suffix('AZN')
                        ->helperText('Orta variant — ən çox seçilən.'),

                    Forms\Components\TextInput::make('premium_price')
                        ->label('Premium')
                        ->numeric()->minValue(0)->suffix('AZN')
                        ->helperText('Yüksək seqment.'),
                ])
                ->columns(3),

            Section::make('Digər')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Daxili qeyd')
                        ->rows(2)
                        ->helperText('Yalnız admin panelində görünür.')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Sıralama nömrəsi')->numeric()->default(0),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')->default(true)
                        ->helperText('Söndürsəniz bu material hesablamada istifadə olunmur.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description('Müştəri kataloqdan məhsul seçməyəndə istifadə olunan orta qiymətlər. '
                .'Müştərinin 1-ci addımda seçdiyi səviyyəyə (Ekonom/Standart/Premium) uyğun sütun işə düşür.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Material')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('surface_group')
                    ->label('Qrup')
                    ->formatStateUsing(fn ($state) => self::GROUPS[$state] ?? $state)
                    ->badge(),

                Tables\Columns\TextColumn::make('unit')
                    ->label('Nəyin biri üçün')
                    ->badge()->color('gray'),

                Tables\Columns\TextInputColumn::make('economy_price')
                    ->label('Ekonom, AZN')->rules(['nullable', 'numeric', 'min:0'])->placeholder('—'),

                Tables\Columns\TextInputColumn::make('standard_price')
                    ->label('Standart, AZN')->rules(['nullable', 'numeric', 'min:0'])->placeholder('—'),

                Tables\Columns\TextInputColumn::make('premium_price')
                    ->label('Premium, AZN')->rules(['nullable', 'numeric', 'min:0'])->placeholder('—'),

                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('surface_group')->label('Qrup')->options(self::GROUPS),
                Tables\Filters\Filter::make('missing_price')
                    ->label('Qiyməti doldurulmayanlar')
                    ->query(fn ($query) => $query->whereNull('standard_price')),
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
            'index' => Pages\ListCalculatorMaterialFallbacks::route('/'),
            'create' => Pages\CreateCalculatorMaterialFallback::route('/create'),
            'edit' => Pages\EditCalculatorMaterialFallback::route('/{record}/edit'),
        ];
    }
}
