<?php

namespace App\Services\Calculators;

class HeatSeverityCalculator
{
    /**
     * Calculate heat severity score (0-100).
     * Uses absolute thresholds from config to avoid micro-variation amplification.
     * Parks with similar temperatures receive similar scores.
     */
    public function calculate(float $temperature): float
    {
        $low = config('park_heat.temperature.low', 25.0);
        $high = config('park_heat.temperature.high', 45.0);

        return $this->normalize($temperature, $low, $high);
    }

    /**
     * Normalize value to 0-100 range using absolute thresholds.
     */
    private function normalize(float $value, float $min, float $max): float
    {
        $score = (($value - $min) / ($max - $min)) * 100;

        return round(max(0, min(100, $score)), 2);
    }
}
