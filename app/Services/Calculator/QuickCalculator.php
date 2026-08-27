<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\PriceSegment;
use App\Enums\Calculator\RenovationType;

/**
 * The quick calculator of the TZ: three inputs, one multiplication, no server round
 * trip per keystroke.
 *
 *     TotalPrice = Area × PricePerM2
 *
 * The six prices and every limit come from the database, so the same six numbers the
 * admin edits are the ones the browser multiplies (TZ §30, §35, §43). This class is
 * the authority: the frontend receives the matrix once and repeats the same
 * arithmetic locally, and the tests assert both against the acceptance criteria.
 */
class QuickCalculator
{
    public function __construct(private readonly CalculatorConfig $config) {}

    public function minArea(): float
    {
        return $this->config->float('QUICK_MIN_AREA', 10);
    }

    public function maxArea(): float
    {
        return $this->config->float('QUICK_MAX_AREA', 10000);
    }

    public function defaultArea(): float
    {
        return $this->config->float('QUICK_DEFAULT_AREA', 80);
    }

    public function defaultRenovationType(): RenovationType
    {
        return RenovationType::fromSlug($this->config->setting('QUICK_DEFAULT_TYPE', 'WHITE_BOX'))
            ?? RenovationType::WhiteBox;
    }

    public function defaultSegment(): PriceSegment
    {
        return PriceSegment::fromSlug($this->config->setting('QUICK_DEFAULT_SEGMENT', 'STANDARD'))
            ?? PriceSegment::Standard;
    }

    public function pricePerM2(RenovationType $type, PriceSegment $segment): ?float
    {
        return $this->config->quickRates()[$type->value][$segment->value] ?? null;
    }

    /**
     * @return array{
     *     valid: bool, error: ?string, area: float, pricePerM2: ?float, total: ?float,
     *     renovationType: string, segment: string
     * }
     */
    public function calculate(mixed $area, RenovationType $type, PriceSegment $segment): array
    {
        $result = [
            'valid' => false,
            'error' => null,
            'area' => 0.0,
            'pricePerM2' => null,
            'total' => null,
            'renovationType' => $type->value,
            'segment' => $segment->value,
        ];

        if ($area === null || $area === '' || ! is_numeric($area)) {
            return array_merge($result, ['error' => 'AREA_REQUIRED']);
        }

        $area = (float) $area;

        if ($area <= 0) {
            return array_merge($result, ['error' => 'AREA_MUST_BE_POSITIVE']);
        }

        if ($area < $this->minArea()) {
            return array_merge($result, ['area' => $area, 'error' => 'AREA_BELOW_MINIMUM']);
        }

        if ($area > $this->maxArea()) {
            // Deliberately not a silent clamp: a 20 000 m² object is a conversation
            // with a manager, not a number the quick calculator should invent.
            return array_merge($result, ['area' => $area, 'error' => 'AREA_ABOVE_MAXIMUM']);
        }

        $price = $this->pricePerM2($type, $segment);

        if ($price === null) {
            // No approved rate for this combination — say so, never show 0 or NaN
            // (master spec audit §2.3).
            return array_merge($result, ['area' => $area, 'error' => 'PRICE_NOT_AVAILABLE']);
        }

        return [
            'valid' => true,
            'error' => null,
            'area' => $area,
            'pricePerM2' => $price,
            'total' => round($area * $price, 2),
            'renovationType' => $type->value,
            'segment' => $segment->value,
        ];
    }

    /**
     * Everything the frontend needs to run the calculator without another request:
     * the matrix, the limits, the defaults and the CTA target (TZ §36, §43, §47).
     */
    public function payload(): array
    {
        $matrix = [];

        foreach (RenovationType::cases() as $type) {
            foreach (PriceSegment::cases() as $segment) {
                $matrix[$type->value][$segment->value] = $this->pricePerM2($type, $segment);
            }
        }

        return [
            'currency' => 'AZN',
            'prices' => $matrix,
            'limits' => [
                'minArea' => $this->minArea(),
                'maxArea' => $this->maxArea(),
            ],
            'defaults' => [
                'area' => $this->defaultArea(),
                'renovationType' => $this->defaultRenovationType()->value,
                'segment' => $this->defaultSegment()->value,
            ],
            'showFromPrefix' => $this->config->bool('SHOW_FROM_PREFIX', true),
            'detailedCalculatorUrl' => (string) $this->config->setting('DETAILED_CALCULATOR_URL', route('calculator.detailed', absolute: false)),
            'configVersion' => $this->config->configVersion(),
        ];
    }

    /**
     * Hand the visitor's three answers to the detailed calculator so nothing has to
     * be entered twice (TZ §47).
     */
    public function handoffUrl(float $area, RenovationType $type, PriceSegment $segment): string
    {
        $base = (string) $this->config->setting('DETAILED_CALCULATOR_URL', route('calculator.detailed', absolute: false));

        return $base.'?'.http_build_query([
            'area' => $area,
            'type' => $type->slug(),
            'segment' => $segment->slug(),
        ]);
    }
}
