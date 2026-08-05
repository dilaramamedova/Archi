<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';
    protected static string | \UnitEnum | null $navigationGroup = 'Satış';
    protected static ?string $navigationLabel = 'Sifarişlər';
    protected static ?string $modelLabel = 'Sifariş';
    protected static ?string $pluralModelLabel = 'Sifarişlər';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Sifariş')->schema([
                Forms\Components\TextInput::make('order_number')->label('Sifariş nömrəsi')->disabled(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'confirmed' => 'Təsdiqlənib',
                        'processing' => 'Hazırlanır',
                        'shipped' => 'Göndərilib',
                        'delivered' => 'Çatdırılıb',
                        'cancelled' => 'Ləğv edilib',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('total')->label('Cəm')->disabled()->prefix('₼'),
            ]),
            Section::make('Çatdırılma')->schema([
                Forms\Components\TextInput::make('delivery_name')->label('Ad'),
                Forms\Components\TextInput::make('delivery_phone')->label('Telefon'),
                Forms\Components\TextInput::make('delivery_address')->label('Ünvan'),
                Forms\Components\TextInput::make('delivery_city')->label('Şəhər'),
                Forms\Components\Textarea::make('notes')->label('Qeydlər'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nömrə')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Müştəri'),
                Tables\Columns\TextColumn::make('total')->label('Cəm')->money('AZN'),
                Tables\Columns\TextColumn::make('status')->label('Status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'confirmed', 'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Tarix')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'confirmed' => 'Təsdiqlənib',
                        'processing' => 'Hazırlanır',
                        'shipped' => 'Göndərilib',
                        'delivered' => 'Çatdırılıb',
                        'cancelled' => 'Ləğv edilib',
                    ]),
            ])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
}
