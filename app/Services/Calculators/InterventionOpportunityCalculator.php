<?php

namespace App\Services\Calculators;

class InterventionOpportunityCalculator
{
    /**
     * Calculate intervention opportunity score (0-100).
     * High hard surface + low vegetation + meaningful area = good opportunity.
     */
    public function calculate(float $vegetation, float $hardSurface, float $acres): float
    {
        $vegetationOpportunity = 100 - min($vegetation, 100);
        $hardSurfaceOpportunity = min($hardSurface, 100);

        // Acreage factor, capped to avoid dominating score
        $acreageOpportunity = min(max($acres / 20 * 100, 0), 100);

        return round(
            ($vegetationOpportunity * 0.35)
            + ($hardSurfaceOpportunity * 0.35)
            + ($acreageOpportunity * 0.30),
            2
        );
    }
}
