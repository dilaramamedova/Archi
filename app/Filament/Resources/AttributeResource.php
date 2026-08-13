<?php

namespace App\Filament\Resources;

use App\Enums\AttributeComplexity;
use App\Enums\AttributeFieldType;
use App\Filament\Resources\AttributeResource\Pages;
use App\Filament\Resources\AttributeResource\RelationManagers\OptionsRelationManager;
use App\Models\Attribute;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string | \UnitEnum | null $navigationGroup = 'Kataloq';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationLabel = 'Atributlar';
    protected static ?string $modelLabel = 'Atribut';
    protected static ?string $pluralModelLabel = 'Atributlar';

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

                Forms\Components\Select::make('field_type')
                    ->label('Sahə tipi')
                    ->options(collect(AttributeFieldType::cases())->mapWithKeys(fn (AttributeFieldType $type) => [$type->value => $type->label()])->all())
                    ->required()
                    ->live()
                    ->helperText('Dəyərlər daxil edildikdən sonra tipi dəyişmək tövsiyə olunmur.'),

                Forms\Components\Textarea::make('tooltip')
                    ->label('İzah (tooltip)')
                    ->rows(2)
                    ->translatable(),

                Forms\Components\Select::make('complexity')
                    ->label('Mürəkkəblik')
                    ->options(collect(AttributeComplexity::cases())->mapWithKeys(fn (AttributeComplexity $c) => [$c->value => $c->label()])->all())
                    ->default(AttributeComplexity::Basic->value)
                    ->required(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Aktiv')
                    ->default(true),
            ]),

            // Options can only be managed through the relation manager once the
            // attribute exists, so creating a dropdown used to need a second step.
            // This editor is create-only; on edit the relation manager owns them.
            Section::make('Seçim variantları')
                ->description('Bu atributun mümkün dəyərləri. Yaradıldıqdan sonra variantlar "Seçim variantları" cədvəlindən idarə olunur.')
                ->schema([
                    Forms\Components\Repeater::make('options')
                        ->hiddenLabel()
                        ->schema([
                            Forms\Components\TextInput::make('value')
                                ->label('Dəyər')
                                ->maxLength(255)
                                // Only Azerbaijani (the source language) is mandatory —
                                // ru/en can be filled in later.
                                ->translatable(modifyLocalizedFieldUsing: fn (Forms\Components\TextInput $field, string $locale): Forms\Components\TextInput => $field->required($locale === 'az')),

                            Forms\Components\TextInput::make('sort_order')
                                ->label('Sıra')
                                ->numeric()
                                ->default(0),
                        ])
                        ->columns(2)
                        ->defaultItems(1)
                        ->reorderable()
                        ->addActionLabel('Variant əlavə et'),
                ])
                ->visible(fn (callable $get, $record): bool => $record === null
                    && (AttributeFieldType::tryFrom((string) $get('field_type'))?->hasOptions() ?? false)),
        ]);
    }

    /**
     * Persists the create-form repeater rows as AttributeOption records.
     * Slugs are derived from the Azerbaijani value and de-duplicated, because
     * attribute_options carries a UNIQUE(attribute_id, slug) index.
     *
     * @param  array<int|string, array<string, mixed>>  $rows
     */
    public static function createOptions(Attribute $attribute, array $rows): void
    {
        if (! ($attribute->field_type?->hasOptions() ?? false)) {
            return;
        }

        $usedSlugs = $attribute->options()->pluck('slug')->all();

        foreach (array_values($rows) as $index => $row) {
            $raw = $row['value'] ?? null;
            $value = array_filter(
                is_array($raw) ? $raw : ['az' => $raw],
                fn ($translation): bool => filled($translation),
            );

            if ($value === []) {
                continue;
            }

            $base = Str::slug((string) ($value['az'] ?? reset($value))) ?: 'variant';
            $slug = $base;
            $suffix = 2;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = $base.'-'.$suffix++;
            }

            $usedSlugs[] = $slug;

            $attribute->options()->create([
                'value' => $value,
                'slug' => $slug,
                'sort_order' => (int) ($row['sort_order'] ?? $index),
            ]);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('field_type')
                    ->label('Tip')
                    ->badge()
                    // Nullable hints: a NULL/unknown enum value in the column must not
                    // 500 the whole list page.
                    ->formatStateUsing(fn (?AttributeFieldType $state) => $state?->label())
                    ->placeholder('—')
                    ->color(fn (?AttributeFieldType $state) => $state?->hasOptions() ? 'info' : 'gray'),
                Tables\Columns\TextColumn::make('complexity')
                    ->label('Mürəkkəblik')
                    ->badge()
                    ->formatStateUsing(fn (?AttributeComplexity $state) => $state?->label())
                    ->placeholder('—')
                    ->color(fn (?AttributeComplexity $state) => $state === AttributeComplexity::Professional ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('sub_categories_count')
                    ->counts('subCategories')
                    ->label('Siniflər')
                    ->sortable(),
                Tables\Columns\TextColumn::make('options_count')
                    ->counts('options')
                    ->label('Variantlar'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
            ])
            ->defaultSort('id')
            ->filters([
                Tables\Filters\SelectFilter::make('field_type')
                    ->label('Sahə tipi')
                    ->options(collect(AttributeFieldType::cases())->mapWithKeys(fn (AttributeFieldType $type) => [$type->value => $type->label()])->all()),
                Tables\Filters\SelectFilter::make('complexity')
                    ->label('Mürəkkəblik')
                    ->options(collect(AttributeComplexity::cases())->mapWithKeys(fn (AttributeComplexity $c) => [$c->value => $c->label()])->all()),
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
            OptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
            'create' => Pages\CreateAttribute::route('/create'),
            'edit' => Pages\EditAttribute::route('/{record}/edit'),
        ];
    }
}
