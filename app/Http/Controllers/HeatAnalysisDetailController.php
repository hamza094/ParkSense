<?php

namespace App\Http\Controllers;

use App\Models\HeatmapAnalysis;
use App\Models\ParkHeatAnalysis;
use App\Models\EnvironmentalMetric;
use App\Models\SatelliteMetric;
use App\Models\ParkPriorityScore;
use App\Models\InterventionRecommendation;
use App\Models\InvestmentPlan;
use App\Models\Park;
use App\Services\ParkHeatAnalysisService;
use Inertia\Inertia;

class HeatAnalysisDetailController extends Controller
{
    /**
     * Display the specified heat analysis detail page.
     */
    public function show($id)
    {
        $heatAnalysis = HeatmapAnalysis::findOrFail($id);

        // Get park heat analyses for this analysis
        $parkHeatAnalyses = ParkHeatAnalysis::where('heatmap_analysis_id', $heatAnalysis->id)
            ->with('park')
            ->get();

        // Use the original map_data directly for the map (already in correct GeoJSON format)
        // Load GeoJSON via API endpoint to avoid Inertia timeout with large payloads
        $heatmapTiles = null; // Don't load GeoJSON through Inertia props
        $hasHeatmapData = !empty($heatAnalysis->map_data);

        // Get only the parks relevant to this heat analysis
        $parkIds = $heatAnalysis->park_ids ?? [];
        $parks = Park::whereIn('id', $parkIds)
            ->get()
            ->map(function ($park) {
                return [
                    'id' => $park->id,
                    'park_id' => $park->park_id,
                    'name' => $park->name,
                    'property_type' => $park->property_type ?? null,
                    'park_type' => $park->park_type ?? null,
                    'acres' => $park->acres ?? null,
                    'latitude' => $park->latitude ?? null,
                    'longitude' => $park->longitude ?? null,
                    'geometry' => $park->geometry ? $park->geometry->toJson() : null,
                ];
            });

        // Get Environmental Analysis Results
        $environmentalResults = EnvironmentalMetric::query()
            ->where('heatmap_analysis_id', $heatAnalysis->id)
            ->with(['park', 'parkHeatAnalysis'])
            ->get()
            ->map(function ($metric) {
                return [
                    'id' => $metric->id,
                    'park_id' => $metric->park_id,
                    'park_name' => $metric->park->name,
                    'activity_id' => $metric->activity_id,
                    'status' => $metric->status,
                    'data' => $metric->data,
                    'average_temperature' => $metric->parkHeatAnalysis ? $metric->parkHeatAnalysis->average_temperature : null,
                    'created_at' => $metric->created_at->toISOString(),
                ];
            });

        // Get Satellite Analysis Results
        $satelliteResults = SatelliteMetric::query()
            ->where('heatmap_analysis_id', $heatAnalysis->id)
            ->with(['park', 'parkHeatAnalysis'])
            ->get()
            ->map(function ($metric) {
                return [
                    'id' => $metric->id,
                    'park_id' => $metric->park_id,
                    'park_name' => $metric->park->name,
                    'activity_id' => $metric->activity_id,
                    'status' => $metric->status,
                    'data' => $metric->data,
                    'average_temperature' => $metric->parkHeatAnalysis ? $metric->parkHeatAnalysis->average_temperature : null,
                    'created_at' => $metric->created_at->toISOString(),
                ];
            });

        // Get Priority Scores
        $priorityScores = ParkPriorityScore::query()
            ->where('heatmap_analysis_id', $heatAnalysis->id)
            ->with(['park'])
            ->orderByDesc('priority_score')
            ->get()
            ->map(function ($score) {
                return [
                    'id' => $score->id,
                    'park_id' => $score->park_id,
                    'park_name' => $score->park->name,
                    'priority_score' => $score->priority_score,
                    'heat_severity' => $score->heat_severity,
                    'environmental_stress' => $score->environmental_stress,
                    'physical_condition' => $score->physical_condition,
                    'park_importance' => $score->park_importance,
                    'intervention_opportunity' => $score->intervention_opportunity,
                    'created_at' => $score->created_at->toISOString(),
                ];
            });

        // Priority Scoring Overview - explanation of factors used in calculation
        $priorityScoringOverview = [
            'title' => 'Priority Scoring Methodology',
            'description' => 'Priority scores are calculated by combining multiple weighted factors to identify parks most in need of intervention.',
            'factors' => [
                [
                    'name' => 'Heat Severity',
                    'description' => 'Measures the intensity of heat exposure based on average temperature readings from the heat analysis. Higher temperatures indicate greater heat stress.',
                    'weight' => 'High',
                    'range' => '0-100'
                ],
                [
                    'name' => 'Environmental Stress',
                    'description' => 'Assesses environmental conditions such as tree canopy coverage, surface temperature, and heat island effects. Indicates the level of environmental pressure on the park.',
                    'weight' => 'High',
                    'range' => '0-100'
                ],
                [
                    'name' => 'Physical Condition',
                    'description' => 'Evaluates the current state of park infrastructure, including amenities, pathways, and general maintenance. Poor condition increases intervention priority.',
                    'weight' => 'Medium',
                    'range' => '0-100'
                ],
                [
                    'name' => 'Park Importance',
                    'description' => 'Considers the park\'s community value, usage frequency, accessibility, and role in the urban ecosystem. Higher importance elevates priority.',
                    'weight' => 'Medium',
                    'range' => '0-100'
                ],
                [
                    'name' => 'Intervention Opportunity',
                    'description' => 'Measures the potential impact and feasibility of interventions. Parks with high improvement potential and implementable solutions score higher.',
                    'weight' => 'Medium',
                    'range' => '0-100'
                ]
            ],
            'calculation_note' => 'The final priority score is a weighted composite of all factors, with higher scores indicating greater need for immediate intervention.'
        ];

        // Intervention Recommendations Overview - explanation of recommendation generation
        $interventionRecommendationsOverview = [
            'title' => 'Intervention Recommendations Methodology',
            'description' => 'Intervention recommendations are generated based on priority scores, park characteristics, and evidence-based cooling strategies.',
            'factors' => [
                [
                    'name' => 'Priority Score Integration',
                    'description' => 'Uses calculated priority scores to determine which parks require immediate intervention and which specific interventions are most appropriate.',
                    'weight' => 'High',
                    'range' => 'Based on priority scores'
                ],
                [
                    'name' => 'Park Characteristics',
                    'description' => 'Analyzes park size, type, property classification, and existing infrastructure to recommend suitable interventions that fit the park context.',
                    'weight' => 'High',
                    'range' => 'Park-specific data'
                ],
                [
                    'name' => 'Evidence-Based Strategies',
                    'description' => 'Applies proven urban heat mitigation strategies including tree planting, shade structures, water features, and surface modifications.',
                    'weight' => 'High',
                    'range' => 'Established best practices'
                ],
                [
                    'name' => 'Cost-Benefit Analysis',
                    'description' => 'Evaluates intervention costs against projected cooling benefits, water savings, and long-term maintenance requirements.',
                    'weight' => 'Medium',
                    'range' => 'ROI calculations'
                ],
                [
                    'name' => 'Implementation Feasibility',
                    'description' => 'Considers technical requirements, maintenance needs, and operational constraints to ensure recommendations are practical and sustainable.',
                    'weight' => 'Medium',
                    'range' => 'Feasibility assessment'
                ]
            ],
            'calculation_note' => 'Recommendations are generated through rule-based matching of park conditions to proven intervention strategies, with each recommendation including cost estimates and expected benefits.'
        ];

        // Budget Optimization Overview - explanation of investment optimization
        $budgetOptimizationOverview = [
            'title' => 'Budget Optimization Methodology',
            'description' => 'Budget optimization maximizes the impact of available funding by selecting the most effective combination of interventions across parks.',
            'factors' => [
                [
                    'name' => 'Budget Constraints',
                    'description' => 'Operates within specified budget limits to ensure financial feasibility while maximizing coverage and impact.',
                    'weight' => 'High',
                    'range' => 'Budget allocation'
                ],
                [
                    'name' => 'Priority Weighting',
                    'description' => 'Prioritizes interventions for high-priority parks to ensure limited resources address the most critical needs first.',
                    'weight' => 'High',
                    'range' => 'Priority score impact'
                ],
                [
                    'name' => 'Cost-Effectiveness',
                    'description' => 'Balances intervention costs against modeled benefits to achieve maximum cooling impact per dollar spent.',
                    'weight' => 'High',
                    'range' => 'Cost-benefit ratio'
                ],
                [
                    'name' => 'Spatial Coverage',
                    'description' => 'Distributes interventions across multiple parks to maximize community benefit and heat island reduction impact.',
                    'weight' => 'Medium',
                    'range' => 'Geographic spread'
                ],
                [
                    'name' => 'Scalability Analysis',
                    'description' => 'Considers implementation scalability and the ability to phase interventions over time for optimal long-term results.',
                    'weight' => 'Medium',
                    'range' => 'Implementation planning'
                ]
            ],
            'calculation_note' => 'The optimization algorithm selects intervention combinations that maximize total modeled benefit while staying within budget constraints, with preference for high-priority parks and cost-effective solutions.'
        ];

        // Get Intervention Recommendations
        $interventionRecommendations = InterventionRecommendation::query()
            ->where('heatmap_analysis_id', $heatAnalysis->id)
            ->with(['park', 'priorityScore'])
            ->get()
            ->groupBy('park_id')
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'park' => [
                        'id' => $first->park_id,
                        'name' => $first->park->name,
                    ],
                    'priority_score' => $first->priorityScore ? $first->priorityScore->priority_score : null,
                    'recommendations' => $items->map(fn ($item) => [
                        'id' => $item->id,
                        'scenario' => $item->scenario,
                        'name' => $item->intervention_name,
                        'category' => $item->category,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'upfront_cost' => $item->upfront_cost,
                        'annual_maintenance_cost' => $item->annual_maintenance_cost,
                        'annual_water_cost' => $item->annual_water_cost,
                        'cost_basis' => $item->cost_basis,
                        'source' => $item->source,
                        'source_url' => $item->source_url,
                        'rule' => $item->rule_matched,
                        'justification' => $item->justification,
                    ])->values(),
                ];
            })
            ->values();

        // Get Investment Plan
        $investmentPlan = null;
        $plan = InvestmentPlan::where('heatmap_analysis_id', $heatAnalysis->id)
            ->with(['items.park'])
            ->latest()
            ->first();
        
        if ($plan) {
            $investmentPlan = [
                'id' => $plan->id,
                'budget' => $plan->budget,
                'allocated_cost' => $plan->allocated_cost,
                'remaining_budget' => $plan->remaining_budget,
                'total_modeled_benefit' => $plan->total_modeled_benefit,
                'modeled_priority_coverage' => $plan->modeled_priority_coverage,
                'created_at' => $plan->created_at->toISOString(),
                'items' => $plan->items->map(function ($item) {
                    return [
                        'park_id' => $item->park_id,
                        'park_name' => $item->park->name,
                        'intervention_type' => $item->intervention_type,
                        'scenario' => $item->scenario,
                        'quantity' => $item->quantity,
                        'unit' => $item->unit,
                        'total_cost' => $item->total_cost,
                        'modeled_benefit' => $item->modeled_benefit,
                    ];
                })->values(),
            ];
        }

        // Get park information for the heat analysis
        $parkId = null;
        $parkName = null;
        if ($parkHeatAnalyses->isNotEmpty()) {
            $firstParkAnalysis = $parkHeatAnalyses->first();
            $parkId = $firstParkAnalysis->park_id;
            $parkName = $firstParkAnalysis->park->name;
        }

        return Inertia::render('HeatAnalysisDetail', [
            'heatAnalysis' => [
                'id' => $heatAnalysis->id,
                'park_id' => $parkId,
                'park_name' => $parkName,
                'created_at' => $heatAnalysis->created_at->toISOString(),
                'status' => $heatAnalysis->status,
                'activity_id' => $heatAnalysis->activity_id,
                'has_heatmap_data' => $hasHeatmapData,
            ],
            'heatmapGeoJson' => $heatmapTiles, // Now null to avoid timeout
            'parks' => $parks,
            'environmentalResults' => $environmentalResults,
            'satelliteResults' => $satelliteResults,
            'priorityScores' => $priorityScores,
            'priorityScoringOverview' => $priorityScoringOverview,
            'interventionRecommendations' => $interventionRecommendations,
            'interventionRecommendationsOverview' => $interventionRecommendationsOverview,
            'budgetOptimizationOverview' => $budgetOptimizationOverview,
            'investmentPlan' => $investmentPlan,
        ]);
    }

    /**
     * Get GeoJSON data for heat analysis (avoids Inertia timeout with large payloads)
     */
    public function getGeoJson($id)
    {
        $heatAnalysis = HeatmapAnalysis::findOrFail($id);
        
        return response()->json($heatAnalysis->map_data);
    }

}