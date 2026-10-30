<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tender board (Figma «11 · TENDERLƏR»).
 *
 * The section is behind a paid monthly package. There is no payment gateway:
 * a user picks a package and sends an access request, the admin gets it by mail,
 * takes payment offline and activates a subscription in the panel. Replies to a
 * tender itself go straight to the organizer's e-mail — the site does not
 * collect them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tenders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('tender_category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('summary')->nullable();
            $table->json('description')->nullable();
            // One item per line — "İşin həcmi", "İştirakçıya tələblər", "Göndəriləcək sənədlər".
            $table->json('scope')->nullable();
            $table->json('requirements')->nullable();
            $table->json('submission_documents')->nullable();
            $table->string('organizer_name');
            $table->string('organizer_logo')->nullable();
            $table->string('city', 80)->nullable();
            $table->decimal('area_sqm', 10, 2)->nullable();
            $table->decimal('budget_min', 14, 2)->nullable();
            $table->decimal('budget_max', 14, 2)->nullable();
            $table->string('currency', 3)->default('AZN');
            $table->json('duration')->nullable();
            $table->json('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 40)->nullable();
            // Private disk; served only to subscribers by TenderFileController.
            $table->json('files')->nullable();
            $table->json('file_names')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('deadline_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index('deadline_at');
            $table->index('city');
        });

        Schema::create('tender_packages', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedSmallInteger('duration_months');
            $table->decimal('price', 10, 2)->nullable();
            $table->string('currency', 3)->default('AZN');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tender_access_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name', 120);
            $table->string('company_name', 160)->nullable();
            $table->string('phone', 25);
            $table->string('email');
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('tender_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tender_access_request_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ends_at']);
        });

        Schema::create('tender_saves', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tender_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->primary(['user_id', 'tender_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_saves');
        Schema::dropIfExists('tender_subscriptions');
        Schema::dropIfExists('tender_access_requests');
        Schema::dropIfExists('tender_packages');
        Schema::dropIfExists('tenders');
        Schema::dropIfExists('tender_categories');
    }
};
