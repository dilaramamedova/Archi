<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromoBannerResource\Pages;
use App\Models\PromoBanner;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PromoBannerResource extends Resource
{
    protected static ?string $model = PromoBanner::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';
    protected static string | \UnitEnum | null $navigationGroup = 'Kontent';
    protected static ?string $navigationLabel = 'Kampaniya bannerləri';
    protected static ?string $modelLabel = 'Kampaniya banneri';
    protected static ?string $pluralModelLabel = 'Kampaniya bannerləri';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('badge_text')->label('Badge mətni')->translatable(),
                Forms\Components\TextInput::make('title')->label('Başlıq')->required()->translatable(),
                Forms\Components\Textarea::make('description')->label('Açıqlama')->rows(2)->translatable(),
                Forms\Components\TextInput::make('code')->label('Promo kodu')->nullable(),
                Forms\Components\TextInput::make('button_text')->label('Düymə mətni')->translatable(),
                Forms\Components\TextInput::make('button_url')->label('Düymə linki')->nullable(),
                Forms\Components\ColorPicker::make('background_color')->label('Arxa plan rəngi')->nullable(),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->limit(40),
                Tables\Columns\TextColumn::make('code')->label('Kod')->badge(),
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
            'index' => Pages\ListPromoBanners::route('/'),
            'create' => Pages\CreatePromoBanner::route('/create'),
            'edit' => Pages\EditPromoBanner::route('/{record}/edit'),
        ];
    }
}
