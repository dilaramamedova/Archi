<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Company info (from business-profile-company)
            $table->string('legal_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('tax_id_verified')->default(false);
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->text('about')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('cover_path')->nullable();

            // Contact info (from business-profile-contact)
            $table->string('contact_person')->nullable();
            $table->string('contact_role')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->string('telegram')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('website')->nullable();
            $table->string('work_hours')->nullable();

            // Social (from business-profile-contact)
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('facebook')->nullable();

            // Languages (from business-profile-contact chips)
            $table->json('languages')->nullable();

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
