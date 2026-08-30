<?php

namespace App\Services;

use App\Helpers\CoolingBenefitHelper;
use App\Models\InterventionRecommendation;
use App\Models\ParkPriorityScore;
use Illuminate\Support\Collection;

class InterventionSelectionService
{
    public function recommendForAnalysis(
        int $heatmapAnalysisId
    ): Collection {

        // Check if intervention recommendations already exist for this heatmap
        $existingRecommendations = InterventionRecommendation::query()
            ->where('heatmap_analysis_id', $heatmapAnalysisId)
            ->exists();

        if ($existingRecommendations) {
            throw new \Exception('Intervention recommendations already exist for this heatmap. Data is available.');
        }

        /*
         * Only the Top 5 parks need intervention recommendations.
         */
        $priorityScores = ParkPriorityScore::query()
            ->with([
                'park',
                'parkHeatAnalysis',
                'environmentalMetric',
                'satelliteMetric',
            ])
            ->where('heatmap_analysis_id', $heatmapAnalysisId)
            ->orderByDesc('priority_score')
            ->limit(5)
            ->get();

        $recommendations = collect();

        foreach ($priorityScores as $score) {

            $parkRecommendations = $this->recommendForPark($score);

            foreach ($parkRecommendations as $recommendation) {
                $recommendations->push($recommendation);
            }
        }

        return $recommendations;
    }

    private function recommendForPark(
        ParkPriorityScore $score
    ): Collection {

        $park = $score->park;

        /*
         * Pull the physical metrics created in Phase 8.
         */
        $vegetation = (float) (
            $score->calculation_data['physical']['vegetation_percent'] 
            ?? $score->satelliteMetric?->data['calculation']['vegetation_percent'] 
            ?? 0
        );

        $hardSurface = (float) (
            $score->calculation_data['physical']['hard_surface_percent'] 
            ?? $score->satelliteMetric?->data['calculation']['hard_surface_percent'] 
            ?? 0
        );

        $recommendations = collect();
        $eligibleInterventions = [];

        /*
         * Evaluate all intervention rules and collect eligible ones with their priority
         */
        $rules = config('park_heat.interventions.rules');

        // Tree planting rule
        $treeRule = $rules['tree_planting'];
        if ($this->evaluateRuleConditions($treeRule['when'], [
            'heat_severity' => $score->heat_severity,
            'vegetation_percent' => $vegetation,
        ])) {
            $eligibleInterventions[] = [
                'key' => 'tree_planting',
                'priority' => $treeRule['priority'],
                'rule' => 'low_vegetation_high_heat',
            ];
        }

        // Cool pavement rule
        $pavementRule = $rules['cool_pavement'];
        if ($this->evaluateRuleConditions($pavementRule['when'], [
            'heat_severity' => $score->heat_severity,
            'hard_surface_percent' => $hardSurface,
        ])) {
            $eligibleInterventions[] = [
                'key' => 'cool_pavement',
                'priority' => $pavementRule['priority'],
                'rule' => 'high_hard_surface',
            ];
        }

        // Ramada rule
        $ramadaRule = $rules['ramada'];
        if ($this->evaluateRuleConditions($ramadaRule['when'], [
            'heat_severity' => $score->heat_severity,
            'playground' => $park->playground,
            'shade_structures' => $park->shade_structures,
        ])) {
            $eligibleInterventions[] = [
                'key' => 'ramada',
                'priority' => $ramadaRule['priority'],
                'rule' => 'playground_without_shade',
            ];
        }

        /*
         * Sort eligible interventions by priority (higher priority first)
         */
        usort($eligibleInterventions, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        /*
         * Generate recommendations for top priority interventions
         */
        foreach ($eligibleInterventions as $intervention) {
            if ($intervention['key'] === 'tree_planting') {
                // Select appropriate package based on intervention opportunity
                $selectedPackage = $this->selectTreePackage($score->intervention_opportunity);
                $recommendations->push(
                    $this->storeRecommendation(
                        score: $score,
                        key: 'tree_planting',
                        scenario: $selectedPackage,
                        rule: $intervention['rule'],
                        justification: $this->getTreePlantingJustification($score, $selectedPackage)
                    )
                );
            } else {
                $recommendations->push(
                    $this->storeRecommendation(
                        score: $score,
                        key: $intervention['key'],
                        scenario: null,
                        rule: $intervention['rule'],
                        justification: $this->getGenericJustification($intervention['key'])
                    )
                );
            }
        }

        /*
         * Limit recommendations per park.
         */
        return $recommendations->take(
            config(
                'park_heat.interventions.max_recommendations_per_park'
            )
        );
    }

    /**
     * Evaluate rule conditions against park data
     */
    private function evaluateRuleConditions(array $conditions, array $data): bool
    {
        foreach ($conditions as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if ($value === null) {
                return false;
            }

            foreach ($rule as $operator => $threshold) {
                switch ($operator) {
                    case 'gte':
                        if (!($value >= $threshold)) return false;
                        break;
                    case 'lte':
                        if (!($value <= $threshold)) return false;
                        break;
                    case 'gt':
                        if (!($value > $threshold)) return false;
                        break;
                    case 'lt':
                        if (!($value < $threshold)) return false;
                        break;
                    case 'eq':
                        if ($value != $threshold) return false;
                        break;
                    case 'bool':
                        if ($value !== $threshold) return false;
                        break;
                }
            }
        }
        return true;
    }

    /**
     * Select tree package based on intervention opportunity score
     */
    private function selectTreePackage(float $opportunityScore): string
    {
        $packageRules = config('park_heat.interventions.rules.tree_planting.package_selection');

        foreach ($packageRules as $packageKey => $rules) {
            if ($this->evaluateRuleConditions($rules['when'], [
                'intervention_opportunity' => $opportunityScore,
            ])) {
                return $packageKey;
            }
        }

        // Default to medium if no rules match
        return 'medium';
    }

    /**
     * Get tree planting justification based on selected package
     */
    private function getTreePlantingJustification(ParkPriorityScore $score, string $package): string
    {
        $packageConfig = config("park_heat.interventions.tree_planning_packages.$package");
        $packageName = $packageConfig['name'];
        
        return "High heat exposure and low vegetation. Selected {$packageName} package based on intervention opportunity score of {$score->intervention_opportunity}.";
    }

    /**
     * Get generic justification for non-tree interventions
     */
    private function getGenericJustification(string $key): string
    {
        switch ($key) {
            case 'cool_pavement':
                return 'High heat exposure with significant hard-surface coverage suitable for cool pavement treatment.';
            case 'ramada':
                return 'Playground area with insufficient shade structures requires built shade for visitor comfort.';
            default:
                return 'Park conditions indicate this intervention would address identified heat exposure issues.';
        }
    }

    private function storeRecommendation(
        ParkPriorityScore $score,
        string $key,
        ?string $scenario,
        string $rule,
        string $justification
    ): InterventionRecommendation {

        $catalog = config(
            "park_heat.interventions.catalog.$key"
        );

        // Calculate quantity and costs based on intervention type
        $quantity = null;
        $unit = $catalog['unit'] ?? null;
        $upfrontCost = null;
        $annualMaintenanceCost = null;
        $annualWaterCost = null;
        $costBasis = null;
        $source = $catalog['source'] ?? null;
        $sourceUrl = $catalog['source_url'] ?? null;

        if ($key === 'tree_planting') {
            // Use scenario-specific package
            $packageConfig = config("park_heat.interventions.tree_planning_packages.$scenario");
            $quantity = $packageConfig['quantity'];
            $scenarioName = $packageConfig['name'];
            $upfrontCost = $quantity * $catalog['upfront_cost_per_unit'];
            $annualMaintenanceCost = $quantity * $catalog['annual_maintenance'];
            $annualWaterCost = $quantity * $catalog['annual_water'];
            $costBasis = $catalog['cost_note'] . " - {$scenarioName} scenario";
        } elseif ($key === 'ramada') {
            $quantity = 1; // Single ramada recommendation
            $upfrontCost = $catalog['planning_cost']; // Use midpoint for budget optimization
            $costBasis = $catalog['cost_note'];
        } elseif ($key === 'cool_pavement') {
            // For cool pavement, we'll estimate based on hard surface percentage
            // This is a planning assumption - clearly labeled
            $hardSurfacePercent = (float) (
                $score->calculation_data['physical']['hard_surface_percent']
                ?? $score->satelliteMetric?->data['calculation']['hard_surface_percent']
                ?? 0
            );
            // Estimate 10% of hard surface gets treatment (planning assumption)
            $parkAcres = $score->park->acres ?? 0;
            $totalSqFt = $parkAcres * 43560; // Convert acres to sq ft
            $treatableSqFt = ($hardSurfacePercent / 100) * $totalSqFt * 0.10;
            $quantity = round($treatableSqFt);
            $upfrontCost = $quantity * $catalog['planning_cost_per_sqft'];
            $costBasis = $catalog['cost_basis'];
        }

        // Get cooling benefit evidence from Phoenix research
        $coolingBenefit = CoolingBenefitHelper::getCoolingBenefit($key);

        return InterventionRecommendation::updateOrCreate(
            [
                'park_priority_score_id' => $score->id,
                'intervention_key' => $key,
                'scenario' => $scenario,
            ],
            [
                'park_id' => $score->park_id,
                'heatmap_analysis_id' =>
                    $score->heatmap_analysis_id,

                'intervention_name' =>
                    $catalog['name'],

                'category' =>
                    $catalog['category'],

                'quantity' => $quantity,
                'unit' => $unit,
                'upfront_cost' => $upfrontCost,
                'annual_maintenance_cost' => $annualMaintenanceCost,
                'annual_water_cost' => $annualWaterCost,
                'cost_basis' => $costBasis,
                'source' => $source,
                'source_url' => $sourceUrl,

                'rule_matched' =>
                    $rule,

                'justification' =>
                    $justification,

                'cooling_benefit' => $coolingBenefit,

                'model_version' =>
                    config(
                        'park_heat.interventions.model_version'
                    ),
            ]
        );
    }
}
