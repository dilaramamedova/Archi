<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ServiceIconResource\Pages;
use App\Models\ServiceIcon;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ServiceIconResource extends Resource
{
    protected static ?string $model = ServiceIcon::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';
    protected static string | \UnitEnum | null $navigationGroup = 'Ana səhifə';
    protected static ?string $navigationLabel = 'Xidmət ikonları';
    protected static ?string $modelLabel = 'Xidmət ikonu';
    protected static ?string $pluralModelLabel = 'Xidmət ikonları';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('title')->label('Başlıq')->required()->translatable(),
                Forms\Components\TextInput::make('text')->label('Alt yazı')->translatable(),

                Forms\Components\FileUpload::make('icon')
                    ->label('İkon')
                    ->image()
                    ->disk('public')
                    ->directory('service-icons'),

                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('icon')->label('İkon')->width(40)->height(40),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->limit(40),
                Tables\Columns\TextColumn::make('text')->label('Alt yazı')->limit(40),
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
            'index' => Pages\ListServiceIcons::route('/'),
            'create' => Pages\CreateServiceIcon::route('/create'),
            'edit' => Pages\EditServiceIcon::route('/{record}/edit'),
        ];
    }
}
