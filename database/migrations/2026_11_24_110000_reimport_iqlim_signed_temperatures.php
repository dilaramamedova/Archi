<?php

use App\Models\Category;
use App\Services\ProductCard\CardImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Card numbers may now go below zero when the card sets `rules.min` at or below 0
 * (CardInput::inBounds): the İqlim outdoor-temperature range becomes four signed
 * decimals instead of two text fields.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Category::query()->where('slug', 'istilik-ventilyasiya-ve-kondisionerlesdirme')->whereNull('parent_id')->doesntExist()) {
            return;
        }

        app(CardImporter::class)->importFile('iqlim');
    }

    public function down(): void
    {
        // Nothing to undo: the old text fields were never filled (no products).
    }
};
