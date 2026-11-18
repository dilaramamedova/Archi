<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\LineSource;
use App\Enums\Calculator\LineType;
use App\Enums\Calculator\ObjectCondition;
use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\WorkCategory;
use App\Models\Product;

/**
 * The single recalculation entry point (master spec §18.1):
 *
 *     geometry → works → materials → catalog prices → logistics → stages →
 *     contractor margin → contingency → totals
 *
 * Every line belongs to a stage (TZ 17.11.2026 §2): WHITE_BOX or FINISH, or none
 * for the services outside both (the design project). Logistics and the final
 * cleaning belong to the last stage the visitor ordered. The money is
 *
 *     Base        = works + materials bought through Archi + logistics + services
 *     Margin      = Base × CONTRACTOR_MARGIN_PERCENT
 *     Contingency = (eligible Base + its margin) × CONTINGENCY_PERCENT
 *     Total       = Base + Margin + Contingency (+ VAT in ADDED mode)
 *
 * — once per stage and once for the whole job, so the three totals always add up.
 *
 * It is a pure function of (inputs, configuration). Nothing is stored while it runs,
 * so the same inputs and the same rates always produce the same estimate — which is
 * what makes a snapshot reproducible and the acceptance tests meaningful.
 */
class EstimateBuilder
{
    public const LEVEL_FULL = 'FULL';

    public const LEVEL_WHITE_BOX = 'WHITE_BOX';

    public const STAGE_WHITE_BOX = 'WHITE_BOX';

    public const STAGE_FINISH = 'FINISH';

    /** A rate whose stage is «the last one ordered» (logistics, final cleaning). */
    public const STAGE_LAST = 'LAST';

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

        // The object condition seeds the state of every surface zone and the
        // demolition / rewiring defaults (master spec §7).
        $condition = ObjectCondition::fromAny($object['condition'] ?? null) ?? ObjectCondition::Shell;
        $segment = PriceSegment::fromSlug($object['segment'] ?? null) ?? PriceSegment::Standard;
        $level = strtoupper((string) ($object['level'] ?? '')) === self::LEVEL_WHITE_BOX ? self::LEVEL_WHITE_BOX : self::LEVEL_FULL;
        $lastStage = $level === self::LEVEL_WHITE_BOX ? self::STAGE_WHITE_BOX : self::STAGE_FINISH;

        $rooms = $this->geometry->buildRooms(is_array($inputs['rooms'] ?? null) ? $inputs['rooms'] : [], $condition, $object);
        $derived = $this->geometry->deriveQuantities($rooms);
        $calcArea = round(ModuleResolver::calcArea($object, $derived), 2);

        $requests = array_merge(
            $this->dependencies->suggest($rooms),
            $this->modules->resolve($rooms, is_array($inputs['modules'] ?? null) ? $inputs['modules'] : [], $object, $derived, $condition),
        );

        $selection = is_array($inputs['works'] ?? null) ? $inputs['works'] : [];
        $suggestedIds = array_values(array_unique(array_map(fn (WorkRequest $r) => $r->workId, $requests)));

        $requests = $this->applySelection($requests, $selection, $level);
        $requests = $this->consolidate($requests);

        $cityCoefficient = $this->config->cityCoefficient($object['cityKey'] ?? null);

        $workLines = $this->priceWorks($requests, $cityCoefficient);

        $materialLines = $this->materials->resolve(
            $workLines,
            is_array($inputs['materials'] ?? null) ? $inputs['materials'] : [],
            $products,
            $segment,
            $level === self::LEVEL_WHITE_BOX ? self::STAGE_FINISH : null,
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

        $logistics = $this->logistics->resolve($materialLines, $object, $derived);

        $logisticsLines = $this->priceWorks($this->consolidate($logistics['requests']), $cityCoefficient);

        $lines = array_map(
            fn (EstimateLine $line) => $line->withStage($this->stageOf($line, $lastStage)),
            array_merge($workLines, $materialLines, $logisticsLines),
        );

        $totals = $this->totals($lines);
        $stages = $this->stageTotals($lines);

        if ($margin = $this->marginLine($totals['baseSubtotal'], $stages)) {
            $lines[] = $margin;
        }

        if ($contingency = $this->contingencyLine($stages)) {
            $lines[] = $contingency;
        }

        $vat = $this->vatLine($totals['baseSubtotal'] + ($margin?->lineTotal ?? 0) + ($contingency?->lineTotal ?? 0));

        if ($vat) {
            $lines[] = $vat;
        }

        $grandTotal = round(
            $totals['baseSubtotal'] + ($margin?->lineTotal ?? 0) + ($contingency?->lineTotal ?? 0) + ($vat?->lineTotal ?? 0),
            2,
        );

        return [
            'currency' => 'AZN',
            'calculatorVersion' => CalculatorConfig::CALCULATOR_VERSION,
            'configVersion' => $this->config->configVersion(),
            'segment' => $segment->value,
            'level' => $level,
            // THE area of the estimate — the sum of the rooms (TZ §5).
            'calcArea' => $calcArea,
            // At least one room was filled from the norms: the estimate says so.
            'pointsByNorm' => (bool) array_filter($rooms, fn (RoomGeometry $room) => $room->pointsByNorm),
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
                'margin' => $margin?->lineTotal ?? 0.0,
                'marginPercent' => $this->marginPercent(),
                'contingency' => $contingency?->lineTotal ?? 0.0,
                'contingencyPercent' => $this->config->float('CONTINGENCY_PERCENT', 0),
                'vat' => $vat?->lineTotal ?? 0.0,
                'grandTotal' => $grandTotal,
                'grandTotalRounded' => $this->config->bool('ROUND_TOTALS_TO_INTEGER', true)
                    ? round($grandTotal)
                    : $grandTotal,
                'perM2' => $calcArea > 0 ? round($grandTotal / $calcArea, 2) : 0.0,
                'stages' => [
                    self::STAGE_WHITE_BOX => $this->publicStage($stages[self::STAGE_WHITE_BOX]),
                    self::STAGE_FINISH => $this->publicStage($stages[self::STAGE_FINISH]),
                ],
                'servicesBlock' => $this->publicStage($stages['SERVICES']),
                'lastStage' => $lastStage,
                'excludedMaterials' => round(array_sum(array_map(
                    fn (EstimateLine $l) => (float) ($l->meta['estimatedTotal'] ?? 0),
                    array_filter($lines, fn (EstimateLine $l) => ($l->meta['mode'] ?? null) === 'EXCLUDED'),
                )), 2),
            ],
            'condition' => $condition->value,
            'warnings' => $this->warnings($rooms, $object, $derived, $lines),
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
    private function applySelection(array $requests, array $selection, string $level = self::LEVEL_FULL): array
    {
        $kept = [];
        $seen = [];

        foreach ($requests as $request) {
            $choice = $selection[$request->workId] ?? null;
            $seen[$request->workId] = true;
            $explicit = is_array($choice) && ($choice['selected'] ?? null) === true;

            if (is_array($choice) && array_key_exists('selected', $choice) && ! $choice['selected']) {
                continue;
            }

            // White Box: the finishing stage is off by default, and the visitor can
            // switch any of it back on in the works step (TZ §3).
            if ($level === self::LEVEL_WHITE_BOX && ! $explicit
                && $this->config->workRate($request->workId)?->stage === self::STAGE_FINISH) {
                continue;
            }

            if ($explicit) {
                $request = $request->withMeta(['explicit' => true]);
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
                $kept[] = new WorkRequest($workId, $quantity, suggested: false, meta: ['explicit' => true]);
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
        $priced = [];

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

            $priced[] = ['request' => $request, 'rate' => $rate, 'coefficients' => $coefficients];
        }

        $minimums = $this->minimumShares($priced);
        $lines = [];

        foreach ($priced as $index => $row) {
            $request = $row['request'];
            $rate = $row['rate'];

            $lines[] = EstimateLine::make(
                itemId: $rate->workId,
                lineType: $rate->category->lineType(),
                category: $rate->category,
                name: $rate->name(),
                unit: $rate->unit,
                quantitySource: $rate->quantitySource,
                quantity: $request->quantity,
                unitPrice: $rate->rate,
                coefficients: $row['coefficients'],
                minimumCharge: $minimums[$index],
                precisionClass: $request->precisionClass ?? $rate->precisionClass,
                source: LineSource::AdminRate,
                roomId: $request->roomId,
                roomName: $request->roomName,
                contingencyEligible: $rate->contingencyEligible,
                meta: $request->meta + ['suggested' => $request->suggested]
                    + ($rate->minimumCharge > 0 ? ['minimumChargeOrder' => $rate->minimumCharge] : []),
                stage: $rate->stage,
            );
        }

        return $lines;
    }

    /**
     * The minimum charge is a MINIMUM ORDER ("minimum sifariş"), so it applies once
     * to the whole job — not once per room.
     *
     * A flat with 9 m² of kitchen floor tile and 4 m² of bathroom floor tile is one
     * order of 13 m²: at 15 ₼/m² that is 195 ₼, comfortably over the 150 ₼ minimum.
     * The engine used to bill it as two lines of MAX(135, 150) and MAX(60, 150) =
     * 300 ₼ — 105 ₼ invented out of the fact that the estimate happens to be broken
     * down by room.
     *
     * So the minimum is shared across the lines of one work, in proportion to what
     * each line is actually worth. Two consequences, both wanted:
     *
     *   - when the work's raw total already clears the minimum, no line is lifted;
     *   - when it does not, the lifted lines sum to exactly the minimum, and the
     *     by-room view still shows which room owes which part of it.
     *
     * A work that appears once — the common case — is unaffected: its share is the
     * whole minimum, and the behaviour is what it has always been. The order-wide
     * figure travels on the line as meta.minimumChargeOrder for the admin panel.
     *
     * @param  array<int, array{request: WorkRequest, rate: WorkRate, coefficients: array}>  $priced
     * @return array<int, float> same keys as $priced
     */
    private function minimumShares(array $priced): array
    {
        $rawByWork = [];

        foreach ($priced as $row) {
            if ($row['rate']->minimumCharge <= 0) {
                continue;
            }

            $workId = $row['rate']->workId;
            $rawByWork[$workId] = ($rawByWork[$workId] ?? 0.0) + $this->rawTotal($row);
        }

        $shares = [];

        foreach ($priced as $index => $row) {
            $minimum = $row['rate']->minimumCharge;
            $total = $rawByWork[$row['rate']->workId] ?? 0.0;

            // Nothing to share out: no minimum, or the work has no value at all yet
            // (every quantity zero), in which case EstimateLine keeps the line at 0.
            $shares[$index] = $minimum <= 0 || $total <= 0
                ? $minimum
                : round($minimum * ($this->rawTotal($row) / $total), 2);
        }

        return $shares;
    }

    /** @param array{request: WorkRequest, rate: WorkRate, coefficients: array} $row */
    private function rawTotal(array $row): float
    {
        if ($row['rate']->rate === null) {
            return 0.0;
        }

        $factor = 1.0;

        foreach ($row['coefficients'] as $coefficient) {
            $factor *= $coefficient['value'];
        }

        return max(0.0, $row['request']->quantity) * $row['rate']->rate * $factor;
    }

    /** @param array<int, EstimateLine> $lines */
    private function totals(array $lines): array
    {
        $totals = ['works' => 0.0, 'materials' => 0.0, 'logistics' => 0.0, 'services' => 0.0, 'contingencyBase' => 0.0];

        foreach ($lines as $line) {
            // `services` is what sits outside both stages (the design project); the
            // final cleaning belongs to the last stage and is counted with logistics,
            // so the summary's «Logistika və xidmətlər» row never counts it twice.
            $bucket = match ($line->lineType) {
                LineType::Work => 'works',
                LineType::Material => 'materials',
                LineType::Logistics => 'logistics',
                LineType::Service => $line->stage === null ? 'services' : 'logistics',
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
     * Which stage a line belongs to. A rate's own stage wins; LAST — logistics and
     * the final cleaning — and anything without a stage (a cart product, a work the
     * admin added without one) go to the last stage the visitor ordered. Only the
     * services stay outside both stages.
     */
    private function stageOf(EstimateLine $line, string $lastStage): ?string
    {
        if (in_array($line->stage, [self::STAGE_WHITE_BOX, self::STAGE_FINISH], true)) {
            return $line->stage;
        }

        if ($line->stage === null && $line->lineType === LineType::Service) {
            return null;
        }

        return $lastStage;
    }

    private function marginPercent(): float
    {
        return max(0.0, $this->config->float('CONTRACTOR_MARGIN_PERCENT', 0));
    }

    /**
     * Base, margin and contingency of each stage and of the services block. The job
     * totals are the sums of these parts, so the stage totals always add up to the
     * grand total to the qəpik.
     *
     * @param  array<int, EstimateLine>  $lines
     * @return array<string, array<string, float>>
     */
    private function stageTotals(array $lines): array
    {
        $empty = ['works' => 0.0, 'materials' => 0.0, 'other' => 0.0, 'base' => 0.0, 'eligible' => 0.0];
        $stages = [self::STAGE_WHITE_BOX => $empty, self::STAGE_FINISH => $empty, 'SERVICES' => $empty];

        foreach ($lines as $line) {
            $part = match ($line->lineType) {
                LineType::Work => 'works',
                LineType::Material => 'materials',
                LineType::Logistics, LineType::Service => 'other',
                default => null,
            };

            if ($part === null) {
                continue;
            }

            $key = $line->stage ?? 'SERVICES';
            $stages[$key][$part] += $line->lineTotal;
            $stages[$key]['base'] += $line->lineTotal;

            if ($line->contingencyEligible) {
                $stages[$key]['eligible'] += $line->lineTotal;
            }
        }

        $margin = $this->marginPercent();
        $contingency = max(0.0, $this->config->float('CONTINGENCY_PERCENT', 0));

        foreach ($stages as $key => $stage) {
            $stage = array_map(fn (float $v) => round($v, 2), $stage);
            $stage['margin'] = round($stage['base'] * $margin / 100, 2);
            $stage['contingencyBase'] = round($stage['eligible'] * (1 + $margin / 100), 2);
            $stage['contingency'] = round($stage['contingencyBase'] * $contingency / 100, 2);
            $stage['total'] = round($stage['base'] + $stage['margin'] + $stage['contingency'], 2);
            $stages[$key] = $stage;
        }

        return $stages;
    }

    /** The stage block as the response, the PDF and the WhatsApp text show it. */
    private function publicStage(array $stage): array
    {
        return [
            'works' => $stage['works'],
            'materials' => $stage['materials'],
            'other' => $stage['other'],
            'base' => $stage['base'],
            'margin' => $stage['margin'],
            'contingency' => $stage['contingency'],
            'total' => $stage['total'],
        ];
    }

    /**
     * The contractor's management and profit (TZ §1) — its own row, on everything
     * bought through Archi. It is not the contingency, and the contingency is taken
     * of the base WITH it.
     */
    private function marginLine(float $baseSubtotal, array $stages): ?EstimateLine
    {
        $percent = $this->marginPercent();
        $total = round(array_sum(array_column($stages, 'margin')), 2);

        if ($percent <= 0 || $total <= 0) {
            return null;
        }

        return EstimateLine::make(
            itemId: 'CONTRACTOR_MARGIN',
            lineType: LineType::Contingency,
            category: WorkCategory::Reserve,
            name: __('calculator-detailed.summary.margin'),
            unit: '%',
            quantitySource: 'fixed',
            quantity: 1,
            unitPrice: $total,
            precisionClass: PrecisionClass::Calculated,
            source: LineSource::AdminRate,
            contingencyEligible: false,
            meta: ['percent' => $percent, 'base' => round($baseSubtotal, 2), 'stages' => array_map(fn ($s) => $s['margin'], $stages)],
        );
    }

    /**
     * The financial reserve — a separate row, applied to the eligible subtotal and
     * its margin only. It is not the material waste percentage and must never be
     * applied on top of it a second time (master spec §15).
     */
    private function contingencyLine(array $stages): ?EstimateLine
    {
        $percent = $this->config->float('CONTINGENCY_PERCENT', 0);
        $total = round(array_sum(array_column($stages, 'contingency')), 2);

        if ($percent <= 0 || $total <= 0) {
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
            unitPrice: $total,
            precisionClass: PrecisionClass::Calculated,
            source: LineSource::AdminRate,
            contingencyEligible: false,
            meta: [
                'percent' => $percent,
                'base' => round(array_sum(array_column($stages, 'contingencyBase')), 2),
                'stages' => array_map(fn ($s) => $s['contingency'], $stages),
            ],
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
    private function warnings(array $rooms, array $object, array $derived, array $lines): array
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

        return $warnings;
    }
}
