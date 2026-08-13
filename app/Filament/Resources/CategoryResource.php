<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-squares-2x2';
    protected static string | \UnitEnum | null $navigationGroup = 'Kataloq';
    protected static ?string $navigationLabel = 'Kateqoriyalar';
    protected static ?string $modelLabel = 'Kateqoriya';
    protected static ?string $pluralModelLabel = 'Kateqoriyalar';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Əsas məlumatlar')->schema([
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
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),

                Forms\Components\Select::make('parent_id')
                    ->label('Ana bölmə')
                    ->relationship(
                        'parent',
                        'name',
                        // Only root sections may be parents (2-level tree), and a
                        // category can never be its own parent.
                        modifyQueryUsing: fn ($query, ?Category $record) => $query
                            ->whereNull('parent_id')
                            ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                    )
                    ->searchable()
                    ->nullable()
                    ->preload()
                    ->helperText('Boş saxlansa Bölmə (kök), seçilsə Qrup olur.'),

                Forms\Components\FileUpload::make('icon')
                    ->label('İkon (SVG)')
                    ->disk('public')
                    ->directory('categories/icons')
                    ->acceptedFileTypes(['image/svg+xml', 'image/png'])
                    ->nullable(),

                Forms\Components\FileUpload::make('image')
                    ->label('Şəkil')
                    ->image()
                    ->disk('public')
                    ->directory('categories/images')
                    ->nullable(),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Sıra')
                    ->numeric()
                    ->default(0),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),

                Forms\Components\Toggle::make('show_on_home')
                    ->label('Ana səhifədə göstər')
                    ->default(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                Tables\Columns\ImageColumn::make('icon')->label('İkon')->width(40)->height(40),
                Tables\Columns\TextColumn::make('name')
                    ->label('Ad')
                    ->searchable()
                    ->sortable()
                    // Legacy inactive categories stay listed, but visibly muted.
                    ->color(fn ($state, Category $record) => $record->is_active ? null : 'gray')
                    ->description(fn (Category $record) => $record->is_active ? null : 'Passiv (köhnə)'),
                Tables\Columns\TextColumn::make('level')
                    ->label('Səviyyə')
                    ->badge()
                    ->state(fn (Category $record) => $record->parent_id ? 'Qrup' : 'Bölmə')
                    ->color(fn (string $state) => $state === 'Bölmə' ? 'primary' : 'gray'),
                Tables\Columns\TextColumn::make('parent.name')->label('Ana bölmə')->placeholder('—'),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
                Tables\Columns\ToggleColumn::make('show_on_home')->label('Ana səhifə'),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('level')
                    ->label('Səviyyə')
                    ->options([
                        'root' => 'Bölmə (kök)',
                        'child' => 'Qrup',
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['value'] === 'root', fn ($q) => $q->whereNull('parent_id'))
                        ->when($data['value'] === 'child', fn ($q) => $q->whereNotNull('parent_id'))),
                Tables\Filters\SelectFilter::make('parent_id')
                    ->label('Ana bölmə')
                    ->relationship('parent', 'name', modifyQueryUsing: fn ($query) => $query->whereNull('parent_id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktiv'),
            ])
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
