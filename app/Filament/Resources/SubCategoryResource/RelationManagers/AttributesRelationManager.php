<?php

declare(strict_types=1);

namespace App\Filament\Resources\SubCategoryResource\RelationManagers;

use App\Enums\AttributeFieldType;
use App\Enums\FilterPriority;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class AttributesRelationManager extends RelationManager
{
    protected static string $relationship = 'attributes';

    protected static ?string $title = 'Atributlar (forma tərifi)';

    protected static ?string $recordTitleAttribute = 'name';

    /**
     * Pivot settings of the class ↔ attribute link. Field names match the
     * withPivot() columns, so Filament saves them straight to the pivot table.
     */
    private static function pivotFields(): array
    {
        return [
            Forms\Components\Toggle::make('is_required')
                ->label('Məcburi')
                ->default(false),

            Forms\Components\Toggle::make('is_filterable')
                ->label('Filtrlənə bilən')
                ->default(false),

            Forms\Components\Select::make('filter_priority')
                ->label('Filter prioriteti')
                ->options(collect(FilterPriority::cases())->mapWithKeys(fn (FilterPriority $p) => [$p->value => $p->label()])->all())
                ->nullable(),

            Forms\Components\TextInput::make('unit')
                ->label('Ölçü vahidi')
                ->maxLength(20)
                ->helperText('Göstərilən vahid, məs. "мм", "кг/м²".'),

            Forms\Components\TextInput::make('sort_order')
                ->label('Sıra')
                ->numeric()
                ->default(0),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components(self::pivotFields());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Atribut')->searchable(),
                Tables\Columns\TextColumn::make('field_type')
                    ->label('Tip')
                    ->badge()
                    // Nullable: an unknown/NULL field_type must not 500 the table.
                    ->formatStateUsing(fn (?AttributeFieldType $state) => $state?->label())
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('pivot.is_required')->label('Məcburi')->boolean(),
                Tables\Columns\IconColumn::make('pivot.is_filterable')->label('Filtr')->boolean(),
                Tables\Columns\TextColumn::make('pivot.filter_priority')
                    ->label('Prioritet')
                    ->badge()
                    ->formatStateUsing(fn (?FilterPriority $state) => $state?->label())
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('pivot.unit')->label('Vahid')->placeholder('—'),
                Tables\Columns\TextColumn::make('pivot.sort_order')->label('Sıra'),
            ])
            ->defaultSort('attribute_sub_category.sort_order')
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Atribut əlavə et')
                    ->preloadRecordSelect()
                    ->schema(fn (Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        ...self::pivotFields(),
                    ]),
            ])
            ->recordActions([
                Actions\EditAction::make(),
                Actions\DetachAction::make(),
            ])
            ->bulkActions([Actions\DetachBulkAction::make()]);
    }
}
