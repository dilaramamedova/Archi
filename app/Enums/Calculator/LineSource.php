<?php

namespace App\Enums\Calculator;

/** Where the unit price of an estimate line came from (master spec §5). */
enum LineSource: string
{
    case AdminRate = 'ADMIN_RATE';
    case CatalogPrice = 'CATALOG_PRICE';
    case FallbackCategoryPrice = 'FALLBACK_CATEGORY_PRICE';
    case ManualQuote = 'MANUAL_QUOTE';
    case PriceMissing = 'PRICE_MISSING';
}
