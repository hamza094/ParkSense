<?php

namespace App\Services\Calculators;

use App\Models\Park;

class ParkImportanceCalculator
{
    /**
     * Calculate park importance score (0-100).
     * Based on park type and facilities.
     */
    public function calculate(Park $park): float
    {
        $score = config("park_heat.park_type_scores.{$park->park_type}", 5);

        $facilityScores = config('park_heat.facility_scores');

        if ($park->playground) {
            $score += $facilityScores['playground'];
        }
        if ($park->splash_pads) {
            $score += $facilityScores['splash_pads'];
        }
        if ($park->swimming_pool) {
            $score += $facilityScores['swimming_pool'];
        }
        if ($park->sports_complex) {
            $score += $facilityScores['sports_complex'];
        }
        if ($park->recreation_community_center) {
            $score += $facilityScores['recreation_community_center'];
        }
        if ($park->shade_structures) {
            $score += $facilityScores['shade_structures'];
        }

        return round(min($score, 100), 2);
    }
}
