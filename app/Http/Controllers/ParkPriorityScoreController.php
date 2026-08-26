<?php

namespace App\Http\Controllers;

use App\Services\ParkPriorityScoreService;
use App\Models\ParkPriorityScore;
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

    /**
     * Get priority scores for a heatmap analysis.
     */
    public function index(Request $request, int $heatmapAnalysisId): JsonResponse
    {
        $heatmapAnalysis = HeatmapAnalysis::find($heatmapAnalysisId);

        if (!$heatmapAnalysis) {
            return response()->json(['error' => 'Heatmap analysis not found'], 404);
        }

        $scores = ParkPriorityScore::query()
            ->with(['park'])
            ->where('heatmap_analysis_id', $heatmapAnalysisId)
            ->orderByDesc('priority_score')
            ->get();

        return response()->json([
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
    }

    /**
     * Get detailed priority score for a specific park.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $score = ParkPriorityScore::with(['park', 'heatmapAnalysis', 'parkHeatAnalysis', 'environmentalMetric', 'satelliteMetric'])
            ->find($id);

        if (!$score) {
            return response()->json(['error' => 'Priority score not found'], 404);
        }

        return response()->json([
            'id' => $score->id,
            'park' => [
                'id' => $score->park->id,
                'name' => $score->park->name,
                'park_type' => $score->park->park_type,
                'acres' => $score->park->acres,
            ],
            'heatmap_analysis_id' => $score->heatmap_analysis_id,
            'priority_score' => $score->priority_score,
            'component_scores' => [
                'heat_severity' => $score->heat_severity,
                'environmental_stress' => $score->environmental_stress,
                'physical_condition' => $score->physical_condition,
                'park_importance' => $score->park_importance,
                'intervention_opportunity' => $score->intervention_opportunity,
            ],
            'calculation_data' => $score->calculation_data,
            'model_version' => $score->model_version,
            'created_at' => $score->created_at,
            'updated_at' => $score->updated_at,
        ]);
    }
}
