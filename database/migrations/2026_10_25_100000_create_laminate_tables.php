<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The laminate card (TZ «Ламинат», v1.0, §2–§5).
 *
 * Laminate gets typed columns instead of rows in product_attribute_values. The
 * generic EAV table cannot hold what the TZ requires: its numeric column keeps three
 * decimals where sizes and pack area need four, it has no yes / no / unknown answer,
 * no way to tie a dependent field to its parent, and no per-scope warranty record.
 * Above all, the TZ asks for the rules to live in the database — positive numbers,
 * allowed codes, unique links — and an EAV value column cannot carry a constraint
 * that differs per attribute.
 *
 * One spec row per product. The shared-variant / seller-offer / revision model the
 * TZ also describes is a change to how every product on the site is owned and is
 * deliberately not part of this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laminate_specs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();

            // Identification (TZ §1, §3 publication rule).
            $table->string('model_name', 200)->nullable();
            $table->string('collection_name', 150)->nullable();
            // The manufacturer's article as printed — never globally unique, two
            // brands may use the same code (§6 «Дубли»).
            $table->string('manufacturer_sku', 100)->nullable();
            $table->string('sku_normalized', 100)->nullable()->index();

            // §3 — the twelve parameters (brand lives on products.brand_id).
            $table->char('country_code', 2)->nullable()->index();
            // Dictionary options are never deleted while a card points at them.
            $table->foreignId('usage_class_option_id')->nullable()->constrained('attribute_options')->restrictOnDelete();
            $table->foreignId('abrasion_option_id')->nullable()->constrained('attribute_options')->restrictOnDelete();
            $table->decimal('thickness_mm', 12, 4)->nullable();
            $table->decimal('length_mm', 12, 4)->nullable();
            $table->decimal('width_mm', 12, 4)->nullable();

            $table->string('water_protection', 8)->default('unknown');
            $table->string('water_claim', 20)->default('unknown');
            $table->unsignedInteger('water_hours')->nullable();

            $table->string('floor_heating', 8)->default('unknown');
            $table->boolean('heating_types_known')->default(false);
            $table->decimal('max_surface_temp_c', 6, 2)->nullable();
            $table->decimal('thermal_resistance_m2k_w', 12, 6)->nullable();

            $table->string('integrated_underlay', 8)->default('unknown');
            $table->decimal('underlay_thickness_mm', 12, 4)->nullable();
            $table->string('thickness_includes_underlay', 8)->default('unknown');

            $table->foreignId('connection_option_id')->nullable()->constrained('attribute_options')->restrictOnDelete();
            $table->decimal('pack_area_m2', 12, 4)->nullable();
            $table->string('warranty', 8)->default('unknown');

            // §2: changed entities carry their author and a version for If-Match.
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('laminate_spec_heating_types', function (Blueprint $table): void {
            $table->foreignId('laminate_spec_id')->constrained()->cascadeOnDelete();
            $table->string('heating_type_code', 20);
            // Composite key: ticking the same system twice cannot create two rows.
            $table->primary(['laminate_spec_id', 'heating_type_code']);
            $table->index(['heating_type_code', 'laminate_spec_id'], 'lam_heat_code_spec_idx');
        });

        Schema::create('laminate_spec_warranties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('laminate_spec_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 20);
            $table->string('term_kind', 10)->default('unknown');
            $table->unsignedSmallInteger('years')->nullable();
            $table->text('conditions_text')->nullable();
            $table->timestamps();

            $table->unique(['laminate_spec_id', 'scope']);
        });

        Schema::create('laminate_spec_documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('laminate_spec_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 30);
            // Exactly one of the two — a stored object or an absolute HTTPS link.
            $table->string('storage_key')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->string('original_name', 255)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('warranty_scope', 20)->nullable();
            // Removing a document from the card detaches it rather than deleting the
            // file: the audit trail may still point at it (§5 «Файлы и ссылки»).
            $table->timestamp('detached_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['laminate_spec_id', 'detached_at']);
        });

        Schema::create('dictionary_requests', function (Blueprint $table): void {
            $table->id();
            // brand | laminate_option
            $table->string('entity_type', 30);
            // brand | usage_class | abrasion | connection
            $table->string('field_code', 30);
            $table->string('proposed_label', 150);
            $table->string('normalized_label', 150);
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('proposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 12)->default('pending');
            // The approved value — a brand id or an attribute_options id. Until the
            // request is resolved the card's own foreign key stays NULL (§2).
            $table->unsignedBigInteger('resolved_id')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['status', 'field_code']);
            $table->index(['product_id', 'field_code', 'status']);
        });

        Schema::create('audit_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('entity_id');
            $table->string('action', 40);
            $table->json('changes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
        });

        $this->addChecks();
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
        Schema::dropIfExists('dictionary_requests');
        Schema::dropIfExists('laminate_spec_documents');
        Schema::dropIfExists('laminate_spec_warranties');
        Schema::dropIfExists('laminate_spec_heating_types');
        Schema::dropIfExists('laminate_specs');
    }

    /**
     * The TZ (§6) asks for positivity, allowed codes and "exactly one source" to be
     * enforced by the database itself, so a bug in a form or a direct API call cannot
     * store a zero-millimetre panel or a warranty of "lifetime = 999 years". The
     * application validates first and these are the floor under it.
     *
     * MySQL enforces CHECK constraints from 8.0.16; on any other driver they are
     * skipped rather than failing the migration.
     */
    private function addChecks(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $tri = "('yes','no','unknown')";

        $checks = [
            'laminate_specs' => [
                'lam_thickness_pos' => 'thickness_mm IS NULL OR thickness_mm > 0',
                'lam_length_pos' => 'length_mm IS NULL OR length_mm > 0',
                'lam_width_pos' => 'width_mm IS NULL OR width_mm > 0',
                'lam_pack_pos' => 'pack_area_m2 IS NULL OR pack_area_m2 > 0',
                'lam_underlay_pos' => 'underlay_thickness_mm IS NULL OR underlay_thickness_mm > 0',
                'lam_temp_pos' => 'max_surface_temp_c IS NULL OR max_surface_temp_c > 0',
                'lam_resist_pos' => 'thermal_resistance_m2k_w IS NULL OR thermal_resistance_m2k_w > 0',
                'lam_hours_pos' => 'water_hours IS NULL OR water_hours > 0',
                'lam_water_tri' => "water_protection IN {$tri}",
                'lam_heating_tri' => "floor_heating IN {$tri}",
                'lam_underlay_tri' => "integrated_underlay IN {$tri}",
                'lam_includes_tri' => "thickness_includes_underlay IN {$tri}",
                'lam_warranty_tri' => "warranty IN {$tri}",
                'lam_water_claim' => "water_claim IN ('moisture_resistant','water_resistant','unknown')",
            ],
            'laminate_spec_heating_types' => [
                'lam_heat_code' => "heating_type_code IN ('water','electric_cable','electric_film')",
            ],
            'laminate_spec_warranties' => [
                'lam_war_scope' => "scope IN ('residential','commercial')",
                'lam_war_term' => "term_kind IN ('years','lifetime','unknown')",
                // A year count exists exactly when the term is expressed in years.
                'lam_war_years' => "(term_kind = 'years' AND years IS NOT NULL AND years > 0) OR (term_kind <> 'years' AND years IS NULL)",
            ],
            'laminate_spec_documents' => [
                'lam_doc_kind' => "kind IN ('technical_passport','installation','warranty_conditions','water_conditions')",
                'lam_doc_source' => '(storage_key IS NULL) <> (external_url IS NULL)',
            ],
        ];

        foreach ($checks as $table => $constraints) {
            foreach ($constraints as $name => $expression) {
                DB::statement("ALTER TABLE `{$table}` ADD CONSTRAINT `{$name}` CHECK ({$expression})");
            }
        }
    }
};
