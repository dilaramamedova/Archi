<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\MenuItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-bars-3';
    protected static string | \UnitEnum | null $navigationGroup = 'Naviqasiya';
    protected static ?string $navigationLabel = 'Menyu elementləri';
    protected static ?string $modelLabel = 'Menyu elementi';
    protected static ?string $pluralModelLabel = 'Menyu elementləri';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\Select::make('location')
                    ->label('Mövqe')
                    ->options([
                        'header_main' => 'Header — Əsas naviqasiya',
                        'header_mega_catalog' => 'Header — Mega Kataloq',
                        'header_mega_specialists' => 'Header — Mega Mütəxəssislər',
                        'header_mega_blog' => 'Header — Mega Bloq',
                        'footer' => 'Footer — Menyu',
                        'footer_legal' => 'Footer — Hüquqi linklər',
                    ])
                    ->required(),

                Forms\Components\Select::make('parent_id')
                    ->label('Ana element')
                    ->relationship('parent', 'label')
                    ->searchable()
                    ->nullable()
                    ->preload(),

                Forms\Components\TextInput::make('label')->label('Mətn')->required()->translatable(),

                Forms\Components\TextInput::make('url')->label('URL')->nullable(),
                Forms\Components\TextInput::make('route_name')->label('Route adı')->nullable(),

                Forms\Components\Textarea::make('description')->label('Açıqlama')->rows(2)->translatable(),

                Forms\Components\FileUpload::make('icon')
                    ->label('İkon')
                    ->directory('menu/icons')
                    ->nullable(),

                Forms\Components\FileUpload::make('image')
                    ->label('Şəkil')
                    ->image()
                    ->directory('menu/images')
                    ->nullable(),

                Forms\Components\TextInput::make('css_class')->label('CSS class')->nullable(),

                Forms\Components\Toggle::make('has_dropdown')->label('Dropdown var?'),
                Forms\Components\Toggle::make('is_clickable')->label('Klik oluna bilər?')->default(true),
                Forms\Components\Toggle::make('open_in_new_tab')->label('Yeni tab-da aç'),
                Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')->label('Mətn')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('location')->label('Mövqe')->badge(),
                Tables\Columns\TextColumn::make('parent.label')->label('Ana element')->placeholder('—'),
                Tables\Columns\TextColumn::make('url')->label('URL')->placeholder('—')->limit(30),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('location')
                    ->label('Mövqe')
                    ->options([
                        'header_main' => 'Header əsas',
                        'header_mega_catalog' => 'Mega Kataloq',
                        'header_mega_specialists' => 'Mega Mütəxəssislər',
                        'footer' => 'Footer',
                        'footer_legal' => 'Footer hüquqi',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
