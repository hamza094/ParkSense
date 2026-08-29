<?php

namespace App\Services;

use App\Models\Park;
use App\Models\ParkHeatAnalysis;
use App\Models\ParkPriorityScore;
use App\Models\EnvironmentalMetric;
use App\Models\SatelliteMetric;
use App\Services\Calculators\HeatSeverityCalculator;
use App\Services\Calculators\EnvironmentalStressCalculator;
use App\Services\Calculators\PhysicalConditionCalculator;
use App\Services\Calculators\ParkImportanceCalculator;
use App\Services\Calculators\InterventionOpportunityCalculator;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ParkPriorityScoreService
{
    public function __construct(
        private HeatSeverityCalculator $heatSeverityCalculator,
        private EnvironmentalStressCalculator $environmentalStressCalculator,
        private PhysicalConditionCalculator $physicalConditionCalculator,
        private ParkImportanceCalculator $parkImportanceCalculator,
        private InterventionOpportunityCalculator $interventionOpportunityCalculator,
    ) {}

    /**
     * Calculate priority scores for all parks belonging to one heatmap analysis.
     * Only processes parks with complete environmental and satellite data.
     */
    public function calculateForAnalysis(int $heatmapAnalysisId): Collection
    {
        // Check if priority scores already exist for this heatmap
        $existingScores = ParkPriorityScore::query()
            ->where('heatmap_analysis_id', $heatmapAnalysisId)
            ->exists();

        if ($existingScores) {
            throw new \Exception('Priority scores already exist for this heatmap. Data is available.');
        }

        $heatAnalyses = ParkHeatAnalysis::query()
            ->with(['park'])
            ->where('heatmap_analysis_id', $heatmapAnalysisId)
            ->get();

        if ($heatAnalyses->isEmpty()) {
            throw new InvalidArgumentException(
                'No park heat analyses found for this heatmap analysis.'
            );
        }

        $results = collect();

        foreach ($heatAnalyses as $heatAnalysis) {
            // Only process parks with complete environmental and satellite data
            $environmental = EnvironmentalMetric::query()
                ->where('park_id', $heatAnalysis->park_id)
                ->where('heatmap_analysis_id', $heatmapAnalysisId)
                ->where('status', 'completed')
                ->latest('id')
                ->first();

            $satellite = SatelliteMetric::query()
                ->where('park_id', $heatAnalysis->park_id)
                ->where('heatmap_analysis_id', $heatmapAnalysisId)
                ->where('status', 'completed')
                ->latest('id')
                ->first();

            // Skip parks without complete data
            if (!$environmental || !$satellite) {
                continue;
            }

            $scores = $this->calculateScores(
                parkHeat: $heatAnalysis,
                park: $heatAnalysis->park,
                environmental: $environmental,
                satellite: $satellite,
            );

            $priorityScore = $this->calculateWeightedScore($scores);

            $priority = ParkPriorityScore::updateOrCreate(
                [
                    'park_id' => $heatAnalysis->park_id,
                    'heatmap_analysis_id' => $heatmapAnalysisId,
                ],
                [
                    'park_heat_analysis_id' => $heatAnalysis->id,
                    'environmental_metric_id' => $environmental->id,
                    'satellite_metric_id' => $satellite->id,
                    'heat_severity' => $scores['heat_severity'],
                    'environmental_stress' => $scores['environmental_stress'],
                    'physical_condition' => $scores['physical_condition'],
                    'park_importance' => $scores['park_importance'],
                    'intervention_opportunity' => $scores['intervention_opportunity'],
                    'priority_score' => $priorityScore,
                    'calculation_data' => $scores['evidence'],
                    'model_version' => config('park_heat.model_version'),
                ]
            );

            $results->push($priority);
        }

        return $results->sortByDesc('priority_score')->values();
    }

    /**
     * Calculate the five component scores.
     * Assumes environmental and satellite data are available (filtered in calculateForAnalysis).
     */
    private function calculateScores(
        ParkHeatAnalysis $parkHeat,
        Park $park,
        EnvironmentalMetric $environmental,
        SatelliteMetric $satellite,
    ): array {
        $heatSeverity = $this->heatSeverityCalculator->calculate(
            (float) $parkHeat->average_temperature
        );

        $environmentalData = $this->extractEnvironmentalData($environmental);
        $environmentalStress = $this->environmentalStressCalculator->calculate($environmentalData);

        $physicalData = $this->extractPhysicalData($satellite);
        $physicalResult = $this->physicalConditionCalculator->calculate($physicalData['segments']);
        $physicalCondition = $physicalResult['score'];

        $parkImportance = $this->parkImportanceCalculator->calculate($park);

        $interventionOpportunity = $this->interventionOpportunityCalculator->calculate(
            $physicalData['vegetation_percent'],
            $physicalData['hard_surface_percent'],
            (float) ($park->acres ?? 0)
        );

        return [
            'heat_severity' => $heatSeverity,
            'environmental_stress' => $environmentalStress,
            'physical_condition' => $physicalCondition,
            'park_importance' => $parkImportance,
            'intervention_opportunity' => $interventionOpportunity,
            'evidence' => [
                'heat' => [
                    'average_temperature' => $parkHeat->average_temperature,
                    'normalized_score' => $heatSeverity,
                ],
                'environmental' => [
                    ...$environmentalData,
                    'normalized_score' => $environmentalStress,
                ],
                'physical' => [
                    ...$physicalData,
                    'vegetation_data_available' => $physicalResult['vegetation_data_available'],
                    'normalized_score' => $physicalCondition,
                ],
                'park_importance' => [
                    'park_type' => $park->park_type,
                    'acres' => $park->acres,
                    'playground' => (bool) $park->playground,
                    'splash_pads' => (bool) $park->splash_pads,
                    'swimming_pool' => (bool) $park->swimming_pool,
                    'sports_complex' => (bool) $park->sports_complex,
                    'recreation_community_center' => (bool) $park->recreation_community_center,
                    'shade_structures' => (bool) $park->shade_structures,
                    'score' => $parkImportance,
                ],
                'intervention_opportunity' => [
                    'vegetation_percent' => $physicalData['vegetation_percent'],
                    'hard_surface_percent' => $physicalData['hard_surface_percent'],
                    'acres' => $park->acres,
                    'score' => $interventionOpportunity,
                ],
            ],
        ];
    }

    /**
     * Final weighted score.
     * All components are guaranteed to have data due to filtering in calculateForAnalysis.
     */
    private function calculateWeightedScore(array $scores): float
    {
        $weights = config('park_heat.priority_weights');

        $score =
            ($scores['heat_severity'] * $weights['heat_severity'])
            + ($scores['environmental_stress'] * $weights['environmental_stress'])
            + ($scores['physical_condition'] * $weights['physical_condition'])
            + ($scores['park_importance'] * $weights['park_importance'])
            + ($scores['intervention_opportunity'] * $weights['intervention_opportunity']);

        return round(max(0, min(100, $score)), 2);
    }

    /**
     * Extract environmental data from EnvironmentalMetric.
     */
    private function extractEnvironmentalData(?EnvironmentalMetric $environmental): array
    {
        $data = $environmental?->data ?? [];
        $params = $data['locations'][0]['parameters'] ?? [];

        return [
            'heat_index_celsius' => $params['heat_index_celsius'] ?? [],
            'humidity_percent' => $params['relative_humidity_percent'] ?? [],
            'wet_bulb_celsius' => $params['wet_bulb_temperature_celsius'] ?? [],
            'solar_ghi' => $data['locations'][0]['solar_irradiance']['clear_sky']['ghi'] ?? null,
        ];
    }

    /**
     * Extract physical data from SatelliteMetric.
     */
    private function extractPhysicalData(?SatelliteMetric $satellite): array
    {
        $segments = $satellite?->data['segmentation']['segments'] ?? [];

        $vegetationClasses = config('park_heat.satellite.vegetation_classes');
        $hardSurfaceClasses = config('park_heat.satellite.hard_surface_classes');

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

        return [
            'segments' => $segments,
            'vegetation_percent' => $vegetation,
            'hard_surface_percent' => $hardSurface,
        ];
    }
}
