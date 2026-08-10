<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string|\UnitEnum|null $navigationGroup = 'Satış';

    protected static ?string $navigationLabel = 'Sifarişlər';

    protected static ?string $modelLabel = 'Sifariş';

    protected static ?string $pluralModelLabel = 'Sifarişlər';

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Sifariş məlumatları')->schema([
                Infolists\Components\TextEntry::make('order_number')->label('Sifariş nömrəsi')->copyable(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(self::statusOptions())
                    ->required(),
                Infolists\Components\TextEntry::make('created_at')->label('Sifariş tarixi')->dateTime('d.m.Y H:i'),
                Infolists\Components\TextEntry::make('updated_at')->label('Son yenilənmə')->dateTime('d.m.Y H:i'),
                Infolists\Components\TextEntry::make('items_count')
                    ->label('Məhsul çeşidi')
                    ->getStateUsing(fn (Order $record): int => $record->items->count()),
                Infolists\Components\TextEntry::make('items_quantity')
                    ->label('Ümumi məhsul sayı')
                    ->getStateUsing(fn (Order $record): int => (int) $record->items->sum('quantity')),
            ])->columns(3),

            Section::make('Alıcı hesabı')->schema([
                Infolists\Components\TextEntry::make('user.id')->label('İstifadəçi ID')->placeholder('—'),
                Infolists\Components\TextEntry::make('user.name')->label('Ad, soyad')->placeholder('—'),
                Infolists\Components\TextEntry::make('user.email')->label('E-poçt')->copyable()->placeholder('—'),
                Infolists\Components\TextEntry::make('user.phone')->label('Hesab telefonu')->copyable()->placeholder('—'),
            ])->columns(2),

            Section::make('Çatdırılma məlumatları')->schema([
                Forms\Components\TextInput::make('delivery_name')->label('Təhvil alacaq şəxs'),
                Forms\Components\TextInput::make('delivery_phone')->label('Əlaqə telefonu'),
                Forms\Components\TextInput::make('delivery_city')->label('Şəhər'),
                Forms\Components\TextInput::make('delivery_address')->label('Tam ünvan')->columnSpanFull(),
                Forms\Components\Textarea::make('notes')->label('Qeydlər')->placeholder('Qeyd yoxdur')->columnSpanFull(),
            ])->columns(2),

            Section::make('Məhsullar və satıcılar')->schema([
                Infolists\Components\RepeatableEntry::make('items')
                    ->label('Sifariş sətirləri')
                    ->schema([
                        Infolists\Components\TextEntry::make('product_name')
                            ->label('Məhsul')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['name'] ?? $record->product?->name ?? 'Silinmiş məhsul')),
                        Infolists\Components\TextEntry::make('product_id')->label('Məhsul ID')->placeholder('Silinib'),
                        Infolists\Components\TextEntry::make('product_sku')
                            ->label('SKU')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['sku'] ?? $record->product?->sku ?? '—')),
                        Infolists\Components\TextEntry::make('brand')
                            ->label('Brend')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['brand'] ?? $record->product?->brand?->name ?? '—')),
                        Infolists\Components\TextEntry::make('category')
                            ->label('Kateqoriya')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['cat'] ?? $record->product?->category?->name ?? '—')),
                        Infolists\Components\TextEntry::make('seller_name')
                            ->label('Satıcı')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['seller']['name'] ?? $record->product?->user?->name ?? '—')),
                        Infolists\Components\TextEntry::make('seller_id')
                            ->label('Satıcı ID')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['seller']['id'] ?? $record->product?->user_id ?? '—')),
                        Infolists\Components\TextEntry::make('seller_email')
                            ->label('Satıcı e-poçtu')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['seller']['email'] ?? $record->product?->user?->email ?? '—')),
                        Infolists\Components\TextEntry::make('seller_phone')
                            ->label('Satıcı telefonu')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['seller']['phone'] ?? $record->product?->user?->phone ?? '—')),
                        Infolists\Components\TextEntry::make('quantity')->label('Miqdar')->numeric(),
                        Infolists\Components\TextEntry::make('unit')
                            ->label('Ölçü vahidi')
                            ->getStateUsing(fn (OrderItem $record): string => (string) ($record->product_snapshot['unit'] ?? $record->product?->unit ?? 'ədəd')),
                        Infolists\Components\TextEntry::make('unit_price')->label('Vahid qiyməti')->money('AZN'),
                        Infolists\Components\TextEntry::make('total')->label('Sətir cəmi')->money('AZN'),
                        Infolists\Components\TextEntry::make('created_at')->label('Alış vaxtı')->dateTime('d.m.Y H:i'),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]),

            Section::make('Məbləğlər')->schema([
                Infolists\Components\TextEntry::make('subtotal')->label('Məhsulların cəmi')->money('AZN'),
                Infolists\Components\TextEntry::make('discount')->label('Endirim')->money('AZN'),
                Infolists\Components\TextEntry::make('delivery_fee')->label('Çatdırılma')->money('AZN'),
                Infolists\Components\TextEntry::make('promo_code')->label('Promo kod')->placeholder('Tətbiq edilməyib'),
                Infolists\Components\TextEntry::make('total')->label('Yekun məbləğ')->money('AZN')->weight('bold'),
            ])->columns(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->label('Nömrə')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Müştəri'),
                Tables\Columns\TextColumn::make('items_sum_quantity')->sum('items', 'quantity')->label('Məhsul sayı'),
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
                Tables\Filters\SelectFilter::make('status')->label('Status')->options(self::statusOptions()),
            ])
            ->recordActions([Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'user',
            'items.product.user',
            'items.product.brand',
            'items.product.category',
        ]);
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
        $pendingCount = static::getModel()::where('status', 'pending')->count();

        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    /** @return array<string, string> */
    private static function statusOptions(): array
    {
        return [
            'pending' => 'Gözləyir',
            'confirmed' => 'Təsdiqlənib',
            'processing' => 'Hazırlanır',
            'shipped' => 'Göndərilib',
            'delivered' => 'Çatdırılıb',
            'cancelled' => 'Ləğv edilib',
        ];
    }
}
