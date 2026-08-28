<?php

namespace App\Http\Controllers;

use App\Services\InterventionSelectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterventionController extends Controller
{
    public function generate(
        int $heatmapAnalysisId,
        InterventionSelectionService $service
    ): JsonResponse {

        $recommendations =
            $service->recommendForAnalysis(
                $heatmapAnalysisId
            );

        return response()->json([
            'data' => $recommendations
                ->groupBy('park_id')
                ->map(function ($items) {

                    $first = $items->first();

                    return [
                        'park' => [
                            'id' => $first->park_id,
                            'name' => $first->park->name,
                        ],

                        'priority_score' =>
                            $first->priorityScore->priority_score,

                        'recommendations' =>
                            $items->map(fn ($item) => [
                                'id' => $item->id,
                                'scenario' =>
                                    $item->scenario,
                                'name' =>
                                    $item->intervention_name,
                                'category' =>
                                    $item->category,
                                'quantity' =>
                                    $item->quantity,
                                'unit' =>
                                    $item->unit,
                                'upfront_cost' =>
                                    $item->upfront_cost,
                                'annual_maintenance_cost' =>
                                    $item->annual_maintenance_cost,
                                'annual_water_cost' =>
                                    $item->annual_water_cost,
                                'cost_basis' =>
                                    $item->cost_basis,
                                'source' =>
                                    $item->source,
                                'source_url' =>
                                    $item->source_url,
                                'rule' =>
                                    $item->rule_matched,
                                'justification' =>
                                    $item->justification,
                            ])->values(),
                    ];
                })
                ->values(),
        ]);
    }
}
