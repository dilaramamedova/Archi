<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubCategoryResource\Pages;
use App\Filament\Resources\SubCategoryResource\RelationManagers\AttributesRelationManager;
use App\Models\Category;
use App\Models\SubCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubCategoryResource extends Resource
{
    protected static ?string $model = SubCategory::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-group';
    protected static string | \UnitEnum | null $navigationGroup = 'Kataloq';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Məhsul sinifləri';
    protected static ?string $modelLabel = 'Məhsul sinfi';
    protected static ?string $pluralModelLabel = 'Məhsul sinifləri';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Forms\Components\Select::make('category_id')
                    ->label('Qrup')
                    ->relationship(
                        'category',
                        'name',
                        // Product classes always hang off a GROUP-level category.
                        modifyQueryUsing: fn ($query) => $query->whereNotNull('parent_id')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Category $record) => trim(($record->parent?->name ? $record->parent->name.' → ' : '').$record->name))
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('name')
                    ->label('Ad')
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
                    // Nullable in the DB (legacy rows), so we don't hard-require it
                    // when editing old records; new records get it auto-generated.
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Textarea::make('short_description')
                    ->label('Qısa təsvir')
                    ->rows(2)
                    ->translatable(),

                Forms\Components\RichEditor::make('description')
                    ->label('Təsvir')
                    ->translatable(),

                Forms\Components\Select::make('applications')
                    ->label('Tətbiq sahələri')
                    ->relationship('applications', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload(),

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
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('category.name')->label('Qrup'),
                Tables\Columns\TextColumn::make('category.parent.name')->label('Bölmə')->placeholder('—'),
                Tables\Columns\TextColumn::make('attributes_count')
                    ->counts('attributes')
                    ->label('Atributlar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Qrup')
                    ->relationship('category', 'name', modifyQueryUsing: fn ($query) => $query->whereNotNull('parent_id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('section')
                    ->label('Bölmə')
                    ->options(fn () => Category::query()->whereNull('parent_id')->orderBy('sort_order')->get()->mapWithKeys(fn (Category $c) => [$c->id => $c->name])->all())
                    ->query(fn ($query, array $data) => $query->when(
                        $data['value'],
                        fn ($q, $sectionId) => $q->whereHas('category', fn ($c) => $c->where('parent_id', $sectionId))
                    )),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }

    public static function getRelations(): array
    {
        return [
            AttributesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubCategories::route('/'),
            'create' => Pages\CreateSubCategory::route('/create'),
            'edit' => Pages\EditSubCategory::route('/{record}/edit'),
        ];
    }
}
