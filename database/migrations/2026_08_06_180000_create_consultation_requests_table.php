<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name', 120);
            $table->string('phone', 25);
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->text('admin_note')->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};
