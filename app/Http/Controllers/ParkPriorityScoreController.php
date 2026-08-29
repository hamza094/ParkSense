<?php

namespace App\Http\Controllers;

use App\Services\ParkPriorityScoreService;
use App\Models\HeatmapAnalysis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParkPriorityScoreController extends Controller
{
    public function __construct(
        private ParkPriorityScoreService $scoreService
    ) {}

    /**
     * Calculate priority scores for all parks in a heatmap analysis.
     */
    public function calculate(Request $request, int $heatmapAnalysisId): JsonResponse
    {
        $heatmapAnalysis = HeatmapAnalysis::find($heatmapAnalysisId);

        if (!$heatmapAnalysis) {
            return response()->json(['error' => 'Heatmap analysis not found'], 404);
        }

        try {
            $scores = $this->scoreService->calculateForAnalysis($heatmapAnalysisId);

            return response()->json([
                'message' => 'Priority scores calculated successfully',
                'heatmap_analysis_id' => $heatmapAnalysisId,
                'count' => $scores->count(),
                'scores' => $scores->map(fn($score) => [
                    'id' => $score->id,
                    'park_id' => $score->park_id,
                    'park_name' => $score->park->name,
                    'priority_score' => $score->priority_score,
                    'heat_severity' => $score->heat_severity,
                    'environmental_stress' => $score->environmental_stress,
                    'physical_condition' => $score->physical_condition,
                    'park_importance' => $score->park_importance,
                    'intervention_opportunity' => $score->intervention_opportunity,
                    'model_version' => $score->model_version,
                ]),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
