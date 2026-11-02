<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B2B directory (Figma «10 · B2B BEYNƏLXALQ / ArchiAds»).
 *
 * The Figma shows a self-service onboarding; per the brief that is dropped —
 * companies are added by the admin and only appear on the public /b2b page.
 * The onboarding frames (company info / contact / first catalog) define the
 * fields the admin fills.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('b2b_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('b2b_companies', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('b2b_category_id')->nullable()->constrained()->nullOnDelete();

            // Step 1 — company
            $table->string('legal_name');
            $table->string('brand_name')->nullable();
            $table->string('registration_no')->nullable();
            $table->string('country', 80)->nullable();
            $table->string('legal_address')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('logo')->nullable();
            $table->json('description')->nullable();

            // Step 3 — catalog / cooperation
            $table->string('cooperation_type', 40)->nullable();
            $table->string('brands')->nullable();
            $table->string('moq')->nullable();
            $table->json('catalog_description')->nullable();
            $table->json('catalog_images')->nullable();

            // Step 2 — contact
            $table->string('contact_person', 120)->nullable();
            $table->string('contact_position', 120)->nullable();
            $table->string('contact_phone', 40)->nullable();
            $table->string('whatsapp', 40)->nullable();
            $table->string('telegram', 80)->nullable();
            $table->string('email')->nullable();
            $table->string('working_hours', 120)->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('facebook')->nullable();
            $table->json('languages')->nullable();

            // Placement
            $table->string('market', 20)->default('local');     // local | international
            $table->string('plan', 20)->default('free');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'published_at']);
            $table->index('country');
            $table->index('cooperation_type');
            $table->index('market');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('b2b_companies');
        Schema::dropIfExists('b2b_categories');
    }
};
