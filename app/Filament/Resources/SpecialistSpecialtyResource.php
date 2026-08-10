<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\SpecialistSpecialtyResource\Pages;
use App\Models\SpecialistSpecialty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

final class SpecialistSpecialtyResource extends Resource
{
    protected static ?string $model = SpecialistSpecialty::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';

    protected static string|\UnitEnum|null $navigationGroup = 'İstifadəçilər';

    protected static ?string $navigationLabel = 'Mütəxəssis ixtisasları';

    protected static ?string $modelLabel = 'Mütəxəssis ixtisası';

    protected static ?string $pluralModelLabel = 'Mütəxəssis ixtisasları';

    protected static ?string $slug = 'specialist-specialties';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('İxtisas')->schema([
                Forms\Components\TextInput::make('name')->label('Ad')->maxLength(150)
                    // The az/ru/en tab strip does not fit in a half-width column: it
                    // overflowed and left the English tab unreachable.
                    ->columnSpanFull()
                    ->translatable(
                        // Only the Azerbaijani tab drives the slug and is mandatory.
                        modifyLocalizedFieldUsing: function (Forms\Components\TextInput $field, string $locale): Forms\Components\TextInput {
                            if ($locale !== 'az') {
                                return $field;
                            }

                            return $field->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, callable $set, $record): void {
                                    if ($record === null) {
                                        $set('slug', Str::slug((string) $state));
                                    }
                                });
                        },
                    ),
                Forms\Components\TextInput::make('slug')->label('Slug')->required()->maxLength(160)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->minValue(0)->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('İxtisas')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('specialists_count')->label('Mütəxəssis sayı')->counts('specialists'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpecialistSpecialties::route('/'),
            'create' => Pages\CreateSpecialistSpecialty::route('/create'),
            'edit' => Pages\EditSpecialistSpecialty::route('/{record}/edit'),
        ];
    }
}
