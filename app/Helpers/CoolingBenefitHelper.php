<?php

namespace App\Helpers;

class CoolingBenefitHelper
{
    /**
     * Get cooling benefit evidence for an intervention type.
     * 
     * @param string $interventionKey The intervention key (tree_planting, ramada, cool_pavement)
     * @return array|null Cooling benefit evidence from Phoenix research
     */
    public static function getCoolingBenefit(string $interventionKey): ?array
    {
        return config("cooling_benefits.{$interventionKey}");
    }
    
    /**
     * Format evidence type for display.
     * 
     * @param string $evidenceType The evidence type (research_reference, measured_reference)
     * @return string Formatted evidence type
     */
    public static function formatEvidenceType(string $evidenceType): string
    {
        return match($evidenceType) {
            'research_reference' => 'Research Reference',
            'measured_reference' => 'Measured Reference',
            'planning_assumption' => 'Planning Assumption',
            default => 'Unknown',
        };
    }
    
    /**
     * Get CSS class for evidence type.
     * 
     * @param string $evidenceType The evidence type
     * @return string CSS class name
     */
    public static function getEvidenceClass(string $evidenceType): string
    {
        return match($evidenceType) {
            'research_reference' => 'evidence-research',
            'measured_reference' => 'evidence-measured',
            'planning_assumption' => 'evidence-planning',
            default => 'evidence-unknown',
        };
    }
}