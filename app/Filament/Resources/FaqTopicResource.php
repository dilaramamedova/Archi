<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqTopicResource\Pages;
use App\Filament\Resources\FaqTopicResource\RelationManagers\QuestionsRelationManager;
use App\Models\FaqTopic;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqTopicResource extends Resource
{
    protected static ?string $model = FaqTopic::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | \UnitEnum | null $navigationGroup = 'FAQ';
    protected static ?int $navigationSort = 80;
    protected static ?string $navigationLabel = 'FAQ Mövzuları';
    protected static ?string $modelLabel = 'FAQ Mövzusu';
    protected static ?string $pluralModelLabel = 'FAQ Mövzuları';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\TextInput::make('title')
                    ->label('Başlıq')
                    ->required()
                    ->translatable()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $set, $record) {
                        if (! $record) {
                            $value = $state;
                            while (is_array($value)) {
                                $value = $value['az'] ?? reset($value) ?: '';
                            }
                            $set('slug', \Illuminate\Support\Str::slug((string) $value));
                        }
                    }),

                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\Textarea::make('description')
                    ->label('Təsvir')
                    ->rows(2)
                    ->translatable(),

                Forms\Components\TextInput::make('icon')
                    ->label('İkon (emoji)')
                    ->maxLength(8)
                    ->helperText('Emoji, məs. 📦'),

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
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                Tables\Columns\TextColumn::make('icon')->label('İkon'),
                Tables\Columns\TextColumn::make('title')->label('Başlıq')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('questions_count')
                    ->label('Sual sayı')
                    ->counts('questions')
                    ->sortable(),
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

    public static function getRelations(): array
    {
        return [
            QuestionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqTopics::route('/'),
            'create' => Pages\CreateFaqTopic::route('/create'),
            'edit' => Pages\EditFaqTopic::route('/{record}/edit'),
        ];
    }
}
