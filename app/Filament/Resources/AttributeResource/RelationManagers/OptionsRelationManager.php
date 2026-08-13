<?php

declare(strict_types=1);

namespace App\Filament\Resources\AttributeResource\RelationManagers;

use App\Models\Attribute;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

final class OptionsRelationManager extends RelationManager
{
    protected static string $relationship = 'options';

    protected static ?string $title = 'Seçim variantları';

    /**
     * Options only make sense for dropdown/multiselect attributes.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Attribute
            && ($ownerRecord->field_type?->hasOptions() ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('value')
                ->label('Dəyər')
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
                ->maxLength(255),

            Forms\Components\TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('value')->label('Dəyər')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([Actions\CreateAction::make()->label('Variant əlavə et')])
            ->recordActions([Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->bulkActions([Actions\DeleteBulkAction::make()]);
    }
}
