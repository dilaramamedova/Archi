<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutStatResource\Pages;
use App\Models\AboutStat;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AboutStatResource extends Resource
{
    protected static ?string $model = AboutStat::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-chart-bar';
    protected static string | \UnitEnum | null $navigationGroup = 'Haqqımızda';
    protected static ?string $navigationLabel = 'Statistikalar';
    protected static ?string $modelLabel = 'Statistika';
    protected static ?string $pluralModelLabel = 'Statistikalar';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('value')->label('Dəyər (rəqəm)')->required()->translatable(),
                Forms\Components\TextInput::make('label')->label('Alt yazı')->required()->translatable(),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('value')->label('Dəyər'),
                Tables\Columns\TextColumn::make('label')->label('Alt yazı'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAboutStats::route('/'),
            'create' => Pages\CreateAboutStat::route('/create'),
            'edit' => Pages\EditAboutStat::route('/{record}/edit'),
        ];
    }
}
