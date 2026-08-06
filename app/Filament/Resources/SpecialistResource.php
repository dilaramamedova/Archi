<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\SpecialistResource\Pages;
use App\Filament\Resources\SpecialistResource\RelationManagers\PortfolioItemsRelationManager;
use App\Filament\Resources\SpecialistResource\RelationManagers\SchedulesRelationManager;
use App\Filament\Resources\SpecialistResource\RelationManagers\ServicesRelationManager;

class SpecialistResource extends BaseUserResource
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Usta / Mütəxəssislər';

    protected static ?string $modelLabel = 'Usta / Mütəxəssis';

    protected static ?string $pluralModelLabel = 'Usta / Mütəxəssislər';

    protected static ?string $slug = 'specialists';

    protected static ?int $navigationSort = 3;

    public static function getManagedRole(): UserRole
    {
        return UserRole::Master;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSpecialists::route('/'),
            'create' => Pages\CreateSpecialist::route('/create'),
            'view' => Pages\ViewSpecialist::route('/{record}'),
            'edit' => Pages\EditSpecialist::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            ServicesRelationManager::class,
            SchedulesRelationManager::class,
            PortfolioItemsRelationManager::class,
        ];
    }
}
