<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\FinishFamily;
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
 * rooms: if the visitor removes the laminate, its laminate disappears with it. There
 * are three kinds of requirement —
 *
 *   the finish itself   the finish row says the unit, the waste and the segment price
 *   consumables         primer, plaster, putty, screed, adhesive… from a norm per m²
 *   items               a door, a fixture, a light fitting, a socket — one per work unit
 *
 * — and each resolves in one of three ways:
 *
 *   CATALOG_PRODUCT  a real product: package count = CEIL(need / package), EXACT
 *   FALLBACK_SEGMENT no product yet: the finish's / category's segment price, CALCULATED
 *   EXCLUDED         "materialı özüm alacağam": kept as a shopping list, priced at 0
 *
 * Technological waste lives inside the required quantity here. The financial reserve
 * is a separate line and the two must never be applied to each other (master spec §15).
 */
class MaterialResolver
{
    /**
     * Rough materials whose quantity is a consumption norm per m² of the work.
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
        'FLOOR_WATERPROOF' => ['category' => 'MAT_WATERPROOF', 'norm' => 'WATERPROOF_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'waterproofLayers'],
        'TILE_WATERPROOF_WALL' => ['category' => 'MAT_WATERPROOF', 'norm' => 'WATERPROOF_CONSUMPTION', 'unit' => 'kq', 'multiplier' => 'waterproofLayers'],
        'TILE_GROUT' => ['category' => 'MAT_GROUT', 'norm' => 'GROUT_CONSUMPTION', 'unit' => 'kq', 'multiplier' => null],
    ];

    /**
     * Items bought one per unit of work: a door per door installed, a fitting per
     * fitting hung, a socket per socket point, a metre of cable per metre pulled.
     *
     * @var array<string, array{category: string, unit: string, waste: ?string}>
     */
    private const ITEMS = [
        'SKIRTING_INSTALL' => ['category' => 'MAT_SKIRTING', 'unit' => 'p.m', 'waste' => 'WASTE_SKIRTING'],
        'DOOR_INSTALL_INTERIOR' => ['category' => 'MAT_DOOR_INTERIOR', 'unit' => 'əd', 'waste' => null],
        'ELEC_SOCKET_POINT' => ['category' => 'MAT_ELECTRIC_ACCESSORIES', 'unit' => 'nöqtə', 'waste' => null],
        'ELEC_SWITCH_POINT' => ['category' => 'MAT_ELECTRIC_ACCESSORIES', 'unit' => 'nöqtə', 'waste' => null],
        'ELEC_CABLE' => ['category' => 'MAT_CABLE', 'unit' => 'm', 'waste' => null],
        'PLUMB_TOILET_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'unit' => 'əd', 'waste' => null],
        'PLUMB_BASIN_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'unit' => 'əd', 'waste' => null],
        'PLUMB_SHOWER_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'unit' => 'əd', 'waste' => null],
        'PLUMB_BATHTUB_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'unit' => 'əd', 'waste' => null],
        'PLUMB_KITCHEN_SINK' => ['category' => 'MAT_SANITARY_BASIC', 'unit' => 'əd', 'waste' => null],
        'PLUMB_MIXER_INSTALL' => ['category' => 'MAT_SANITARY_BASIC', 'unit' => 'əd', 'waste' => null],
        'LIGHT_FIXTURE_INSTALL' => ['category' => 'MAT_LIGHTING_BASIC', 'unit' => 'əd', 'waste' => null],
        'FLOOR_HEAT_ELECTRIC' => ['category' => 'MAT_WARM_FLOOR', 'unit' => 'm²', 'waste' => null],
    ];

    /**
     * Catalog unit keys → what an estimate should print. The catalog stores the
     * seller's chosen key ("m2", "lm"), which is fine in a dropdown and wrong in a
     * priced line next to the engine's own "m²" rows.
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
     * @param  array<string, mixed>  $materialInput  material id => {mode, productId}
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
            $this->collectItem($line, $requirements);
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

    /**
     * The finish's own material: the finish row says the unit, the waste and the
     * price. A tile-type finish also eats adhesive by the square metre.
     */
    private function collectFinish(EstimateLine $line, array &$requirements): void
    {
        if (($line->meta['source'] ?? null) !== 'FINISH') {
            return;
        }

        $finish = $this->config->finish($line->meta['finish'] ?? null);

        if (! $finish) {
            return;
        }

        $this->add($requirements, $finish->finishId, $finish->unit, $line->quantity, $finish->wasteFactor(), $line, [
            'finish' => $finish->finishId,
            'family' => $finish->family->value,
        ]);

        if ($finish->family === FinishFamily::Tile) {
            $norm = $this->config->norm('TILE_ADHESIVE_CONSUMPTION');

            if ($norm > 0) {
                $this->add($requirements, 'MAT_TILE_ADHESIVE', 'kq', $line->quantity * $norm, 1.0, $line);
            }
        }
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

    private function collectItem(EstimateLine $line, array &$requirements): void
    {
        $map = self::ITEMS[$line->itemId] ?? null;

        if (! $map) {
            return;
        }

        $this->add($requirements, $map['category'], $map['unit'], $line->quantity, $this->config->wasteFactor($map['waste']), $line);
    }

    private function add(array &$requirements, string $id, string $unit, float $quantity, float $wasteFactor, EstimateLine $line, array $meta = []): void
    {
        $requirements[$id] ??= [
            'id' => $id,
            'unit' => $unit,
            'baseQty' => 0.0,
            'wasteFactor' => $wasteFactor,
            'rooms' => [],
            'meta' => $meta,
        ];

        $requirements[$id]['baseQty'] += $quantity;
        $requirements[$id]['wasteFactor'] = max($requirements[$id]['wasteFactor'], $wasteFactor);
        $requirements[$id]['meta'] = array_merge($requirements[$id]['meta'], $meta);

        if ($line->roomId) {
            $requirements[$id]['rooms'][$line->roomId] = $line->roomName;
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
            default => 1.0,
        };
    }

    /** @param array<int, Product> $products */
    private function priceRequirement(array $requirement, array $materialInput, array $products, PriceSegment $segment): ?EstimateLine
    {
        $id = $requirement['id'];
        $selection = $materialInput[$id] ?? [];
        $mode = strtoupper((string) ($selection['mode'] ?? 'FALLBACK_SEGMENT'));

        // A finish prices its own material; everything else is a fallback category.
        $finish = $this->config->finish($id);
        $fallback = $finish ? null : $this->config->materialFallback($id);

        $name = $finish
            ? __('calculator-detailed.line.material', ['name' => $finish->name()])
            : ($fallback?->name() ?? $id);
        $price = $finish ? $finish->materialPrice($segment) : $fallback?->priceFor($segment);

        $requiredQty = round($requirement['baseQty'] * $requirement['wasteFactor'], 4);

        if ($requiredQty <= 0) {
            return null;
        }

        $roomId = count($requirement['rooms']) === 1 ? array_key_first($requirement['rooms']) : null;
        $roomName = $roomId ? $requirement['rooms'][$roomId] : null;

        // The finish's labour line already carries the finish id; its material line
        // gets its own, so the two can never be confused in a trace or a snapshot.
        // meta.materialId stays the selection key the materials step posts back.
        $lineId = $finish ? $id.'_MATERIAL' : $id;

        $baseMeta = $requirement['meta'] + [
            'materialId' => $id,
            'baseQty' => round($requirement['baseQty'], 4),
            'wastePercent' => round(($requirement['wasteFactor'] - 1) * 100, 2),
            'rooms' => array_values($requirement['rooms']),
        ];

        // "I will buy it myself": the requirement stays visible as a shopping list
        // item but adds nothing to the estimate.
        if ($mode === 'EXCLUDED') {
            return EstimateLine::make(
                itemId: $lineId,
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
            return $this->catalogLine($product, $requirement, $requiredQty, $roomId, $roomName, $baseMeta);
        }

        return EstimateLine::make(
            itemId: $lineId,
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

        // A square-metre requirement bought by the litre is paint: the tin covers
        // PAINT_COVERAGE m² per litre per coat. The rule hangs on the product's unit,
        // not on the finish's name, so it holds for any coat the admin adds.
        if ($requirement['unit'] === 'm²' && $product->unit === 'litre') {
            $coverage = $this->config->norm('PAINT_COVERAGE', 10);
            $coats = $this->config->float('DEFAULT_PAINT_COATS', 2);

            if ($coverage > 0) {
                $litres = $requiredQty * $coats / $coverage;
                $meta['paintedArea'] = $requiredQty;
                $meta['coats'] = $coats;
                $meta['litres'] = round($litres, 3);
                $requiredQty = $litres;
            }
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
