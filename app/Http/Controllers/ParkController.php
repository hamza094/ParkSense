<?php

namespace App\Http\Controllers;

use App\Models\Park;
use App\Actions\SendFortyGuardHeatmapRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ParkController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $parks = Park::all();

        return Inertia::render('Dashboard', [
            'parks' => $parks,
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
        
        // Convert to GeoJSON format
        $polygonCoordinates = array_map(
            fn ($point) => [
                $point['lng'],
                $point['lat'],
            ],
            // Close the polygon
            array_merge($coordinates, [$coordinates[0]])
        );
        
        // Prepare data for FortyGuard API
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
                'start_date' => now()->subDays(7)->toDateString(),
                'start_time' => '08:00',
                'end_time' => '18:00',
                'filter_type' => 2,
            ],
            'granularity' => 60,
            'analytic_type' => 'tcm',
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
            
            return response()->json([
                'activity_id' => $responseData['data']['activity_id'] ?? null,
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
    public function checkHeatmapStatus(Request $request, string $activityId)
    {
        try {
            // Check cache first for completed results
            $cacheKey = 'heatmap_result_' . $activityId;
            $cachedResult = cache()->get($cacheKey);
            
            if ($cachedResult) {
                return response()->json($cachedResult);
            }
            
            $response = Http::timeout(30)
                ->withHeaders([
                    'api-key' => config('services.fortyguard.key'),
                ])
                ->get(config('services.fortyguard.url') . '/status/' . $activityId);
            
            if ($response->failed()) {
                return response()->json([
                    'error' => 'Failed to check status',
                    'status' => $response->status(),
                    'message' => $response->body(),
                ], $response->status());
            }
            
            $responseData = $response->json();
            
            // Cache the result if status is Completed
            if ($responseData['data']['status'] === 'Completed') {
                cache()->put($cacheKey, $responseData, now()->addHours(24));
            }
            
            return response()->json($responseData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check heatmap status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get cached heatmap result without API call
     */
    public function getCachedHeatmapResult(string $activityId)
    {
        $cacheKey = 'heatmap_result_' . $activityId;
        $cachedResult = cache()->get($cacheKey);
        
        if ($cachedResult) {
            return Inertia::render('Dashboard', [
                'parks' => Park::all(),
                'cachedHeatmap' => [
                    'cached' => true,
                    'data' => $cachedResult,
                ],
            ]);
        }
        
        return Inertia::render('Dashboard', [
            'parks' => Park::all(),
            'cachedHeatmap' => [
                'cached' => false,
                'message' => 'No cached result found',
            ],
        ]);
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
