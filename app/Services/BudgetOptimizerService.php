<?php

namespace App\Services;

use App\Models\InterventionRecommendation;
use App\Models\InvestmentPlan;
use App\Models\InvestmentPlanItem;
use App\Models\ParkPriorityScore;
use Illuminate\Support\Collection;

class BudgetOptimizerService
{
    /**
     * Generate concrete options from intervention recommendations
     */
    public function generateOptions(int $heatmapAnalysisId): array
    {
        $recommendations = InterventionRecommendation::where('heatmap_analysis_id', $heatmapAnalysisId)
            ->with(['park', 'priorityScore'])
            ->get();

        $options = [];

        foreach ($recommendations as $rec) {
            $priorityScore = $rec->priorityScore;
            $catalog = config("park_heat.interventions.catalog.{$rec->intervention_key}");

            if (!$catalog) {
                continue;
            }

            $option = [
                'park_id' => $rec->park_id,
                'park_name' => $rec->park->name,
                'intervention_type' => $rec->intervention_key,
                'scenario' => $rec->scenario,
                'quantity' => $rec->quantity,
                'unit' => $rec->unit,
                'unit_cost' => $this->calculateUnitCost($catalog, $rec),
                'total_cost' => $rec->upfront_cost,
                'modeled_benefit' => $this->calculateModeledBenefit($priorityScore->priority_score, $rec),
                'cost_source' => $catalog['source'] ?? null,
                'cost_is_assumption' => true,
                'benefit_is_assumption' => true,
            ];

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Calculate unit cost from catalog and recommendation
     */
    private function calculateUnitCost(array $catalog, InterventionRecommendation $rec): float
    {
        if ($rec->quantity > 0) {
            return round($rec->upfront_cost / $rec->quantity, 2);
        }

        return 0;
    }

    /**
     * Calculate modeled benefit = priority score × scale factor
     */
    private function calculateModeledBenefit(float $priorityScore, InterventionRecommendation $rec): float
    {
        $scaleFactors = [
            'small' => 0.25,
            'medium' => 0.50,
            'large' => 0.90,
        ];

        $factor = $scaleFactors[$rec->scenario] ?? 0.50;

        return round($priorityScore * $factor, 2);
    }

    /**
     * Optimize investment using multiple-choice knapsack algorithm
     * Never exceeds budget, one intervention per park
     */
    public function optimize(array $options, float $budget): array
    {
        // Group options by park_id (one intervention per park constraint)
        $groups = collect($options)
            ->groupBy('park_id')
            ->values()
            ->all();

        // Initialize states with empty selection
        $states = [
            0 => [
                'cost' => 0,
                'benefit' => 0,
                'selected' => [],
            ],
        ];

        // Process each park's options
        foreach ($groups as $parkOptions) {
            $newStates = $states;

            foreach ($states as $state) {
                foreach ($parkOptions as $option) {
                    $newCost = $state['cost'] + $option['total_cost'];

                    // Never exceed budget constraint
                    if ($newCost > $budget) {
                        continue;
                    }

                    $newBenefit = $state['benefit'] + $option['modeled_benefit'];

                    // Convert to cents for safe integer array key
                    $stateKey = (int) round($newCost * 100);

                    // Keep best option for each cost level
                    $existing = $newStates[$stateKey] ?? null;

                    if ($existing === null || $newBenefit > $existing['benefit']) {
                        $newStates[$stateKey] = [
                            'cost' => $newCost,
                            'benefit' => $newBenefit,
                            'selected' => [
                                ...$state['selected'],
                                $option,
                            ],
                        ];
                    }
                }
            }

            $states = $newStates;
        }

        // Select state with maximum benefit
        $best = collect($states)
            ->sortByDesc('benefit')
            ->first();

        // Calculate best possible benefit (for coverage calculation)
        $bestPossibleBenefit = $this->calculateBestPossibleBenefit($options);

        return [
            'selected_options' => $best['selected'] ?? [],
            'total_cost' => $best['cost'] ?? 0,
            'remaining_budget' => $budget - ($best['cost'] ?? 0),
            'total_modeled_benefit' => $best['benefit'] ?? 0,
            'best_possible_benefit' => $bestPossibleBenefit,
            'modeled_priority_coverage' => $bestPossibleBenefit > 0
                ? round((($best['benefit'] ?? 0) / $bestPossibleBenefit) * 100, 2)
                : 0,
        ];
    }

    /**
     * Calculate best possible benefit (sum of best option per park)
     */
    private function calculateBestPossibleBenefit(array $options): float
    {
        return collect($options)
            ->groupBy('park_id')
            ->map(function ($parkOptions) {
                return $parkOptions->max('modeled_benefit');
            })
            ->sum();
    }

    /**
     * Create and save investment plan
     */
    public function createInvestmentPlan(int $heatmapAnalysisId, float $budget, array $optimizationResult): InvestmentPlan
    {
        $plan = InvestmentPlan::create([
            'heatmap_analysis_id' => $heatmapAnalysisId,
            'budget' => $budget,
            'allocated_cost' => $optimizationResult['total_cost'],
            'remaining_budget' => $optimizationResult['remaining_budget'],
            'total_modeled_benefit' => $optimizationResult['total_modeled_benefit'],
            'modeled_priority_coverage' => $optimizationResult['modeled_priority_coverage'],
            'model_version' => 'v1',
        ]);

        // Create plan items
        foreach ($optimizationResult['selected_options'] as $option) {
            InvestmentPlanItem::create([
                'investment_plan_id' => $plan->id,
                'park_id' => $option['park_id'],
                'intervention_type' => $option['intervention_type'],
                'scenario' => $option['scenario'],
                'quantity' => $option['quantity'],
                'unit' => $option['unit'],
                'unit_cost' => $option['unit_cost'],
                'total_cost' => $option['total_cost'],
                'modeled_benefit' => $option['modeled_benefit'],
                'cost_source' => $option['cost_source'],
                'cost_is_assumption' => $option['cost_is_assumption'],
                'benefit_is_assumption' => $option['benefit_is_assumption'],
            ]);
        }

        return $plan;
    }
}
