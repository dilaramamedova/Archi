<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\AdminResource\Pages;

class AdminResource extends BaseUserResource
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Adminlər';

    protected static ?string $modelLabel = 'Admin';

    protected static ?string $pluralModelLabel = 'Adminlər';

    protected static ?string $slug = 'admins';

    protected static ?int $navigationSort = 4;

    public static function getManagedRole(): UserRole
    {
        return UserRole::Admin;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdmins::route('/'),
            'create' => Pages\CreateAdmin::route('/create'),
            'view' => Pages\ViewAdmin::route('/{record}'),
            'edit' => Pages\EditAdmin::route('/{record}/edit'),
        ];
    }
}
