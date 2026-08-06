<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AboutStepResource\Pages;
use App\Models\AboutStep;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AboutStepResource extends Resource
{
    protected static ?string $model = AboutStep::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-list-bullet';
    protected static string | \UnitEnum | null $navigationGroup = 'Haqqımızda';
    protected static ?string $navigationLabel = 'Addımlar';
    protected static ?string $modelLabel = 'Addım';
    protected static ?string $pluralModelLabel = 'Addımlar';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Əsas məlumatlar')->schema([
                Forms\Components\TextInput::make('step_number')
                    ->label('Addım nömrəsi')
                    ->numeric()
                    ->required()
                    ->default(1),

                Forms\Components\TextInput::make('title')
                    ->label('Başlıq')
                    ->required()
                    ->translatable(),

                Forms\Components\Textarea::make('description')
                    ->label('Təsvir')
                    ->rows(3)
                    ->translatable(),

                Forms\Components\FileUpload::make('image')
                    ->label('Şəkil')
                    ->image()
                    ->disk('public')
                    ->directory('about/steps'),

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
                Tables\Columns\TextColumn::make('step_number')->label('№')->sortable()->width(50),
                Tables\Columns\ImageColumn::make('image')->label('Şəkil')->width(60)->height(40),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->searchable(),
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
            'index' => Pages\ListAboutSteps::route('/'),
            'create' => Pages\CreateAboutStep::route('/create'),
            'edit' => Pages\EditAboutStep::route('/{record}/edit'),
        ];
    }
}
