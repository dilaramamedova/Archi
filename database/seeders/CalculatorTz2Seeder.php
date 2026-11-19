<?php

namespace Database\Seeders;

/**
 * Calculator TZ №2 of 19.11.2026 — the track system (lights, rail, feed points),
 * LED outputs, the track norms and the BALCONY / WARDROBE room types
 * (database/seeders/data/calculator_tz_2026_11_19.php).
 *
 * Same two modes as CalculatorTzSeeder: the production migration (2026_11_19_110100)
 * only adds what is missing; CalculatorSeeder writes everything.
 */
class CalculatorTz2Seeder extends CalculatorTzSeeder
{
    protected string $dataFile = 'calculator_tz_2026_11_19.php';
}
