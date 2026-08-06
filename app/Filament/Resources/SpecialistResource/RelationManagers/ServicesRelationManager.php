<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecialistResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

final class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    protected static ?string $title = 'Xidmətlər';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function getRelationship(): Relation|Builder
    {
        $profile = $this->getOwnerRecord()->specialistProfile()->firstOrCreate();

        return $profile->services();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->label('Xidmət')->required()->maxLength(255),
            Forms\Components\TextInput::make('description')->label('Açıqlama')->maxLength(255),
            Forms\Components\TextInput::make('price')->label('Qiymət')->numeric()->minValue(0),
            Forms\Components\Select::make('unit')->label('Ölçü vahidi')->options([
                'sqm' => 'm²',
                'hour' => 'saat',
                'piece' => 'ədəd',
                'linear' => 'poqon metr',
            ])->required(),
            Forms\Components\TextInput::make('sort_order')->label('Sıra')->numeric()->minValue(0)->default(0),
            Forms\Components\Toggle::make('is_active')->label('Aktiv')->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Xidmət')->searchable(),
                Tables\Columns\TextColumn::make('price')->label('Qiymət')->numeric(decimalPlaces: 2),
                Tables\Columns\TextColumn::make('unit')->label('Vahid')->badge(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktiv'),
                Tables\Columns\TextColumn::make('sort_order')->label('Sıra')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->headerActions([Actions\CreateAction::make()])
            ->recordActions([Actions\EditAction::make(), Actions\DeleteAction::make()]);
    }
}
