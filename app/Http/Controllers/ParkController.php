<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Models\HeatmapAnalysis;
use App\Models\ParkHeatAnalysis;
use App\Actions\SendFortyGuardHeatmapRequest;
use App\Actions\ManageHeatmapAnalysis;
use App\Services\ParkHeatAnalysisService;
use App\Services\FortyGuard\FortyGuardClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\LineString;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

class ParkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parks = Park::all();

        // Get heat analyses summary list (simplified to avoid memory issues)
        // Get latest IDs first without sorting large dataset
        $latestIds = HeatmapAnalysis::query()
            ->select('id')
            ->orderByDesc('id')
            ->limit(50)
            ->pluck('id');

        $heatAnalyses = HeatmapAnalysis::query()
            ->select(['id', 'park_ids', 'created_at', 'status'])
            ->whereIn('id', $latestIds)
            ->withCount('parkHeatAnalyses as tile_count')
            ->get()
            ->map(function ($analysis) {
                // Get park name from park_ids array
                $parkName = 'Multiple Parks';
                $parkId = null;
                if ($analysis->park_ids && is_array($analysis->park_ids) && count($analysis->park_ids) > 0) {
                    $firstParkId = $analysis->park_ids[0];
                    $park = Park::find($firstParkId);
                    if ($park) {
                        $parkId = $firstParkId;
                        $parkName = $park->name;
                        if (count($analysis->park_ids) > 1) {
                            $parkName = $park->name . ' +' . (count($analysis->park_ids) - 1);
                        }
                    }
                }

                return [
                    'id' => $analysis->id,
                    'park_id' => $parkId,
                    'park_name' => $parkName,
                    'created_at' => $analysis->created_at->toISOString(),
                    'status' => $analysis->status,
                    'tile_count' => $analysis->tile_count ?? 0,
                ];
            });

        return Inertia::render('Dashboard', [
            'parks' => $parks,
            'heatAnalyses' => $heatAnalyses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Store polygon data from map drawing
     */
    public function storePolygon(Request $request)
    {
        $coordinates = $request->input('coordinates', []);
        
        // Convert coordinates to Point objects
        $points = array_map(
            fn ($point) => new Point($point['lat'], $point['lng']),
            $coordinates
        );
        
        // Close the polygon by adding the first point at the end
        $points[] = $points[0];
        
        // Create LineString from points
        $lineString = new LineString($points);
        
        // Create Polygon object
        $aoiPolygon = new Polygon([$lineString]);
        
        // Find parks that intersect with the AOI polygon
        $intersectingParks = Park::query()
            ->whereIntersects('geometry', $aoiPolygon)
            ->get();
        
        // Check if any parks were found in the selected area
        if ($intersectingParks->isEmpty()) {
            return response()->json([
                'error' => 'No parks were found in the selected area.',
            ], 422);
        }
       
        // Convert to GeoJSON format for FortyGuard API
        $polygonCoordinates = array_map(
            fn ($point) => [
                $point['lng'],
                $point['lat'],
            ],
            array_merge($coordinates, [$coordinates[0]])
        );
        
        // Prepare data for FortyGuard API
        $startDate = now()->subDays(7)->toDateString();
        $startTime = '08:00';
        $endTime = '22:00';
        $filterType = 2;
        $granularity = 60;
        $analyticType = 'tcm';
        
        $apiData = [
            'polygon_aoi' => [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'properties' => [],
                        'geometry' => [
                            'type' => 'Polygon',
                            'coordinates' => [$polygonCoordinates],
                        ],
                    ],
                ],
            ],
            'date_time' => [
                'start_date' => $startDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'filter_type' => $filterType,
            ],
            'granularity' => $granularity,
            'analytic_type' => $analyticType,
        ];
        
        try {
            // Send request to FortyGuard API using action class
            $sendHeatmapRequest = new SendFortyGuardHeatmapRequest();
            $response = $sendHeatmapRequest($apiData);
            
            if ($response->failed()) {
                return response()->json([
                    'error' => 'API request failed',
                    'status' => $response->status(),
                    'message' => $response->body(),
                ], $response->status());
            }
            
            $responseData = $response->json();
            $activityId = $responseData['data']['activity_id'] ?? null;
            
            // Save initial heatmap analysis with pending status using action class
            $manageHeatmap = new ManageHeatmapAnalysis();
            $manageHeatmap->create([
                'activity_id' => $activityId,
                'aoi' => $coordinates,
                'park_ids' => $intersectingParks->pluck('id')->toArray(),
                'start_date' => $startDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'filter_type' => $filterType,
                'granularity' => $granularity,
                'analytic_type' => $analyticType,
            ]);
            
            return response()->json([
                'activity_id' => $activityId,
                'message' => 'Heatmap submitted successfully. Processing...',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to process heatmap request',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check heatmap processing status
     */
    public function checkHeatmapStatus(string $activityId)
    {
        $cacheKey = 'heatmap_result_' . $activityId;
        
        // Check if result is already cached
        $cachedResult = cache()->get($cacheKey);
        if ($cachedResult) {
            return response()->json($cachedResult);
        }
        
        try {
            $fortyGuard = new FortyGuardClient();
            $response = $fortyGuard->getStatus($activityId);
            
            // Update heatmap analysis record if status is Completed using action class
            if ($response['data']['status'] === 'Completed') {
                $manageHeatmap = new ManageHeatmapAnalysis();
                $manageHeatmap->markAsCompleted($activityId, $response['data']);
                
                // Fetch the HeatmapAnalysis record to get its ID for navigation
                $heatmapAnalysis = \App\Models\HeatmapAnalysis::where('activity_id', $activityId)->first();
                if ($heatmapAnalysis) {
                    $response['data']['heatmap_analysis_id'] = $heatmapAnalysis->id;
                }
            }
            
            if ($response['data']['status'] === 'Completed' || $response['data']['status'] === 'Failed') {
                // Cache the result for 24 hours
                cache()->put($cacheKey, $response, now()->addHours(24));
                
                // Cache the latest activity_id for heat analysis (user-scoped)
                if ($response['data']['status'] === 'Completed') {
                    cache()->put('latest_heatmap_activity_id_' . auth()->id(), $activityId, now()->addHours(24));
                }
            }
            
            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check heatmap status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run park heat analysis for latest completed heatmap
     */
    public function runHeatAnalysis(Request $request)
    {
        try {
            // Get the latest activity_id from cache (user-scoped)
            $latestActivityId = cache()->get('latest_heatmap_activity_id_' . auth()->id());
            
            // Fallback: if cache is empty, get from database using raw SQL to avoid memory issues
            if (!$latestActivityId) {
                $result = DB::selectOne('SELECT activity_id FROM heatmap_analyses WHERE status = ? AND map_data IS NOT NULL ORDER BY id DESC LIMIT 1', ['completed']);
                
                if ($result) {
                    $latestActivityId = $result->activity_id;
                    // Cache it for future use
                    cache()->put('latest_heatmap_activity_id_' . auth()->id(), $latestActivityId, now()->addHours(24));
                }
            }
            
            if (!$latestActivityId) {
                return response()->json([
                    'error' => 'No recent heatmap analysis found. Please draw a polygon first.',
                ], 404);
            }

            // Load the analysis by activity_id directly (avoid any sorting)
            $analysis = HeatmapAnalysis::where('activity_id', $latestActivityId)->first();

            if (!$analysis || !$analysis->map_data) {
                return response()->json([
                    'error' => 'Heatmap analysis has no map data',
                ], 404);
            }

            $service = new ParkHeatAnalysisService();
            
            // Extract tiles
            $tiles = $service->extractHeatmapTiles($analysis);
            
            // Get parks that were in the AOI
            $parkIds = $analysis->park_ids ?? [];
            $parks = Park::whereIn('id', $parkIds)->get();
            
            // Process each park
            foreach ($parks as $park) {
                $matchedTiles = $service->findIntersectingTiles($park, $tiles);
                $metrics = $service->calculateParkHeatMetrics($matchedTiles);
                
                // Only save if park has matched tiles with valid temperature data
                if ($metrics['matched_tile_count'] > 0 && $metrics['average_temperature'] !== null) {
                    $service->saveParkHeatAnalysis($park, $analysis, $metrics);
                }
            }

            // Get ranked results
            $rankedParks = $service->rankParksByTemperature($analysis, 3);

            return response()->json([
                'success' => true,
                'message' => 'Park heat analysis completed successfully',
                'ranked_parks' => $rankedParks,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to run park heat analysis',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Park $park)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Park $park)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Park $park)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Park $park)
    {
        //
    }
}
