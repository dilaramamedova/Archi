<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqQuestionResource\Pages;
use App\Models\FaqQuestion;
use App\Models\FaqTopic;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqQuestionResource extends Resource
{
    protected static ?string $model = FaqQuestion::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static string | \UnitEnum | null $navigationGroup = 'FAQ';
    protected static ?int $navigationSort = 81;
    protected static ?string $navigationLabel = 'FAQ Sualları';
    protected static ?string $modelLabel = 'FAQ Sualı';
    protected static ?string $pluralModelLabel = 'FAQ Sualları';

    protected static function topicOptions(): array
    {
        return FaqTopic::ordered()->get()
            ->mapWithKeys(fn ($t) => [$t->id => $t->getTranslation('title', 'az')])
            ->all();
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\Select::make('faq_topic_id')
                    ->label('Mövzu')
                    ->options(fn () => static::topicOptions())
                    ->searchable()
                    ->required(),

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
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                Tables\Columns\TextColumn::make('topic')
                    ->label('Mövzu')
                    ->state(fn ($record) => $record->topic?->getTranslation('title', 'az'))
                    ->badge(),
                Tables\Columns\TextColumn::make('question')
                    ->label('Sual')
                    ->searchable()
                    ->limit(60)
                    ->wrap(),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('faq_topic_id')
                    ->label('Mövzu')
                    ->options(fn () => static::topicOptions()),
            ])
            ->defaultSort('faq_topic_id')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqQuestions::route('/'),
            'create' => Pages\CreateFaqQuestion::route('/create'),
            'edit' => Pages\EditFaqQuestion::route('/{record}/edit'),
        ];
    }
}
