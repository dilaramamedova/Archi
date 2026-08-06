<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegalPageResource\Pages;
use App\Models\LegalPage;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LegalPageResource extends Resource
{
    protected static ?string $model = LegalPage::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';
    protected static string | \UnitEnum | null $navigationGroup = 'Təhlükəsizlik Səhifələri';
    protected static ?string $navigationLabel = 'Hüquqi səhifələr';
    protected static ?string $modelLabel = 'Hüquqi səhifə';
    protected static ?string $pluralModelLabel = 'Hüquqi səhifələr';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Məzmun')->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Başlıq')
                    ->required()
                    ->translatable(),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->disabled(fn ($record) => $record !== null)
                    ->dehydrated(),

                Forms\Components\RichEditor::make('content')
                    ->label('Məzmun')
                    ->translatable()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('meta_description')
                    ->label('Meta təsvir (SEO)')
                    ->translatable(),

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
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
                Tables\Columns\TextColumn::make('updated_at')->label('Yenilənib')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->actions([
                Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegalPages::route('/'),
            'create' => Pages\CreateLegalPage::route('/create'),
            'edit' => Pages\EditLegalPage::route('/{record}/edit'),
        ];
    }
}
