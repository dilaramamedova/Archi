<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutTeamMemberResource\Pages;
use App\Models\AboutTeamMember;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AboutTeamMemberResource extends Resource
{
    protected static ?string $model = AboutTeamMember::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static string | \UnitEnum | null $navigationGroup = 'Haqqımızda';
    protected static ?string $navigationLabel = 'Komanda';
    protected static ?string $modelLabel = 'Komanda üzvü';
    protected static ?string $pluralModelLabel = 'Komanda üzvləri';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Əsas məlumatlar')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Ad')
                    ->required()
                    ->translatable(),

                Forms\Components\TextInput::make('role')
                    ->label('Vəzifə')
                    ->required()
                    ->translatable(),

                Forms\Components\FileUpload::make('image')
                    ->label('Şəkil')
                    ->image()
                    ->disk('public')
                    ->directory('about/team'),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')->label('Şəkil')->width(50)->height(50)->circular(),
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('role')->label('Vəzifə'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAboutTeamMembers::route('/'),
            'create' => Pages\CreateAboutTeamMember::route('/create'),
            'edit' => Pages\EditAboutTeamMember::route('/{record}/edit'),
        ];
    }
}
