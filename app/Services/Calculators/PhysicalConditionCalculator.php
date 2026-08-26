<?php

namespace App\Services\Calculators;

class PhysicalConditionCalculator
{
    /**
     * Calculate physical condition score (0-100).
     * Uses explicit satellite class buckets.
     * Capped at 100.
     */
    public function calculate(array $segments): array
    {
        $vegetationClasses = config('park_heat.satellite.vegetation_classes');
        $hardSurfaceClasses = config('park_heat.satellite.hard_surface_classes');

        // Explicit vegetation bucket
        $vegetation = 0;
        foreach ($segments as $class => $percentage) {
            $classLower = strtolower($class);
            foreach ($vegetationClasses as $vegClass) {
                if (str_contains($classLower, strtolower($vegClass))) {
                    $vegetation += (float) $percentage;
                    break;
                }
            }
        }

        // Explicit hard-surface bucket
        $hardSurface = 0;
        foreach ($segments as $class => $percentage) {
            $classLower = strtolower($class);
            foreach ($hardSurfaceClasses as $surfaceClass) {
                if (str_contains($classLower, strtolower($surfaceClass))) {
                    $hardSurface += (float) $percentage;
                    break;
                }
            }
        }

        $vegetationDataAvailable = $vegetation > 0;

        // If no vegetation classes, use hard surface only (no earth/ground proxy)
        if (!$vegetationDataAvailable) {
            $score = round($hardSurface, 2);
        } else {
            $vegetationDeficit = max(0, 100 - $vegetation);
            $score = round(($vegetationDeficit * 0.5) + ($hardSurface * 0.5), 2);
        }

        // Cap at 100
        $score = min(100, $score);

        return [
            'score' => $score,
            'vegetation_data_available' => $vegetationDataAvailable,
            'vegetation_percent' => $vegetation,
            'hard_surface_percent' => $hardSurface,
        ];
    }
}
