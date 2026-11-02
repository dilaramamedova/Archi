<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\B2bCategoryResource\Pages;
use App\Models\B2bCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class B2bCategoryResource extends Resource
{
    protected static ?string $model = B2bCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'B2B';

    protected static ?string $navigationLabel = 'Kateqoriyalar';

    protected static ?string $modelLabel = 'B2B kateqoriyası';

    protected static ?string $pluralModelLabel = 'B2B kateqoriyaları';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Ad')
                ->maxLength(120)
                ->translatable(modifyLocalizedFieldUsing: fn (Forms\Components\TextInput $field, string $locale) => $field->required($locale === 'az')),
            Forms\Components\TextInput::make('slug')->label('Slug')->helperText('Boş qalsa, addan yaradılır.')->alphaDash()->maxLength(120)->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('companies'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
                Tables\Columns\TextColumn::make('companies_count')->label('Şirkət sayı'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktiv')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListB2bCategories::route('/'),
            'create' => Pages\CreateB2bCategory::route('/create'),
            'edit' => Pages\EditB2bCategory::route('/{record}/edit'),
        ];
    }
}
