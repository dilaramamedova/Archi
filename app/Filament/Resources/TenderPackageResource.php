<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TenderPackageResource\Pages;
use App\Models\TenderPackage;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

final class TenderPackageResource extends Resource
{
    protected static ?string $model = TenderPackage::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|\UnitEnum|null $navigationGroup = 'Tenderlər';

    protected static ?string $navigationLabel = 'Ödənişli paketlər';

    protected static ?string $modelLabel = 'Paket';

    protected static ?string $pluralModelLabel = 'Ödənişli paketlər';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('Ad')
                ->placeholder('Aylıq paket')
                ->maxLength(120)
                ->translatable(modifyLocalizedFieldUsing: fn (Forms\Components\TextInput $field, string $locale) => $field->required($locale === 'az')),
            Forms\Components\Textarea::make('description')->label('Qısa təsvir')->rows(2)->maxLength(300)->translatable(),
            Forms\Components\TextInput::make('duration_months')->label('Müddət (ay)')->numeric()->integer()->minValue(1)->maxValue(36)->required()->default(1),
            Forms\Components\TextInput::make('price')->label('Qiymət')->helperText('Boş qalsa, «Qiymət razılaşma ilə» göstərilir.')->numeric()->minValue(0),
            Forms\Components\Select::make('currency')->label('Valyuta')->options(['AZN' => 'AZN', 'USD' => 'USD', 'EUR' => 'EUR'])->default('AZN')->required(),
            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->default(0),
            Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Ad')->searchable(),
                Tables\Columns\TextColumn::make('duration_months')->label('Müddət')->suffix(' ay')->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Qiymət')
                    ->state(fn (TenderPackage $record): string => $record->priceLabel() ?? 'Razılaşma ilə'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->label('Aktiv')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderPackages::route('/'),
            'create' => Pages\CreateTenderPackage::route('/create'),
            'edit' => Pages\EditTenderPackage::route('/{record}/edit'),
        ];
    }
}
