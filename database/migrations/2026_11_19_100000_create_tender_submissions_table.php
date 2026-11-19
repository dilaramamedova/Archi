<?php

use App\Models\Translation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * «Tender yarat» — a signed-in user sends the essentials of a tender they want
 * published. The admin reviews it in the panel (and gets it by email) and can turn
 * it into a draft Tender with one action; nothing reaches the board by itself.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tender_category_id')->nullable()->constrained()->nullOnDelete();
            // The tender the admin made from it, once converted.
            $table->foreignId('tender_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title', 200);
            $table->text('description');
            $table->string('city', 120);
            $table->decimal('area_sqm', 12, 2)->nullable();
            $table->decimal('budget', 14, 2)->nullable();
            $table->string('currency', 3)->default('AZN');
            $table->date('deadline_at')->nullable();

            $table->string('organizer_name', 160)->nullable();
            $table->string('contact_name', 120);
            $table->string('phone', 25);
            $table->string('email');

            $table->string('status', 20)->default('new')->index();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });

        // Only the new keys: copy Archi edited in the panel stays as it is.
        $rows = require database_path('seeders/data/tenders_translations.php');
        foreach ($rows['tenders'] as $key => $value) {
            if (str_starts_with($key, 'create.') || $key === 'heading.create') {
                Translation::firstOrCreate(['group' => 'tenders', 'key' => $key], ['value' => $value]);
            }
        }
        Translation::clearCache();
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_submissions');
        Translation::query()->where('group', 'tenders')
            ->where(fn ($q) => $q->where('key', 'like', 'create.%')->orWhere('key', 'heading.create'))
            ->delete();
        Translation::clearCache();
    }
};
