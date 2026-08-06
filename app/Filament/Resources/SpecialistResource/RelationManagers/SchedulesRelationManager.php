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

final class SchedulesRelationManager extends RelationManager
{
    protected static string $relationship = 'schedules';

    protected static ?string $title = 'İş qrafiki';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function getRelationship(): Relation|Builder
    {
        $profile = $this->getOwnerRecord()->specialistProfile()->firstOrCreate();

        return $profile->schedules();
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('day_of_week')->label('Həftənin günü')->options([
                1 => 'Bazar ertəsi', 2 => 'Çərşənbə axşamı', 3 => 'Çərşənbə',
                4 => 'Cümə axşamı', 5 => 'Cümə', 6 => 'Şənbə', 7 => 'Bazar',
            ])->required(),
            Forms\Components\TimePicker::make('start_time')->label('Başlanğıc')->seconds(false),
            Forms\Components\TimePicker::make('end_time')->label('Son')->seconds(false)->after('start_time'),
            Forms\Components\Toggle::make('is_day_off')->label('İstirahət günü'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_of_week')->label('Gün')->formatStateUsing(fn (int $state): string => [
                    1 => 'Bazar ertəsi', 2 => 'Çərşənbə axşamı', 3 => 'Çərşənbə',
                    4 => 'Cümə axşamı', 5 => 'Cümə', 6 => 'Şənbə', 7 => 'Bazar',
                ][$state]),
                Tables\Columns\TextColumn::make('start_time')->label('Başlanğıc'),
                Tables\Columns\TextColumn::make('end_time')->label('Son'),
                Tables\Columns\IconColumn::make('is_day_off')->label('İstirahət')->boolean(),
            ])
            ->defaultSort('day_of_week')
            ->headerActions([Actions\CreateAction::make()])
            ->recordActions([Actions\EditAction::make(), Actions\DeleteAction::make()]);
    }
}
