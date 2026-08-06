<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\UserRole;
use App\Filament\Resources\SellerResource\Pages;

class SellerResource extends BaseUserResource
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Satıcılar';

    protected static ?string $modelLabel = 'Satıcı';

    protected static ?string $pluralModelLabel = 'Satıcılar';

    protected static ?string $slug = 'sellers';

    protected static ?int $navigationSort = 2;

    public static function getManagedRole(): UserRole
    {
        return UserRole::Seller;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSellers::route('/'),
            'create' => Pages\CreateSeller::route('/create'),
            'view' => Pages\ViewSeller::route('/{record}'),
            'edit' => Pages\EditSeller::route('/{record}/edit'),
        ];
    }
}
