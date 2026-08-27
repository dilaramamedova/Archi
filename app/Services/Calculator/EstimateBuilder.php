<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\LineSource;
use App\Enums\Calculator\LineType;
use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\PropertyMarketType;
use App\Enums\Calculator\WorkCategory;
use App\Models\Product;

/**
 * The single recalculation entry point (master spec §18.1):
 *
 *     geometry → works → materials → catalog prices → logistics → contingency → totals
 *
 * It is a pure function of (inputs, configuration). Nothing is stored while it runs,
 * so the same inputs and the same rates always produce the same estimate — which is
 * what makes a snapshot reproducible and the acceptance tests meaningful.
 */
class EstimateBuilder
{
    public function __construct(
        private readonly CalculatorConfig $config,
        private readonly GeometryEngine $geometry,
        private readonly DependencyEngine $dependencies,
        private readonly ModuleResolver $modules,
        private readonly MaterialResolver $materials,
        private readonly LogisticsResolver $logistics,
    ) {}

    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<int, Product>  $products  catalog products keyed by id
     * @param  array<int, array{product: Product, quantity: int}>  $cartItems
     */
    public function build(array $inputs, array $products = [], array $cartItems = []): array
    {
        $object = is_array($inputs['object'] ?? null) ? $inputs['object'] : [];

        // The object condition seeds the state of every surface zone. When it was not
        // answered, the new-build / second-hand choice is the next best evidence
        // (master spec §7) — a new build arrives bare, a resale carries an old finish.
        $condition = ObjectCondition::tryFrom(strtoupper((string) ($object['condition'] ?? '')))
            ?? PropertyMarketType::tryFrom(strtoupper((string) ($object['marketType'] ?? '')))?->defaultCondition()
            ?? ObjectCondition::Shell;
        $segment = PriceSegment::fromSlug($object['segment'] ?? null) ?? PriceSegment::Standard;

        $rooms = $this->geometry->buildRooms(is_array($inputs['rooms'] ?? null) ? $inputs['rooms'] : [], $condition);
        $derived = $this->geometry->deriveQuantities($rooms);

        $requests = array_merge(
            $this->dependencies->suggest($rooms),
            $this->modules->resolve($rooms, is_array($inputs['modules'] ?? null) ? $inputs['modules'] : [], $object, $derived),
        );

        $selection = is_array($inputs['works'] ?? null) ? $inputs['works'] : [];
        $suggestedIds = array_values(array_unique(array_map(fn (WorkRequest $r) => $r->workId, $requests)));

        $requests = $this->applySelection($requests, $selection);
        $requests = $this->consolidate($requests);

        $cityCoefficient = $this->config->cityCoefficient($object['cityKey'] ?? null);

        $workLines = $this->priceWorks($requests, $cityCoefficient);

        $materialLines = $this->materials->resolve(
            $workLines,
            is_array($inputs['materials'] ?? null) ? $inputs['materials'] : [],
            $products,
            $segment,
        );

        $basket = is_array($inputs['basket'] ?? null) ? $inputs['basket'] : [];

        if ($basket['useCart'] ?? true) {
            // Products already priced as a material must not come back as cart rows —
            // the calculator knows how much of them the job needs, which is the whole
            // point of choosing them there (master spec §13).
            $pricedProductIds = array_values(array_filter(array_map(
                fn (EstimateLine $line) => $line->meta['productId'] ?? null,
                $materialLines,
            )));

            $materialLines = array_merge(
                $materialLines,
                $this->materials->cartLines(
                    $cartItems,
                    array_map('intval', $basket['excluded'] ?? []),
                    array_map('intval', $pricedProductIds),
                ),
            );
        }

        $logistics = $this->logistics->resolve(
            $materialLines,
            $products,
            $object,
            is_array($inputs['modules']['logistics'] ?? null) ? $inputs['modules']['logistics'] : [],
        );

        $logisticsLines = $this->priceWorks($this->consolidate($logistics['requests']), $cityCoefficient);

        $lines = array_merge($workLines, $materialLines, $logisticsLines);

        $totals = $this->totals($lines);
        $contingency = $this->contingencyLine($lines, $totals['contingencyBase']);

        if ($contingency) {
            $lines[] = $contingency;
        }

        $vat = $this->vatLine($totals['baseSubtotal'] + ($contingency?->lineTotal ?? 0));

        if ($vat) {
            $lines[] = $vat;
        }

        $grandTotal = round(
            $totals['baseSubtotal'] + ($contingency?->lineTotal ?? 0) + ($vat?->lineTotal ?? 0),
            2,
        );

        return [
            'currency' => 'AZN',
            'calculatorVersion' => CalculatorConfig::CALCULATOR_VERSION,
            'configVersion' => $this->config->configVersion(),
            'segment' => $segment->value,
            'cityCoefficient' => $cityCoefficient,
            'rooms' => array_map(fn (RoomGeometry $room) => $room->toArray(), $rooms),
            'derived' => $derived,
            'suggestedWorks' => $suggestedIds,
            'lines' => array_map(fn (EstimateLine $line) => $line->toArray(), $lines),
            'groups' => $this->groupByCategory($lines),
            'byRoom' => $this->groupByRoom($lines),
            'totals' => [
                'works' => $totals['works'],
                'materials' => $totals['materials'],
                'logistics' => $totals['logistics'],
                'services' => $totals['services'],
                'baseSubtotal' => $totals['baseSubtotal'],
                'contingency' => $contingency?->lineTotal ?? 0.0,
                'contingencyPercent' => $this->config->float('CONTINGENCY_PERCENT', 0),
                'vat' => $vat?->lineTotal ?? 0.0,
                'grandTotal' => $grandTotal,
                'grandTotalRounded' => $this->config->bool('ROUND_TOTALS_TO_INTEGER', true)
                    ? round($grandTotal)
                    : $grandTotal,
            ],
            'warnings' => $this->warnings($rooms, $object, $derived, $lines, $logistics['meta']),
            'logistics' => $logistics['meta'],
            // Only a line that actually carries money earns the "təxmini" disclaimer.
            // A material the visitor buys themselves is estimated in the same sense,
            // but flagging it would put an asterisk on an estimate that has none.
            'hasEstimatedLines' => (bool) array_filter(
                $lines,
                fn (EstimateLine $l) => $l->precisionClass === PrecisionClass::Estimated && $l->lineTotal > 0,
            ),
            'priceMissing' => array_values(array_unique(array_map(
                fn (EstimateLine $l) => $l->itemId,
                array_filter($lines, fn (EstimateLine $l) => $l->isPriceMissing()),
            ))),
        ];
    }

    /**
     * The visitor's edits win over the suggestions: a removed work disappears, an
     * added work appears, an overridden quantity replaces the derived one. Removing
     * a suggested step is allowed on purpose — the UI warns about the technological
     * risk instead of forbidding it (master spec §9).
     *
     * @param  array<int, WorkRequest>  $requests
     * @return array<int, WorkRequest>
     */
    private function applySelection(array $requests, array $selection): array
    {
        $kept = [];
        $seen = [];

        foreach ($requests as $request) {
            $choice = $selection[$request->workId] ?? null;
            $seen[$request->workId] = true;

            if (is_array($choice) && array_key_exists('selected', $choice) && ! $choice['selected']) {
                continue;
            }

            if (is_array($choice) && ($choice['quantity'] ?? null) !== null && $request->roomId === null) {
                $request = $request->withQuantity((float) $choice['quantity']);
            }

            $kept[] = $request;
        }

        // Works the dependency engine did not suggest but the visitor asked for.
        foreach ($selection as $workId => $choice) {
            if (isset($seen[$workId]) || ! is_array($choice) || ! ($choice['selected'] ?? false)) {
                continue;
            }

            $quantity = (float) ($choice['quantity'] ?? 0);

            if ($quantity > 0) {
                $kept[] = new WorkRequest($workId, $quantity, suggested: false);
            }
        }

        return $kept;
    }

    /**
     * The same work in the same room is one line, not five (master spec §19). This
     * is what turns "primer under the plaster" and "primer under the paint" of five
     * separate zones into two honest rows per room.
     *
     * @param  array<int, WorkRequest>  $requests
     * @return array<int, WorkRequest>
     */
    private function consolidate(array $requests): array
    {
        $merged = [];

        foreach ($requests as $request) {
            $key = $request->key();

            if (! isset($merged[$key])) {
                $merged[$key] = $request;

                continue;
            }

            $merged[$key] = $merged[$key]->withQuantity($merged[$key]->quantity + $request->quantity);
        }

        return array_values($merged);
    }

    /**
     * @param  array<int, WorkRequest>  $requests
     * @return array<int, EstimateLine>
     */
    private function priceWorks(array $requests, float $cityCoefficient): array
    {
        $lines = [];

        foreach ($requests as $request) {
            $rate = $this->config->workRate($request->workId);

            if (! $rate) {
                continue;
            }

            $coefficients = [];

            foreach ($request->coefficientIds as $id) {
                $value = $this->config->coefficient($id);

                if (abs($value - 1.0) > 0.0001) {
                    $coefficients[] = ['id' => $id, 'value' => $value];
                }
            }

            if (abs($cityCoefficient - 1.0) > 0.0001) {
                $coefficients[] = ['id' => 'CITY', 'value' => $cityCoefficient];
            }

            $lines[] = EstimateLine::make(
                itemId: $rate->workId,
                lineType: $rate->category->lineType(),
                category: $rate->category,
                name: $rate->name(),
                unit: $rate->unit,
                quantitySource: $rate->quantitySource,
                quantity: $request->quantity,
                unitPrice: $rate->rate,
                coefficients: $coefficients,
                minimumCharge: $rate->minimumCharge,
                precisionClass: $request->precisionClass ?? $rate->precisionClass,
                source: LineSource::AdminRate,
                roomId: $request->roomId,
                roomName: $request->roomName,
                contingencyEligible: $rate->contingencyEligible,
                meta: $request->meta + ['suggested' => $request->suggested],
            );
        }

        return $lines;
    }

    /** @param array<int, EstimateLine> $lines */
    private function totals(array $lines): array
    {
        $totals = ['works' => 0.0, 'materials' => 0.0, 'logistics' => 0.0, 'services' => 0.0, 'contingencyBase' => 0.0];

        foreach ($lines as $line) {
            $bucket = match ($line->lineType) {
                LineType::Work => 'works',
                LineType::Material => 'materials',
                LineType::Logistics => 'logistics',
                LineType::Service => 'services',
                default => null,
            };

            if ($bucket === null) {
                continue;
            }

            $totals[$bucket] += $line->lineTotal;

            if ($line->contingencyEligible) {
                $totals['contingencyBase'] += $line->lineTotal;
            }
        }

        $totals = array_map(fn (float $v) => round($v, 2), $totals);
        $totals['baseSubtotal'] = round(
            $totals['works'] + $totals['materials'] + $totals['logistics'] + $totals['services'],
            2,
        );

        return $totals;
    }

    /**
     * The financial reserve — a separate row, applied to the eligible subtotal only.
     * It is not the material waste percentage and must never be applied on top of it
     * a second time (master spec §15).
     */
    private function contingencyLine(array $lines, float $eligibleSubtotal): ?EstimateLine
    {
        $percent = $this->config->float('CONTINGENCY_PERCENT', 0);

        if ($percent <= 0 || $eligibleSubtotal <= 0) {
            return null;
        }

        return EstimateLine::make(
            itemId: 'CONTINGENCY',
            lineType: LineType::Contingency,
            category: WorkCategory::Reserve,
            name: __('calculator-detailed.summary.contingency'),
            unit: '%',
            quantitySource: 'fixed',
            quantity: 1,
            unitPrice: round($eligibleSubtotal * $percent / 100, 2),
            precisionClass: PrecisionClass::Calculated,
            source: LineSource::AdminRate,
            contingencyEligible: false,
            meta: ['percent' => $percent, 'base' => round($eligibleSubtotal, 2)],
        );
    }

    /**
     * VAT was the one question the source documents left open (master spec audit
     * §2.5). It is answered by configuration rather than by guesswork: by default the
     * rates are treated as VAT-inclusive and nothing is added, and Archi can switch
     * the mode to ADDED to show it as its own row on the estimate and the invoice.
     */
    private function vatLine(float $base): ?EstimateLine
    {
        $mode = strtoupper((string) $this->config->setting('VAT_MODE', 'INCLUDED'));
        $percent = $this->config->float('VAT_PERCENT', 0);

        if ($mode !== 'ADDED' || $percent <= 0 || $base <= 0) {
            return null;
        }

        return EstimateLine::make(
            itemId: 'VAT',
            lineType: LineType::Contingency,
            category: WorkCategory::Reserve,
            name: __('calculator-detailed.summary.vat'),
            unit: '%',
            quantitySource: 'fixed',
            quantity: 1,
            unitPrice: round($base * $percent / 100, 2),
            precisionClass: PrecisionClass::Calculated,
            source: LineSource::AdminRate,
            contingencyEligible: false,
            meta: ['percent' => $percent, 'base' => round($base, 2)],
        );
    }

    /** @param array<int, EstimateLine> $lines */
    private function groupByCategory(array $lines): array
    {
        $groups = [];

        foreach ($lines as $line) {
            $key = $line->category->value;

            $groups[$key] ??= ['category' => $key, 'label' => __($line->category->labelKey()), 'total' => 0.0, 'lines' => []];
            $groups[$key]['total'] = round($groups[$key]['total'] + $line->lineTotal, 2);
            $groups[$key]['lines'][] = $line->itemId;
        }

        return array_values($groups);
    }

    /**
     * The same lines aggregated by room (master spec §16.2) — "Qonaq otağı — 8 400
     * AZN". A second view of one calculation, never a second calculation.
     *
     * @param  array<int, EstimateLine>  $lines
     */
    private function groupByRoom(array $lines): array
    {
        $rooms = [];

        foreach ($lines as $line) {
            if (! $line->roomId) {
                continue;
            }

            $rooms[$line->roomId] ??= ['roomId' => $line->roomId, 'name' => $line->roomName, 'total' => 0.0];
            $rooms[$line->roomId]['total'] = round($rooms[$line->roomId]['total'] + $line->lineTotal, 2);
        }

        return array_values($rooms);
    }

    /**
     * Non-blocking notices. Everything that would otherwise silently distort the
     * estimate is surfaced here rather than swallowed.
     *
     * @param  array<int, RoomGeometry>  $rooms
     * @param  array<int, EstimateLine>  $lines
     */
    private function warnings(array $rooms, array $object, array $derived, array $lines, array $logisticsMeta): array
    {
        $warnings = [];

        foreach ($rooms as $room) {
            foreach ($room->warnings as $warning) {
                $warnings[] = ['code' => $warning, 'roomId' => $room->id, 'roomName' => $room->name];
            }
        }

        // The stated object area and the sum of the rooms are allowed to differ a
        // little; beyond the tolerance the visitor is told, never blocked.
        $objectArea = (float) ($object['area'] ?? 0);
        $roomsArea = (float) ($derived['TOTAL_FLOOR_AREA'] ?? 0);
        $tolerance = $this->config->float('AREA_TOLERANCE_PERCENT', 5);

        if ($objectArea > 0 && $roomsArea > 0) {
            $deviation = abs($objectArea - $roomsArea) / $objectArea * 100;

            if ($deviation > $tolerance) {
                $warnings[] = [
                    'code' => 'AREA_MISMATCH',
                    'objectArea' => $objectArea,
                    'roomsArea' => round($roomsArea, 2),
                    'deviationPercent' => round($deviation, 1),
                ];
            }
        }

        foreach ($lines as $line) {
            if ($line->isPriceMissing()) {
                $warnings[] = ['code' => 'PRICE_MISSING', 'itemId' => $line->itemId, 'name' => $line->name];
            }
        }

        if (! ($logisticsMeta['complete'] ?? true)) {
            $warnings[] = ['code' => 'LOGISTICS_INCOMPLETE', 'products' => $logisticsMeta['productsWithoutLogisticsData'] ?? 0];
        }

        return $warnings;
    }
}
