<?php

declare(strict_types=1);

use App\Models\MenuItem;
use Illuminate\Database\Migrations\Migration;

/**
 * The header "B2B" item pointed at the seller registration page as a placeholder.
 * It now opens the B2B directory (/b2b). Only the route changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        MenuItem::query()
            ->where('location', 'header_main')
            ->where('route_name', 'business.register')
            ->update(['route_name' => 'b2b.index']);
    }

    public function down(): void
    {
        MenuItem::query()
            ->where('location', 'header_main')
            ->where('route_name', 'b2b.index')
            ->update(['route_name' => 'business.register']);
    }
};
