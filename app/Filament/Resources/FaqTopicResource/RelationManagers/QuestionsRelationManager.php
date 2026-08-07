<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqTopicResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    protected static ?string $title = 'Suallar';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Textarea::make('question')
                ->label('Sual')
                ->rows(2)
                ->required()
                ->translatable(),
            Forms\Components\Textarea::make('answer')
                ->label('Cavab')
                ->rows(4)
                ->required()
                ->translatable(),
            Forms\Components\TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(0),
            Forms\Components\Toggle::make('is_active')
                ->label('Aktiv')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')->label('Sual')->searchable()->limit(60)->wrap(),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([Actions\CreateAction::make()])
            ->recordActions([Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }
}
