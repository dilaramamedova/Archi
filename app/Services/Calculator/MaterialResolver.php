<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\LineSource;
use App\Enums\Calculator\LineType;
use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\WorkCategory;
use App\Models\Product;

/**
 * Material requirement and pricing (master spec §12–§13).
 *
 * Requirements are derived from the works that were actually selected, not from the
 * rooms: if the visitor removes "purcaq boyası", its paint disappears with it. Each
 * requirement then resolves in one of three ways —
 *
 *   CATALOG_PRODUCT  a real product: package count = CEIL(need / package), EXACT
 *   FALLBACK_SEGMENT no product yet: category price for the chosen segment, CALCULATED
 *   EXCLUDED         "materialı özüm alacağam": kept as a shopping list, priced at 0
 *
 * Technological waste lives inside the required quantity here. The financial reserve
 * is a separate line and the two must never be applied to each other (master spec §15).
 */
class MaterialResolver
{
    /**
     * Finish work => the material it consumes, by area / length / piece.
     *
     * @var array<string, array{category: string, waste: ?string, unit: string}>
     */
    private const FINISH_MATERIALS = [
        'FLOOR_LAMINATE_INSTALL' => ['category' => 'MAT_FLOOR_LAMINATE', 'waste' => 'WASTE_LAMINATE', 'unit' => 'm²'],
        'FLOOR_SPC_INSTALL' => ['category' => 'MAT_FLOOR_SPC', 'waste' => 'WASTE_SPC', 'unit' => 'm²'],
        'FLOOR_PARQUET_INSTALL' => ['category' => 'MAT_FLOOR_PARQUET', 'waste' => 'WASTE_PARQUET', 'unit' => 'm²'],
        'FLOOR_TILE_INSTALL' => ['category' => 'MAT_FLOOR_TILE', 'waste' => 'WASTE_TILE_STANDARD', 'unit' => 'm²'],
        'FLOOR_STONE_INSTALL' => ['category' => 'MAT_FLOOR_STONE', 'waste' => 'WASTE_TILE_STANDARD', 'unit' => 'm²'],
        'TILE_INSTALL_STANDARD' => ['category' => 'MAT_WALL_TILE', 'waste' => 'WASTE_TILE_STANDARD', 'unit' => 'm²'],
        'TILE_INSTALL_LARGE' => ['category' => 'MAT_WALL_TILE', 'waste' => 'WASTE_TILE_STANDARD', 'unit' => 'm²'],
        'TILE_INSTALL_MOSAIC' => ['category' => 'MAT_WALL_TILE', 'waste' => 'WASTE_TILE_STANDARD', 'unit' => 'm²'],
        'WALL_WALLPAPER' => ['category' => 'MAT_WALL_WALLPAPER', 'waste' => null, 'unit' => 'm²'],
        'WALL_DECOR_PLASTER' => ['category' => 'MAT_WALL_DECOR_PLASTER', 'waste' => null, 'unit' => 'm²'],
        'WALL_PANEL_INSTALL' => ['category' => 'MAT_WALL_PANEL', 'waste' => null, 'unit' => 'm²'],
        'WALL_STONE_INSTALL' => ['category' => 'MAT_WALL_STONE', 'waste' => 'WASTE_TILE_STANDARD', 'unit' => 'm²'],
        'CEIL_GYPSUM_FLAT' => ['category' => 'MAT_CEIL_GYPSUM', 'waste' => null, 'unit' => 'm²'],
        'CEIL_GYPSUM_COMPLEX' => ['category' => 'MAT_CEIL_GYPSUM', 'waste' => null, 'unit' => 'm²'],
        'CEIL_STRETCH' => ['category' => 'MAT_CEIL_STRETCH', 'waste' => null, 'unit' => 'm²'],
        'SKIRTING_INSTALL' => ['category' => 'MAT_SKIRTING', 'waste' => 'WASTE_SKIRTING', 'unit' => 'p.m'],
        'DOOR_INSTALL_INTERIOR' => ['category' => 'MAT_DOOR_INTERIOR', 'waste' => null, 'unit' => 'əd'],
        'DOOR_INSTALL_HIDDEN' => ['category' => 'MAT_DOOR_HIDDEN', 'waste' => null, 'unit' => 'əd'],
        'DOOR_INSTALL_SLIDING' => ['category' => 'MAT_DOOR_SLIDING', 'waste' => null, 'unit' => 'əd'],
        'ELEC_SOCKET_POINT' => ['category' => 'MAT_ELECTRIC_ACCESSORIES', 'waste' => null, 'unit' => 'nöqtə'],
        'ELEC_SWITCH_POINT' => ['category' => 'MAT_ELECTRIC_ACCESSORIES', 'waste' => null, 'unit' => 'nöqtə'],
        'PLUMB_TOILET_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'waste' => null, 'unit' => 'əd'],
        'PLUMB_BASIN_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'waste' => null, 'unit' => 'əd'],
        'PLUMB_KITCHEN_SINK' => ['category' => 'MAT_SANITARY_BASIC', 'waste' => null, 'unit' => 'əd'],
        'PLUMB_BATHTUB_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'waste' => null, 'unit' => 'əd'],
        'PLUMB_MIXER_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'waste' => null, 'unit' => 'əd'],
        'LIGHT_SPOT_INSTALL' => ['category' => 'MAT_LIGHTING_BASIC', 'waste' => null, 'unit' => 'əd'],
        'LIGHT_PENDANT_INSTALL' => ['category' => 'MAT_LIGHTING_BASIC', 'waste' => null, 'unit' => 'əd'],
        'LIGHT_CHANDELIER_INSTALL' => ['category' => 'MAT_LIGHTING_BASIC', 'waste' => null, 'unit' => 'əd'],
    ];

    /**
     * Rough materials, whose quantity is a consumption norm rather than an area.
     * `multiplier` names the extra factor: coat/layer counts or a thickness in mm.
     *
     * @var array<string, array{category: string, norm: string, unit: string, multiplier: ?string}>
     */
    private const CONSUMABLES = [
        'WALL_PRIMER_BASE' => ['category' => 'MAT_PRIMER', 'norm' => 'PRIMER_CONSUMPTION', 'unit' => 'L', 'multiplier' => 'primerCoats'],
        'WALL_PRIMER_FINISH' => ['category' => 'MAT_PRIMER', 'norm' => 'PRIMER_CONSUMPTION', 'unit' => 'L', 'multiplier' => 'primerCoats'],
        'CEIL_PRIMER_BASE' => ['category' => 'MAT_PRIMER', 'norm' => 'PRIMER_CONSUMPTION', 'unit' => 'L', 'multiplier' => 'primerCoats'],
        'WALL_PUTTY' => ['category' => 'MAT_PUTTY', 'norm' => 'PUTTY_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'puttyLayers'],
        'CEIL_PUTTY' => ['category' => 'MAT_PUTTY', 'norm' => 'PUTTY_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'puttyLayers'],
        'WALL_PLASTER' => ['category' => 'MAT_PLASTER', 'norm' => 'PLASTER_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'plasterThickness'],
        'CEIL_PLASTER' => ['category' => 'MAT_PLASTER', 'norm' => 'PLASTER_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'plasterThickness'],
        'FLOOR_SCREED' => ['category' => 'MAT_SCREED', 'norm' => 'SCREED_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'screedThickness'],
        'FLOOR_SELF_LEVEL' => ['category' => 'MAT_SELF_LEVEL', 'norm' => 'SELF_LEVEL_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'selfLevelThickness'],
        'FLOOR_WATERPROOF' => ['category' => 'MAT_WATERPROOF', 'norm' => 'WATERPROOF_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'waterproofLayers'],
        'TILE_WATERPROOF_WALL' => ['category' => 'MAT_WATERPROOF', 'norm' => 'WATERPROOF_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'waterproofLayers'],
        'TILE_GROUT' => ['category' => 'MAT_GROUT', 'norm' => 'GROUT_CONSUMPTION', 'unit' => 'kq', 'multiplier' => null],
        'FLOOR_TILE_INSTALL' => ['category' => 'MAT_TILE_ADHESIVE', 'norm' => 'TILE_ADHESIVE_CONSUMPTION', 'unit' => 'kq', 'multiplier' => null],
        'TILE_INSTALL_STANDARD' => ['category' => 'MAT_TILE_ADHESIVE', 'norm' => 'TILE_ADHESIVE_CONSUMPTION', 'unit' => 'kq', 'multiplier' => null],
        'TILE_INSTALL_LARGE' => ['category' => 'MAT_TILE_ADHESIVE', 'norm' => 'TILE_ADHESIVE_CONSUMPTION', 'unit' => 'kq', 'multiplier' => null],
        'TILE_INSTALL_MOSAIC' => ['category' => 'MAT_TILE_ADHESIVE', 'norm' => 'TILE_ADHESIVE_CONSUMPTION', 'unit' => 'kq', 'multiplier' => null],
    ];

    /** Paint is special: per m² on the fallback, per litre once a real tin is chosen. */
    private const PAINT_WORKS = [
        'WALL_PAINT' => 'MAT_WALL_PAINT',
        'CEIL_PAINT' => 'MAT_CEIL_PAINT',
    ];

    /**
     * Catalog unit keys → what an estimate should print.
     *
     * The catalog stores the seller's chosen key ("m2", "lm"), which is fine in a
     * dropdown and wrong in a priced line: "79.39 m2 × 38.50 ₼/m2" reads like a typo
     * next to the engine's own "m²" rows.
     */
    private const PRODUCT_UNITS = [
        'piece' => 'əd',
        'm2' => 'm²',
        'lm' => 'p.m',
        'kg' => 'kq',
        'litre' => 'L',
        'set' => 'dəst',
        'roll' => 'rulon',
        'pack' => 'paket',
    ];

    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, EstimateLine>  $workLines
     * @param  array<string, mixed>  $materialInput  category id => {mode, productId}
     * @param  array<int, Product>  $products  catalog products keyed by id
     * @return array<int, EstimateLine>
     */
    public function resolve(array $workLines, array $materialInput, array $products, PriceSegment $segment): array
    {
        $requirements = [];

        foreach ($workLines as $line) {
            if ($line->quantity <= 0) {
                continue;
            }

            $this->collectFinish($line, $requirements);
            $this->collectConsumable($line, $requirements);
            $this->collectPaint($line, $requirements);
        }

        $lines = [];

        foreach ($requirements as $requirement) {
            $line = $this->priceRequirement($requirement, $materialInput, $products, $segment);

            if ($line) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    private function collectFinish(EstimateLine $line, array &$requirements): void
    {
        $map = self::FINISH_MATERIALS[$line->itemId] ?? null;

        if (! $map) {
            return;
        }

        $this->add($requirements, $map['category'], $map['unit'], $line->quantity, $this->config->wasteFactor($map['waste']), $line);
    }

    private function collectConsumable(EstimateLine $line, array &$requirements): void
    {
        $map = self::CONSUMABLES[$line->itemId] ?? null;

        if (! $map) {
            return;
        }

        $norm = $this->config->norm($map['norm']);

        if ($norm <= 0) {
            return;
        }

        $quantity = $line->quantity * $norm * $this->multiplier($map['multiplier']);

        $this->add($requirements, $map['category'], $map['unit'], $quantity, 1.0, $line);
    }

    /**
     * Paint quantity is only meaningful in litres, and litres need a tin: without a
     * chosen product the honest unit is the painted square metre and the category's
     * fallback price per m² (Material_Fallbacks marks those rows "m² fallback").
     */
    private function collectPaint(EstimateLine $line, array &$requirements): void
    {
        $category = self::PAINT_WORKS[$line->itemId] ?? null;

        if (! $category) {
            return;
        }

        $this->add($requirements, $category, 'm²', $line->quantity, 1.0, $line, [
            'coats' => $this->config->float('DEFAULT_PAINT_COATS', 2),
            'coverage' => $this->config->norm('PAINT_COVERAGE', 10),
            'convertsToLitres' => true,
        ]);
    }

    private function add(array &$requirements, string $category, string $unit, float $quantity, float $wasteFactor, EstimateLine $line, array $meta = []): void
    {
        $requirements[$category] ??= [
            'category' => $category,
            'unit' => $unit,
            'baseQty' => 0.0,
            'wasteFactor' => $wasteFactor,
            'rooms' => [],
            'meta' => $meta,
        ];

        $requirements[$category]['baseQty'] += $quantity;
        $requirements[$category]['wasteFactor'] = max($requirements[$category]['wasteFactor'], $wasteFactor);
        $requirements[$category]['meta'] = array_merge($requirements[$category]['meta'], $meta);

        if ($line->roomId) {
            $requirements[$category]['rooms'][$line->roomId] = $line->roomName;
        }
    }

    private function multiplier(?string $key): float
    {
        return match ($key) {
            'primerCoats' => $this->config->float('DEFAULT_PRIMER_COATS', 1),
            'puttyLayers' => $this->config->float('DEFAULT_WALL_PUTTY_LAYERS', 2),
            'waterproofLayers' => $this->config->float('DEFAULT_WATERPROOF_LAYERS', 2),
            'plasterThickness' => $this->config->float('PLASTER_DEFAULT_THICKNESS_MM', 20),
            'screedThickness' => $this->config->float('SCREED_DEFAULT_THICKNESS_MM', 50),
            'selfLevelThickness' => $this->config->float('SELF_LEVEL_DEFAULT_THICKNESS_MM', 5),
            default => 1.0,
        };
    }

    /** @param array<int, Product> $products */
    private function priceRequirement(array $requirement, array $materialInput, array $products, PriceSegment $segment): ?EstimateLine
    {
        $categoryId = $requirement['category'];
        $selection = $materialInput[$categoryId] ?? [];
        $mode = strtoupper((string) ($selection['mode'] ?? 'FALLBACK_SEGMENT'));

        $fallback = $this->config->materialFallback($categoryId);
        $name = $fallback?->name() ?? $categoryId;
        $requiredQty = round($requirement['baseQty'] * $requirement['wasteFactor'], 4);

        if ($requiredQty <= 0) {
            return null;
        }

        $roomId = count($requirement['rooms']) === 1 ? array_key_first($requirement['rooms']) : null;
        $roomName = $roomId ? $requirement['rooms'][$roomId] : null;

        $baseMeta = [
            'materialCategoryId' => $categoryId,
            'baseQty' => round($requirement['baseQty'], 4),
            'wastePercent' => round(($requirement['wasteFactor'] - 1) * 100, 2),
            'rooms' => array_values($requirement['rooms']),
        ];

        // "I will buy it myself": the requirement stays visible as a shopping list
        // item but adds nothing to the estimate.
        if ($mode === 'EXCLUDED') {
            return EstimateLine::make(
                itemId: $categoryId,
                lineType: LineType::Material,
                category: WorkCategory::Materials,
                name: $name,
                unit: $requirement['unit'],
                quantitySource: 'product',
                quantity: $requiredQty,
                unitPrice: 0.0,
                precisionClass: PrecisionClass::Estimated,
                source: LineSource::ManualQuote,
                roomId: $roomId,
                roomName: $roomName,
                contingencyEligible: false,
                meta: $baseMeta + ['mode' => 'EXCLUDED'],
            );
        }

        $product = $this->resolveProduct($selection, $products);

        if ($product) {
            return $this->catalogLine($product, $requirement, $requiredQty, $name, $roomId, $roomName, $baseMeta);
        }

        $price = $fallback?->priceFor($segment);

        return EstimateLine::make(
            itemId: $categoryId,
            lineType: LineType::Material,
            category: WorkCategory::Materials,
            name: $name,
            unit: $requirement['unit'],
            quantitySource: 'product',
            quantity: $requiredQty,
            unitPrice: $price,
            precisionClass: PrecisionClass::Calculated,
            source: LineSource::FallbackCategoryPrice,
            roomId: $roomId,
            roomName: $roomName,
            meta: $baseMeta + ['mode' => 'FALLBACK_SEGMENT', 'segment' => $segment->value],
        );
    }

    /** Catalog unit key → the symbol the estimate prints. Unknown keys pass through. */
    private static function displayUnit(?string $unit): ?string
    {
        if ($unit === null || $unit === '') {
            return null;
        }

        return self::PRODUCT_UNITS[$unit] ?? $unit;
    }

    /** @param array<int, Product> $products */
    private function resolveProduct(array $selection, array $products): ?Product
    {
        $productId = $selection['productId'] ?? null;

        return $productId ? ($products[(int) $productId] ?? null) : null;
    }

    /**
     * A real product is billed in whole packages: 44.94 m² of SPC in 2.22 m² packs is
     * 21 packs, never 20.24 and never "1 item" (master spec §13).
     */
    private function catalogLine(
        Product $product,
        array $requirement,
        float $requiredQty,
        string $fallbackName,
        ?string $roomId,
        ?string $roomName,
        array $baseMeta,
    ): EstimateLine {
        $meta = $baseMeta + [
            'mode' => 'CATALOG_PRODUCT',
            'productId' => $product->id,
            'productSlug' => $product->slug,
            'requiredQty' => $requiredQty,
        ];

        // Paint is entered per m² but bought per tin: convert once a tin is known.
        if (($requirement['meta']['convertsToLitres'] ?? false) && ($coverage = (float) ($requirement['meta']['coverage'] ?? 0)) > 0) {
            $litres = $requiredQty * (float) ($requirement['meta']['coats'] ?? 1) / $coverage;
            $meta['paintedArea'] = $requiredQty;
            $meta['litres'] = round($litres, 3);
            $requiredQty = $litres;
        }

        $packageSize = (float) ($product->package_size ?? 0);
        $unitPrice = (float) $product->price;

        if ($packageSize > 0) {
            $packages = (int) ceil(round($requiredQty / $packageSize, 6));
            $meta['packageSize'] = $packageSize;
            $meta['packageQty'] = $packages;

            return EstimateLine::make(
                itemId: 'PRODUCT_'.$product->id,
                lineType: LineType::Material,
                category: WorkCategory::Materials,
                name: $product->name,
                unit: $product->package_unit ?: 'paket',
                quantitySource: 'product',
                quantity: $packages,
                unitPrice: $unitPrice,
                precisionClass: PrecisionClass::Exact,
                source: LineSource::CatalogPrice,
                roomId: $roomId,
                roomName: $roomName,
                meta: $meta,
            );
        }

        // Sold by the metre / piece: the requirement is the quantity, rounded up to
        // whole units for a countable product — half a door does not exist.
        $quantity = in_array($product->unit, ['piece', 'set', 'roll', 'pack', 'əd', 'ədəd', 'pcs', 'шт'], true)
            ? (float) ceil($requiredQty)
            : round($requiredQty, 2);

        return EstimateLine::make(
            itemId: 'PRODUCT_'.$product->id,
            lineType: LineType::Material,
            category: WorkCategory::Materials,
            name: $product->name,
            unit: self::displayUnit($product->unit) ?: $requirement['unit'],
            quantitySource: 'product',
            quantity: $quantity,
            unitPrice: $unitPrice,
            precisionClass: PrecisionClass::Exact,
            source: LineSource::CatalogPrice,
            roomId: $roomId,
            roomName: $roomName,
            meta: $meta,
        );
    }

    /**
     * Products the visitor already had in their cart before opening the calculator.
     * They are offered, not forced: `includeInEstimate` decides, and the quantity is
     * the cart quantity because nothing in the calculator asked for them.
     *
     * @param  array<int, array{product: Product, quantity: int}>  $cartItems
     * @return array<int, EstimateLine>
     */
    public function cartLines(array $cartItems, array $excludedProductIds, array $alreadyPricedProductIds = []): array
    {
        $lines = [];

        foreach ($cartItems as $item) {
            $product = $item['product'];

            if (in_array($product->id, $excludedProductIds, true)) {
                continue;
            }

            // The same product cannot be billed twice. If it was chosen as the
            // material for a surface, the calculator already knows how much of it the
            // job needs — and that requirement wins over the number the visitor
            // happened to put in their cart (master spec §13).
            if (in_array($product->id, $alreadyPricedProductIds, true)) {
                continue;
            }

            $lines[] = EstimateLine::make(
                itemId: 'CART_'.$product->id,
                lineType: LineType::Material,
                category: WorkCategory::Materials,
                name: $product->name,
                unit: self::displayUnit($product->unit) ?: 'əd',
                quantitySource: 'product',
                quantity: max(1, (int) $item['quantity']),
                unitPrice: (float) $product->price,
                precisionClass: PrecisionClass::Exact,
                source: LineSource::CatalogPrice,
                meta: [
                    'fromCart' => true,
                    'productId' => $product->id,
                    'productSlug' => $product->slug,
                ],
            );
        }

        return $lines;
    }
}
