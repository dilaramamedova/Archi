<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('specialist_profiles', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('is_on_vacation');
            $table->boolean('show_in_header')->default(false)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('specialist_profiles', function (Blueprint $table) {
            $table->dropColumn(['is_featured', 'show_in_header']);
        });
    }
};
