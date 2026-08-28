<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requests raised from the finished estimate: a turnkey order, a saved estimate the
 * visitor wants a manager to look at, or a PDF/WhatsApp share.
 *
 * Each one points at a snapshot rather than a live session, so the sum a manager
 * opens is the sum the client saw, even if a rate changed the same afternoon
 * (master spec §22).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_requests', function (Blueprint $table) {
            $table->id();
            $table->string('type', 24)->index();
            $table->string('status', 24)->default('NEW')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calculator_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calculator_snapshot_id')->nullable()->constrained()->nullOnDelete();

            // Contact details are optional: the quick calculator collects none at all
            // (TZ §46), and a logged-in visitor's are already on their account.
            $table->string('name')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('email')->nullable();
            $table->text('message')->nullable();

            // Denormalised so the admin list can be read and filtered without opening
            // a snapshot per row.
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->decimal('area', 10, 2)->nullable();
            $table->string('city_key', 40)->nullable();
            $table->unsignedSmallInteger('room_count')->nullable();

            $table->text('admin_note')->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calculator_requests');
    }
};
