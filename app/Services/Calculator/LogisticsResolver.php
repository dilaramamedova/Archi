<?php

namespace App\Services\Calculator;

use App\Models\Product;

/**
 * Delivery, manual lifting and external access (master spec §14).
 *
 * The one rule that matters here: missing product weight or volume never becomes
 * 0 AZN. If the catalog does not know what the materials weigh, the logistics lines
 * are flagged incomplete and the frontend says so, instead of quietly quoting free
 * delivery for two tonnes of screed.
 */
class LogisticsResolver
{
    public function __construct(private readonly CalculatorConfig $config) {}

    /**
     * @param  array<int, EstimateLine>  $materialLines
     * @param  array<int, Product>  $products
     * @return array{requests: array<int, WorkRequest>, meta: array<string, mixed>}
     */
    public function resolve(array $materialLines, array $products, array $object, array $input): array
    {
        $weight = 0.0;
        $volume = 0.0;
        $unknown = 0;

        foreach ($materialLines as $line) {
            $productId = $line->meta['productId'] ?? null;
            $product = $productId ? ($products[(int) $productId] ?? null) : null;

            if (! $product) {
                // A fallback-priced category has no physical product behind it yet.
                $unknown++;

                continue;
            }

            $lineWeight = (float) ($product->weight_kg ?? 0);
            $lineVolume = (float) ($product->volume_m3 ?? 0);

            if ($lineWeight <= 0 && $lineVolume <= 0) {
                $unknown++;

                continue;
            }

            $weight += $lineWeight * $line->quantity;
            $volume += $lineVolume * $line->quantity;
        }

        $meta = [
            'totalWeightKg' => round($weight, 2),
            'totalVolumeM3' => round($volume, 3),
            'productsWithoutLogisticsData' => $unknown,
            'complete' => $unknown === 0 && ($weight > 0 || $volume > 0),
        ];

        $requests = [];

        $weightCapacity = max(0.1, $this->config->float('DELIVERY_VEHICLE_WEIGHT_CAPACITY', 1500));
        $volumeCapacity = max(0.1, $this->config->float('DELIVERY_VEHICLE_VOLUME_CAPACITY', 12));

        if ($weight > 0 || $volume > 0) {
            $trips = (int) ceil(max($weight / $weightCapacity, $volume / $volumeCapacity));
            $trips = max(1, $trips);

            $requests[] = new WorkRequest(
                workId: 'LOG_DELIVERY_TRIP',
                quantity: $trips,
                meta: ['weightKg' => round($weight, 2), 'volumeM3' => round($volume, 3), 'trips' => $trips],
            );

            $manualLift = $this->manualLift($weight, $object);

            if ($manualLift) {
                $requests[] = $manualLift;
            }
        }

        // External access is asked here, in the works step, and only when the object
        // actually needs it — not as a stray toggle on the object screen (audit §2.2).
        $platformDays = (float) ($input['platformDays'] ?? 0);

        if (($input['externalAccess'] ?? false) && $platformDays > 0) {
            $requests[] = new WorkRequest('LOG_PLATFORM_DAY', $platformDays);
        }

        if (($scaffold = (float) ($input['scaffoldQty'] ?? 0)) > 0) {
            $requests[] = new WorkRequest('LOG_SCAFFOLD', $scaffold);
        }

        return ['requests' => $requests, 'meta' => $meta];
    }

    /**
     * Manual lifting is only charged when there is no freight lift and the floor is
     * above the free threshold — and only when the weight is actually known.
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
            meta: ['weightKg' => round($weight, 2), 'chargeableFloors' => $chargeableFloors, 'freeFloors' => $free],
        );
    }
}
