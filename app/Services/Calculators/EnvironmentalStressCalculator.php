<?php

namespace App\Services\Calculators;

class EnvironmentalStressCalculator
{
    /**
     * Calculate environmental stress score (0-100).
     * Uses 08:00-18:00 window with array_indices.
     * Heat Index: MAX, Humidity: AVG, Wet Bulb: AVG, Solar: value.
     * Weights: Heat Index 50%, Wet Bulb 25%, Humidity 15%, Solar 10%.
     */
    public function calculate(array $data): float
    {
        $indices = config('park_heat.analysis_window.array_indices');
        $weights = config('park_heat.environmental_weights');

        // Heat Index - MAX over 08:00-18:00
        $heatIndexScore = null;
        if (!empty($data['heat_index_celsius'])) {
            $heatIndexValues = array_intersect_key($data['heat_index_celsius'], array_flip($indices));
            if (!empty($heatIndexValues)) {
                $heatIndexMax = max($heatIndexValues);
                $heatIndexScore = $this->normalize(
                    $heatIndexMax,
                    config('park_heat.environmental.heat_index.low'),
                    config('park_heat.environmental.heat_index.high')
                );
            }
        }

        // Humidity - AVG over 08:00-18:00
        $humidityScore = null;
        if (!empty($data['humidity_percent'])) {
            $humidityValues = array_intersect_key($data['humidity_percent'], array_flip($indices));
            if (!empty($humidityValues)) {
                $humidityAvg = array_sum($humidityValues) / count($humidityValues);
                $humidityScore = $this->normalize(
                    $humidityAvg,
                    config('park_heat.environmental.humidity.low'),
                    config('park_heat.environmental.humidity.high')
                );
            }
        }

        // Wet Bulb - AVG over 08:00-18:00
        $wetBulbScore = null;
        if (!empty($data['wet_bulb_celsius'])) {
            $wetBulbValues = array_intersect_key($data['wet_bulb_celsius'], array_flip($indices));
            if (!empty($wetBulbValues)) {
                $wetBulbAvg = array_sum($wetBulbValues) / count($wetBulbValues);
                $wetBulbScore = $this->normalize(
                    $wetBulbAvg,
                    config('park_heat.environmental.wet_bulb.low'),
                    config('park_heat.environmental.wet_bulb.high')
                );
            }
        }

        // Solar GHI - single value
        $solarScore = null;
        if (!empty($data['solar_ghi'])) {
            $solarScore = $this->normalize(
                $data['solar_ghi'],
                config('park_heat.environmental.solar_irradiance.low'),
                config('park_heat.environmental.solar_irradiance.high')
            );
        }

        // Calculate weighted average, excluding null values
        $validScores = [];
        $validWeights = [];

        if ($heatIndexScore !== null) {
            $validScores[] = $heatIndexScore;
            $validWeights[] = $weights['heat_index'];
        }
        if ($wetBulbScore !== null) {
            $validScores[] = $wetBulbScore;
            $validWeights[] = $weights['wet_bulb'];
        }
        if ($humidityScore !== null) {
            $validScores[] = $humidityScore;
            $validWeights[] = $weights['humidity'];
        }
        if ($solarScore !== null) {
            $validScores[] = $solarScore;
            $validWeights[] = $weights['solar_ghi'];
        }

        if (empty($validScores)) {
            return 0.0; // No data available
        }

        // Normalize weights to sum to 1.0
        $totalWeight = array_sum($validWeights);
        $normalizedWeights = array_map(fn($w) => $w / $totalWeight, $validWeights);

        // Calculate weighted average
        $score = 0;
        foreach ($validScores as $index => $value) {
            $score += $value * $normalizedWeights[$index];
        }

        return round($score, 2);
    }

    /**
     * Normalize value to 0-100 range.
     */
    private function normalize(float $value, float $min, float $max): float
    {
        if ($max <= $min) {
            return 0;
        }

        $score = (($value - $min) / ($max - $min)) * 100;

        return round(max(0, min(100, $score)), 2);
    }
}
