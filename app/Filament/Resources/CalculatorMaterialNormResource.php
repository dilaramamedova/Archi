<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CalculatorMaterialNormResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorMaterialNorm;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Consumption norms and technological waste percentages (master spec §12.1).
 *
 * The waste percentage lives inside the required quantity of its material. It is not
 * the financial reserve — that is a single separate line at the bottom of the
 * estimate, and the two must never be applied on top of each other (§15).
 */
class CalculatorMaterialNormResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorMaterialNorm::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-beaker';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Material sərfiyyatı və ehtiyatı';
    protected static ?string $modelLabel = 'sərfiyyat norması';
    protected static ?string $pluralModelLabel = 'Material sərfiyyatı və ehtiyatı';
    protected static ?int $navigationSort = 5;

    /** What each parameter means in a sentence a manager can check against reality. */
    private const PARAMETERS = [
        'WastePercent' => 'Kəsim ehtiyatı: materialın neçə faizi artıq alınmalıdır',
        'CoverageM2PerLiter' => '1 litr neçə m² örtür',
        'ConsumptionLPerM2' => '1 m²-ə neçə litr gedir',
        'ConsumptionKgPerM2' => '1 m²-ə neçə kq gedir',
        'ConsumptionKgPerM2PerLayer' => '1 m²-ə, hər qat üçün neçə kq gedir',
        'ConsumptionKgPerM2PerMm' => '1 m²-ə, hər 1 mm qalınlıq üçün neçə kq gedir',
        'ConsumptionPerM2PerLayer' => '1 m²-ə, hər qat üçün neçə kq/litr gedir',
    ];

    public static function explanationParts(): array
    {
        return [
            'what' => 'Materialın <strong>nə qədər lazım olduğunu</strong> hesablayan normalar. '
                .'İki cür var: kəsim ehtiyatı (kafelin neçə faizi artıq alınmalıdır) və '
                .'sərfiyyat (1 m²-ə neçə kq yapışqan gedir).',
            'where' => 'Ətraflı kalkulyatorun «Materiallar» addımında və yekun smetada — '
                .'material sətrinin miqdarında. Məsələn: 30 m² kafel + 10% ehtiyat = 33 m².',
            'rules' => [
                'Kəsim ehtiyatı <strong>materialın miqdarının içindədir</strong>. '
                    .'Smetanın sonundakı «Maliyyə ehtiyatı» isə ayrı bir şeydir — '
                    .'onu «Ümumi parametrlər» səhifəsində idarə edirsiniz. İkisi bir-birinin üstünə gəlmir.',
                'Sərfiyyat normaları texniki rəqəmlərdir — adətən materialın qablaşdırmasında yazılır. '
                    .'Şübhə etsəniz, təchizatçınızdan soruşun.',
                'Rəqəmi böyütsəniz material çox alınır (smeta bahalaşır), kiçiltsəniz az alınır '
                    .'(iş yarımçıq qala bilər).',
            ],
        ];
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            self::explainer(),

            Section::make('Norma')
                ->schema([
                    Forms\Components\TextInput::make('rule_id')
                        ->label('Texniki ID')
                        ->required()
                        ->disabledOn('edit'),

                    Forms\Components\TextInput::make('name')
                        ->label('Hansı material')
                        ->required()
                        ->helperText('Yalnız admin panelində görünür.')
                        ->translatable(),

                    Forms\Components\Select::make('parameter')
                        ->label('Nəyi ölçür')
                        ->options(self::PARAMETERS)
                        ->required()
                        ->helperText('Aşağıdakı rəqəmin mənasını bu seçim müəyyən edir.'),

                    Forms\Components\TextInput::make('value')
                        ->label('Dəyər')
                        ->numeric()
                        ->required()
                        ->helperText('Kəsim ehtiyatı üçün faiz yazın (10 = 10%). Sərfiyyat üçün kq və ya litr.'),

                    Forms\Components\TextInput::make('unit')
                        ->label('Vahid')
                        ->helperText('Yalnız izah üçün, hesablamaya təsir etmir.'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')
                        ->default(true)
                        ->helperText('Söndürsəniz bu norma tətbiq olunmur — ehtiyat 0%, sərfiyyat hesablanmır.'),

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
            ->description('Kəsim ehtiyatı = materialın neçə faizi artıq alınır. '
                .'Sərfiyyat = 1 m²-ə nə qədər material gedir. Smetanın sonundakı maliyyə ehtiyatı ilə '
                .'qarışdırmayın — o, «Parametrlər» səhifəsindədir.')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Material')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('parameter')
                    ->label('Nəyi ölçür')
                    ->formatStateUsing(fn ($state) => self::PARAMETERS[$state] ?? $state)
                    ->wrap()
                    ->color('gray'),

                Tables\Columns\TextInputColumn::make('value')
                    ->label('Dəyər')
                    ->rules(['numeric', 'min:0']),

                Tables\Columns\TextColumn::make('unit')->label('Vahid')->badge()->color('gray'),

                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('parameter')
                    ->label('Növ')
                    ->options(self::PARAMETERS),
            ])
            ->defaultSort('rule_id')
            ->actions([Actions\EditAction::make()->label('Redaktə')])
            ->bulkActions([Actions\DeleteBulkAction::make()])
            ->paginated([25, 50, 'all']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorMaterialNorms::route('/'),
            'create' => Pages\CreateCalculatorMaterialNorm::route('/create'),
            'edit' => Pages\EditCalculatorMaterialNorm::route('/{record}/edit'),
        ];
    }
}
