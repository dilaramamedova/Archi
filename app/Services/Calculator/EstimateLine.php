<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\LineSource;
use App\Enums\Calculator\LineType;
use App\Enums\Calculator\PrecisionClass;
use App\Enums\Calculator\WorkCategory;

/**
 * The one and only priced row of the system (master spec §5).
 *
 * Every figure a client ever sees — a 1 472 AZN plaster row, a 21-pack SPC row, a
 * VRF estimate, the delivery trips, the contingency — is an instance of this class.
 * There is exactly one formula:
 *
 *     lineTotal = MAX(quantity × unitPrice × Π(coefficients), minimumCharge)
 *
 * and one aggregation rule: sum the lines, group them by category or by room. A work
 * whose rate is not configured yet produces a line with source = PRICE_MISSING and a
 * zero total that is reported separately, so a missing rate can never masquerade as
 * free work (master spec §19, §24).
 */
class EstimateLine
{
    /** @param array<int, array{id: string, value: float}> $coefficients */
    public function __construct(
        public readonly string $itemId,
        public readonly LineType $lineType,
        public readonly WorkCategory $category,
        public readonly string $name,
        public readonly string $unit,
        public readonly string $quantitySource,
        public readonly float $quantity,
        public readonly ?float $unitPrice,
        public readonly array $coefficients,
        public readonly float $minimumCharge,
        public readonly PrecisionClass $precisionClass,
        public readonly LineSource $source,
        public readonly float $lineTotal,
        public readonly ?string $roomId = null,
        public readonly ?string $roomName = null,
        public readonly bool $contingencyEligible = true,
        public readonly array $meta = [],
    ) {}

    /**
     * Build a line from its inputs and apply the single formula. Rounding is to
     * 0.01 AZN per line (master spec audit §2.4) — the UI may round the grand total
     * to whole manats, the arithmetic underneath never does.
     *
     * @param array<int, array{id: string, value: float}> $coefficients
     */
    public static function make(
        string $itemId,
        LineType $lineType,
        WorkCategory $category,
        string $name,
        string $unit,
        string $quantitySource,
        float $quantity,
        ?float $unitPrice,
        array $coefficients = [],
        float $minimumCharge = 0.0,
        PrecisionClass $precisionClass = PrecisionClass::Calculated,
        LineSource $source = LineSource::AdminRate,
        ?string $roomId = null,
        ?string $roomName = null,
        bool $contingencyEligible = true,
        array $meta = [],
    ): self {
        $quantity = max(0.0, $quantity);

        if ($unitPrice === null) {
            // No rate configured. The line still exists so the client and the sales
            // manager both see the work is in scope, but it carries no money.
            return new self(
                $itemId, $lineType, $category, $name, $unit, $quantitySource,
                $quantity, null, $coefficients, $minimumCharge,
                PrecisionClass::Estimated, LineSource::PriceMissing, 0.0,
                $roomId, $roomName, $contingencyEligible, $meta,
            );
        }

        $factor = 1.0;

        foreach ($coefficients as $coefficient) {
            $factor *= $coefficient['value'];
        }

        $raw = $quantity * $unitPrice * $factor;

        // The minimum charge only bites when there is something to charge for: an
        // unselected work with quantity 0 must stay at 0, not jump to its minimum.
        $total = $quantity > 0 ? max($raw, $minimumCharge) : 0.0;

        return new self(
            $itemId, $lineType, $category, $name, $unit, $quantitySource,
            $quantity, $unitPrice, $coefficients, $minimumCharge,
            $precisionClass, $source, round($total, 2),
            $roomId, $roomName, $contingencyEligible, $meta,
        );
    }

    public function isPriceMissing(): bool
    {
        return $this->source === LineSource::PriceMissing;
    }

    /**
     * The one-line explanation printed under a row: "184 m² × 8 ₼/m² = 1 472 ₼".
     *
     * Every figure in it is the figure that was actually multiplied, and the result
     * is the line's own total — a client who checks the arithmetic on a phone must
     * get the same answer. Qəpik appear only where they exist (TZ §19), so a whole
     * sum reads "2 500 ₼" and a real remainder reads "474.34 ₼".
     */
    public function shortTrace(): string
    {
        // A percentage line has no quantity worth printing; what the reader wants to
        // see is which subtotal the percentage was taken of.
        if (isset($this->meta['percent'], $this->meta['base'])) {
            return self::number($this->meta['percent']).'% × '.self::money($this->meta['base'])
                .' = '.self::money($this->lineTotal);
        }

        $quantity = self::number($this->quantity).' '.$this->unit;

        if ($this->unitPrice === null) {
            return $quantity;
        }

        // The unit price carries its unit, so "8 ₼" can never be misread as the price
        // of the whole job.
        $rate = self::money($this->unitPrice).'/'.$this->unit;

        // A coefficient changed the result, so it has to appear or the multiplication
        // visibly does not add up.
        $factors = '';

        foreach ($this->coefficients as $coefficient) {
            $factors .= ' × '.self::number($coefficient['value']);
        }

        $factor = 1.0;

        foreach ($this->coefficients as $coefficient) {
            $factor *= $coefficient['value'];
        }

        $raw = round($this->quantity * $this->unitPrice * $factor, 2);

        // When the minimum charge won, the multiplication above is NOT the answer.
        // Printing it as if it were is how an estimate loses its credibility: the
        // client checks 4 × 9 on a phone, gets 36, and sees 150 on the page.
        if ($this->lineTotal > 0 && $raw < $this->lineTotal - 0.005) {
            return $quantity.' × '.$rate.$factors.' = '.self::money($raw)
                .' → '.__('calculator-detailed.notice.minimum_charge').' '.self::money($this->lineTotal);
        }

        return $quantity.' × '.$rate.$factors.' = '.self::money($this->lineTotal);
    }

    /** Thousands separated, qəpik only when there are any. */
    private static function money(float $value): string
    {
        return self::number($value).' ₼';
    }

    private static function number(float $value): string
    {
        $formatted = number_format($value, 2, '.', ' ');

        return str_ends_with($formatted, '.00') ? substr($formatted, 0, -3) : $formatted;
    }

    /**
     * Full calculation trace (master spec §5.2). Stored on snapshots and returned to
     * the frontend so any total can be traced back to quantity, price, coefficients
     * and source without re-running the engine.
     */
    public function toArray(): array
    {
        return [
            'itemId' => $this->itemId,
            'lineType' => $this->lineType->value,
            'category' => $this->category->value,
            'name' => $this->name,
            'unit' => $this->unit,
            'quantitySource' => $this->quantitySource,
            'quantity' => round($this->quantity, 4),
            'unitPrice' => $this->unitPrice === null ? null : round($this->unitPrice, 4),
            'coefficients' => $this->coefficients,
            'minimumCharge' => $this->minimumCharge,
            'precisionClass' => $this->precisionClass->value,
            'source' => $this->source->value,
            'lineTotal' => $this->lineTotal,
            'roomId' => $this->roomId,
            'roomName' => $this->roomName,
            'contingencyEligible' => $this->contingencyEligible,
            'trace' => $this->shortTrace(),
            'meta' => $this->meta,
        ];
    }
}
