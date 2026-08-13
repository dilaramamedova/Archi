<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\TranslatesAdminMessages;
use App\Filament\Resources\SearchSynonymResource\Pages;
use App\Models\SearchSynonym;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Unique;

class SearchSynonymResource extends Resource
{
    use TranslatesAdminMessages;

    protected static ?string $model = SearchSynonym::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-magnifying-glass';
    protected static string | \UnitEnum | null $navigationGroup = 'Kataloq';
    protected static ?int $navigationSort = 6;
    protected static ?string $navigationLabel = 'Axtarış sinonimləri';
    protected static ?string $modelLabel = 'Axtarış sinonimi';
    protected static ?string $pluralModelLabel = 'Axtarış sinonimləri';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\Select::make('sub_category_id')
                    ->label('Məhsul sinfi')
                    ->relationship('subCategory', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('phrase')
                    ->label('İfadə')
                    ->required()
                    ->maxLength(255)
                    // search_synonyms has a UNIQUE(sub_category_id, phrase, locale)
                    // index — without this scoped rule a duplicate reached the DB
                    // and surfaced as an SQLSTATE 23000 500 instead of a form error.
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: fn (Unique $rule, callable $get): Unique => $rule
                            ->where('sub_category_id', $get('sub_category_id'))
                            ->where('locale', $get('locale')),
                    )
                    ->validationMessages([
                        'unique' => static::adminMessage(
                            'admin-catalog.synonym.duplicate',
                            'Bu ifadə seçilmiş məhsul sinfi və dil üçün artıq mövcuddur.',
                        ),
                    ])
                    ->helperText('Axtarışda bu ifadə yazılanda sinfin məhsulları tapılacaq.'),

                Forms\Components\Select::make('locale')
                    ->label('Dil')
                    ->options([
                        'az' => 'Azərbaycan',
                        'ru' => 'Русский',
                        'en' => 'English',
                    ])
                    ->default('az')
                    ->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                Tables\Columns\TextColumn::make('phrase')->label('İfadə')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('subCategory.name')->label('Məhsul sinfi'),
                Tables\Columns\TextColumn::make('locale')->label('Dil')->badge(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('sub_category_id')
                    ->label('Məhsul sinfi')
                    ->relationship('subCategory', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('locale')
                    ->label('Dil')
                    ->options([
                        'az' => 'Azərbaycan',
                        'ru' => 'Русский',
                        'en' => 'English',
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
            'index' => Pages\ListSearchSynonyms::route('/'),
            'create' => Pages\CreateSearchSynonym::route('/create'),
            'edit' => Pages\EditSearchSynonym::route('/{record}/edit'),
        ];
    }
}
