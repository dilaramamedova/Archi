<?php

namespace App\Filament\Resources;

use App\Enums\Calculator\RoomType;
use App\Filament\Resources\CalculatorRoomTypeResource\Pages;
use App\Filament\Resources\Concerns\ExplainsCalculator;
use App\Models\CalculatorRoomType;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * «Otaq növləri» — the room types and their «Sadə» mode norms (TZ 17.11.2026 §6,
 * §34). A visitor who does not count sockets gets these: a base per room plus a
 * share per m² of floor, rounded up; the sanitary fixtures of the type; whether it
 * has a water line and whether it is waterproofed.
 *
 * The types themselves are fixed by the engine (App\Enums\Calculator\RoomType), so
 * rows are edited, never created or deleted here.
 */
class CalculatorRoomTypeResource extends Resource
{
    use ExplainsCalculator;

    protected static ?string $model = CalculatorRoomType::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-home-modern';
    protected static string | \UnitEnum | null $navigationGroup = 'Kalkulyator';
    protected static ?string $navigationLabel = 'Otaq növləri';
    protected static ?string $modelLabel = 'otaq növü';
    protected static ?string $pluralModelLabel = 'Otaq növləri';
    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'name';

    /** Point key => what the admin calls it. Spots are not here: they follow the ceiling. */
    public const POINTS = [
        'sockets' => 'Rozetka',
        'switches' => 'Açar',
        'lights_chandelier' => 'Çilçıraq',
        'lights_pendant' => 'Asma işıq',
        'wall_lights' => 'Bra (divar işığı)',
        'tv' => 'TV nöqtəsi',
        'data' => 'İnternet nöqtəsi',
        'intercom' => 'Domofon',
        'ac_power' => 'Kondisioner',
        'appliance' => 'Texnika nöqtəsi (mətbəx)',
    ];

    public const FIXTURES = [
        'toilet' => 'Unitaz',
        'basin' => 'Lavabo',
        'shower' => 'Duş',
        'bathtub' => 'Vanna',
        'hygienic_shower' => 'Gigiyenik duş',
        'washer' => 'Paltaryuyan maşın',
        'kitchen_sink' => 'Mətbəx yuyucusu',
        'dishwasher' => 'Qabyuyan maşın',
        'mixer' => 'Qarışdırıcı',
        'towel_rail' => 'Dəsmalqurudan',
        'boiler' => 'Boyler',
    ];

    public static function explanationParts(): array
    {
        return [
            'text' => 'Müştəri otaqda elektrik və santexnika nöqtələrini saymırsa («Sadə» rejim), kalkulyator onları buradakı '
                .'<strong>normalarla</strong> özü hesablayır: <strong>baza + hər m²-ə düşən pay</strong>, yuxarı yuvarlaqlaşdırılır '
                .'(məs. rozetka 3 + 0.25/m² → 16 m² yataq otağına 7). Spotlar alçıpan / dartma tavanlı otaqda «Parametrlər» '
                .'bölməsindəki SPOT_AREA_M2-yə görə hesablanır. Su və kanalizasiya nöqtələri cihazlardan çıxır: unitaz, lavabo, duş, '
                .'vanna, paltaryuyan və qabyuyan maşın, mətbəx yuyucusu — 1 su + 1 kanalizasiya, gigiyenik duş — yalnız su.',
            'shot' => null,
        ];
    }

    public static function form(Schema $form): Schema
    {
        $pointRows = [];

        foreach (self::POINTS as $key => $label) {
            $pointRows[] = Forms\Components\TextInput::make("point_norms.{$key}.base")
                ->label($label.' — baza')
                ->numeric()->minValue(0)->step(1)
                ->placeholder('0');

            $pointRows[] = Forms\Components\TextInput::make("point_norms.{$key}.per_m2")
                ->label($label.' — hər m²-ə')
                ->numeric()->minValue(0)->step(0.01)
                ->placeholder('0');
        }

        $fixtureFields = [];

        foreach (self::FIXTURES as $key => $label) {
            $fixtureFields[] = Forms\Components\TextInput::make("fixtures.{$key}")
                ->label($label)
                ->numeric()->minValue(0)->step(1)
                ->placeholder('0');
        }

        return $form->schema([
            self::explainer(),

            Section::make('Otaq növü')
                ->schema([
                    Forms\Components\Select::make('room_type')
                        ->label('Texniki ID')
                        ->options(array_combine(
                            array_map(fn (RoomType $t) => $t->value, RoomType::cases()),
                            array_map(fn (RoomType $t) => $t->value, RoomType::cases()),
                        ))
                        ->disabled()
                        ->helperText('Otaq növləri kalkulyatorun özündə müəyyən edilib; burada yalnız onların normaları dəyişdirilir.'),

                    Forms\Components\TextInput::make('name')
                        ->label('Adı')
                        ->required()
                        ->helperText('Müştəri otaq növü siyahısında bu adı görür.')
                        ->translatable(),

                    Forms\Components\Toggle::make('has_plumbing')
                        ->label('Su xətti var')
                        ->helperText('Sanitar qovşaq, mətbəx: otaqda santexnika cihazları və nöqtələri ola bilər.'),

                    Forms\Components\Toggle::make('needs_waterproofing')
                        ->label('Hidroizolyasiya olunur')
                        ->helperText('Yandırılıbsa, bütün döşəmə və bütün divarlar hidroizolyasiya olunur (Archi-nin qərarı: yalnız hamam və tualet).'),

                    Forms\Components\TextInput::make('ac_extra_above_m2')
                        ->label('Əlavə kondisioner, sahə bundan böyük olduqda')
                        ->numeric()->minValue(0)->suffix('m²')
                        ->helperText('Məs. 25: 25 m²-dən böyük qonaq otağına 2-ci kondisioner. Boş = əlavə yoxdur.'),

                    Forms\Components\TextInput::make('tv_min_area_m2')
                        ->label('TV nöqtəsi yalnız sahə bundan az olmadıqda')
                        ->numeric()->minValue(0)->suffix('m²')
                        ->helperText('Məs. 15: 12 m² yataq otağında TV nöqtəsi hesablanmır. Boş = həmişə.'),
                ])
                ->columns(2),

            Section::make('Elektrik nöqtələrinin norması')
                ->description('Miqdar = baza + pay × otağın sahəsi, yuxarı yuvarlaqlaşdırılır. Boş = 0.')
                ->schema($pointRows)
                ->columns(4),

            Section::make('Santexnika cihazları (ədəd)')
                ->description('Otaq bu növdə yaradılanda susmaya görə olan cihazlar. Su və kanalizasiya nöqtələri bunlardan hesablanır.')
                ->schema($fixtureFields)
                ->columns(4),

            Section::make('Digər')
                ->schema([
                    Forms\Components\TextInput::make('sort_order')->label('Sıralama nömrəsi')->numeric()->default(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktiv')->default(true)
                        ->helperText('Söndürsəniz, bu növ üçün kalkulyatorun daxili normaları işləyir.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Otaq növü')
                    ->description(fn (CalculatorRoomType $record) => $record->room_type),

                Tables\Columns\TextColumn::make('point_norms')
                    ->label('Elektrik norması')
                    ->state(fn (CalculatorRoomType $record) => self::normSummary((array) $record->point_norms))
                    ->wrap(),

                Tables\Columns\TextColumn::make('fixtures')
                    ->label('Cihazlar')
                    ->state(fn (CalculatorRoomType $record) => collect((array) $record->fixtures)
                        ->filter(fn ($v) => (int) $v > 0)
                        ->map(fn ($v, $k) => (self::FIXTURES[$k] ?? $k).((int) $v > 1 ? ' ×'.(int) $v : ''))
                        ->join(', ') ?: '—')
                    ->wrap(),

                Tables\Columns\IconColumn::make('has_plumbing')->label('Su xətti')->boolean(),
                Tables\Columns\IconColumn::make('needs_waterproofing')->label('Hidroizolyasiya')->boolean(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->defaultSort('sort_order')
            ->actions([Actions\EditAction::make()->label('Redaktə')])
            ->paginated(false);
    }

    /** «Rozetka 3 + 0.25/m², Açar 2, …» — the norm the way Archi wrote it in the TZ. */
    public static function normSummary(array $norms): string
    {
        $parts = [];

        foreach (self::POINTS as $key => $label) {
            $base = (float) ($norms[$key]['base'] ?? 0);
            $perM2 = (float) ($norms[$key]['per_m2'] ?? 0);

            if ($base <= 0 && $perM2 <= 0) {
                continue;
            }

            $parts[] = $label.' '.rtrim(rtrim(number_format($base, 2, '.', ''), '0'), '.')
                .($perM2 > 0 ? ' + '.rtrim(rtrim(number_format($perM2, 2, '.', ''), '0'), '.').'/m²' : '');
        }

        return $parts === [] ? '—' : implode(', ', $parts);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalculatorRoomTypes::route('/'),
            'edit' => Pages\EditCalculatorRoomType::route('/{record}/edit'),
        ];
    }
}
