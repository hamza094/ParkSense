<?php

namespace App\Http\Controllers;

use App\Services\BudgetOptimizerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class InvestmentController extends Controller
{
    protected BudgetOptimizerService $optimizer;

    public function __construct(BudgetOptimizerService $optimizer)
    {
        $this->optimizer = $optimizer;
    }

    /**
     * Generate optimized investment plan for given budget
     */
    public function optimize(Request $request, int $heatmapAnalysisId): JsonResponse
    {
        $budget = (float) $request->query('budget');

        if ($budget <= 0) {
            return response()->json([
                'message' => 'Budget must be greater than 0.',
                'selected_options' => [],
                'total_cost' => 0,
                'remaining_budget' => $budget,
                'total_modeled_benefit' => 0,
                'modeled_priority_coverage' => 0,
            ], 400);
        }

        // Generate concrete options from intervention recommendations
        $options = $this->optimizer->generateOptions($heatmapAnalysisId);

        if (empty($options)) {
            return response()->json([
                'message' => 'No intervention recommendations found for this analysis.',
                'selected_options' => [],
                'total_cost' => 0,
                'remaining_budget' => $budget,
                'total_modeled_benefit' => 0,
                'modeled_priority_coverage' => 0,
            ], 200);
        }

        // Optimize within budget constraint
        $optimizationResult = $this->optimizer->optimize($options, $budget);

        // Create and save investment plan
        $plan = $this->optimizer->createInvestmentPlan(
            $heatmapAnalysisId,
            $budget,
            $optimizationResult
        );

        // Load plan with items and park relationships
        $plan->load(['items.park']);

        return response()->json([
            'plan' => $plan,
            'selected_options' => $optimizationResult['selected_options'],
            'total_cost' => $optimizationResult['total_cost'],
            'remaining_budget' => $optimizationResult['remaining_budget'],
            'total_modeled_benefit' => $optimizationResult['total_modeled_benefit'],
            'modeled_priority_coverage' => $optimizationResult['modeled_priority_coverage'],
        ], 200);
    }
}
