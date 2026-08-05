<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-bag';
    protected static string | \UnitEnum | null $navigationGroup = 'Kataloq';
    protected static ?string $navigationLabel = 'Məhsullar';
    protected static ?string $modelLabel = 'Məhsul';
    protected static ?string $pluralModelLabel = 'Məhsullar';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Tabs::make('Tabs')->tabs([
                Tabs\Tab::make('Əsas')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Ad')
                        ->required()
                        ->translatable()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set, $record) {
                            if (! $record) {
                                $set('slug', \Illuminate\Support\Str::slug($state));
                            }
                        }),

                    Forms\Components\RichEditor::make('description')
                        ->label('Təsvir')
                        ->translatable(),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('user_id')
                        ->label('Satıcı')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('category_id')
                        ->label('Kateqoriya')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->nullable(),

                    Forms\Components\TextInput::make('brand')
                        ->label('Brend')
                        ->nullable(),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU')
                        ->unique(ignoreRecord: true)
                        ->nullable(),
                ]),

                Tabs\Tab::make('Qiymət')->schema([
                    Forms\Components\TextInput::make('price')
                        ->label('Qiymət (₼)')
                        ->numeric()
                        ->required()
                        ->prefix('₼'),

                    Forms\Components\TextInput::make('old_price')
                        ->label('Köhnə qiymət (₼)')
                        ->numeric()
                        ->nullable()
                        ->prefix('₼'),

                    Forms\Components\TextInput::make('discount_percent')
                        ->label('Endirim (%)')
                        ->numeric()
                        ->nullable()
                        ->suffix('%'),

                    Forms\Components\Select::make('unit')
                        ->label('Ölçü vahidi')
                        ->options([
                            'piece' => 'Ədəd',
                            'm2' => 'm²',
                            'lm' => 'Xətti metr',
                            'kg' => 'Kq',
                            'litre' => 'Litr',
                            'set' => 'Dəst',
                            'roll' => 'Rulon',
                            'pack' => 'Paket',
                        ])
                        ->default('piece'),

                    Forms\Components\TextInput::make('stock')
                        ->label('Stok')
                        ->numeric()
                        ->default(0),

                    Forms\Components\TextInput::make('min_order')
                        ->label('Min. sifariş')
                        ->numeric()
                        ->default(1),
                ]),

                Tabs\Tab::make('Statuslar')->schema([
                    Forms\Components\Toggle::make('is_visible')->label('Görünür')->default(true),
                    Forms\Components\Toggle::make('is_approved')->label('Təsdiqlənib'),
                    Forms\Components\Toggle::make('is_featured')->label('Seçilmiş məhsul'),
                    Forms\Components\Toggle::make('is_sale')->label('SALE məhsulu'),
                    Forms\Components\Toggle::make('free_delivery')->label('Pulsuz çatdırılma'),
                    Forms\Components\Toggle::make('return_14_days')->label('14 gün qaytarma'),
                ]),

                Tabs\Tab::make('Şəkillər')->schema([
                    Forms\Components\Repeater::make('images')
                        ->relationship()
                        ->schema([
                            Forms\Components\FileUpload::make('path')
                                ->label('Şəkil')
                                ->image()
                                ->disk('public')
                                ->directory('products')
                                ->getUploadedFileUrlUsing(function ($file) {
                                    if (! $file) {
                                        return null;
                                    }
                                    // New uploads stored on the public disk (storage/app/public/)
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($file)) {
                                        return \Illuminate\Support\Facades\Storage::disk('public')->url($file);
                                    }
                                    // Legacy images stored directly in public/ (e.g. assets/...)
                                    if (file_exists(public_path($file))) {
                                        return asset($file);
                                    }
                                    return null;
                                }),
                            Forms\Components\Toggle::make('is_main')->label('Əsas şəkil'),
                            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
                        ])
                        ->maxItems(4)
                        ->reorderable()
                        ->collapsible()
                        ->defaultItems(0)
                        ->label('Şəkillər'),
                ]),

                Tabs\Tab::make('Əlavə')->schema([
                    Forms\Components\KeyValue::make('specifications')
                        ->label('Xüsusiyyətlər')
                        ->keyLabel('Parametr')
                        ->valueLabel('Dəyər'),

                    Forms\Components\KeyValue::make('features')
                        ->label('Özəlliklər')
                        ->keyLabel('Özəllik')
                        ->valueLabel('Dəyər'),

                    Forms\Components\TagsInput::make('accessories')
                        ->label('Aksessuarlar (ID-lər)')
                        ->placeholder('Məhsul ID əlavə edin')
                        ->nullable(),

                    Forms\Components\Select::make('condition')
                        ->label('Vəziyyət')
                        ->options([
                            'new' => 'Yeni',
                            'used' => 'İşlənmiş',
                        ])
                        ->default('new'),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->width(60),
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable()->sortable()->limit(40),
                Tables\Columns\TextColumn::make('category.name')->label('Kateqoriya')->placeholder('—'),
                Tables\Columns\TextColumn::make('user.name')->label('Satıcı'),
                Tables\Columns\TextColumn::make('price')->label('Qiymət')->money('AZN')->sortable(),
                Tables\Columns\TextColumn::make('stock')->label('Stok')->sortable(),
                Tables\Columns\IconColumn::make('is_approved')->label('Təsdiqlənib')->boolean(),
                Tables\Columns\IconColumn::make('is_featured')->label('Seçilmiş')->boolean(),
                Tables\Columns\IconColumn::make('is_sale')->label('SALE')->boolean(),
                Tables\Columns\ToggleColumn::make('is_visible')->label('Görünür'),
                Tables\Columns\TextColumn::make('created_at')->label('Yaradılıb')->dateTime('d.m.Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kateqoriya')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('is_approved')->label('Təsdiqlənib'),
                Tables\Filters\TernaryFilter::make('is_featured')->label('Seçilmiş'),
                Tables\Filters\TernaryFilter::make('is_sale')->label('SALE'),
            ])
            ->actions([
                Actions\Action::make('approve')
                    ->label('Təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Product $record) => !$record->is_approved)
                    ->action(fn (Product $record) => $record->update(['is_approved' => true])),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Actions\DeleteBulkAction::make(),
                Actions\BulkAction::make('approve_selected')
                    ->label('Seçilmişləri təsdiqlə')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['is_approved' => true])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('is_approved', false)->count() ?: null;
    }
}
