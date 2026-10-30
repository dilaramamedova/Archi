<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TenderCategoryResource\Pages;
use App\Models\TenderCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class TenderCategoryResource extends Resource
{
    protected static ?string $model = TenderCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'Tenderlər';

    protected static ?string $navigationLabel = 'Kateqoriyalar';

    protected static ?string $modelLabel = 'Tender kateqoriyası';

    protected static ?string $pluralModelLabel = 'Tender kateqoriyaları';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Ad')
                ->maxLength(120)
                ->translatable(modifyLocalizedFieldUsing: fn (Forms\Components\TextInput $field, string $locale) => $field->required($locale === 'az')),
            Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->helperText('Boş qalsa, addan yaradılır.')
                ->alphaDash()
                ->maxLength(120)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('tenders'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
                Tables\Columns\TextColumn::make('tenders_count')->label('Tender sayı'),
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
            'index' => Pages\ListTenderCategories::route('/'),
            'create' => Pages\CreateTenderCategory::route('/create'),
            'edit' => Pages\EditTenderCategory::route('/{record}/edit'),
        ];
    }
}
