<?php

namespace App\Services\Calculator;

use App\Enums\Calculator\LineSource;

/**
 * Delivery and manual lifting (master spec §14), estimated from the object.
 *
 * The catalog rarely knows what a product weighs, and a fallback-priced material
 * has no product at all — so weighing the basket produced "0 ₼, cannot be
 * calculated" on almost every estimate, which is an underestimate dressed as
 * honesty. The delivery is now a norm: one trip per DELIVERY_AREA_PER_TRIP square
 * metres, and MATERIAL_KG_PER_M2 of material to carry up when there is no lift.
 * Both numbers are the admin's.
 *
 * Nothing is delivered when the visitor buys every material themselves.
 */
class LogisticsResolver
{
    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, EstimateLine>  $materialLines
     * @return array{requests: array<int, WorkRequest>, meta: array<string, mixed>}
     */
    public function resolve(array $materialLines, array $object, array $derived): array
    {
        $supplied = array_filter(
            $materialLines,
            fn (EstimateLine $line) => $line->source !== LineSource::ManualQuote && $line->quantity > 0,
        );

        $area = (float) ($object['area'] ?? 0) ?: (float) ($derived['TOTAL_FLOOR_AREA'] ?? 0);

        if ($supplied === [] || $area <= 0) {
            return ['requests' => [], 'meta' => ['trips' => 0, 'estimatedWeightKg' => 0.0, 'complete' => true]];
        }

        $perTrip = max(1.0, $this->config->float('DELIVERY_AREA_PER_TRIP', 40));
        $trips = max(1, (int) ceil($area / $perTrip));
        $weight = round($area * $this->config->float('MATERIAL_KG_PER_M2', 25), 2);

        $requests = [
            new WorkRequest(
                workId: 'LOG_DELIVERY_TRIP',
                quantity: $trips,
                meta: ['area' => $area, 'areaPerTrip' => $perTrip],
            ),
        ];

        if ($lift = $this->manualLift($weight, $object)) {
            $requests[] = $lift;
        }

        return [
            'requests' => $requests,
            'meta' => ['trips' => $trips, 'estimatedWeightKg' => $weight, 'complete' => true],
        ];
    }

    /**
     * Manual lifting is only charged when there is no freight lift and the floor is
     * above the free threshold.
     */
    private function manualLift(float $weight, array $object): ?WorkRequest
    {
        if ($weight <= 0 || ($object['hasFreightElevator'] ?? false)) {
            return null;
        }

        $floor = (int) ($object['floor'] ?? 0);
        $free = $this->config->int('FREE_LIFT_FLOORS', 1);
        $chargeableFloors = max($floor - $free, 0);

        if ($chargeableFloors <= 0) {
            return null;
        }

        return new WorkRequest(
            workId: 'LOG_MANUAL_LIFT',
            quantity: round($weight * $chargeableFloors, 2),
            meta: ['weightKg' => $weight, 'chargeableFloors' => $chargeableFloors, 'freeFloors' => $free],
        );
    }
}
