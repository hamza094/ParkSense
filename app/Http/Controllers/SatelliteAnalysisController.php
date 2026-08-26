<?php

namespace App\Http\Controllers;

use App\Services\SatelliteAnalysisService;
use Illuminate\Http\Request;

class SatelliteAnalysisController extends Controller
{
    public function __construct(
        private SatelliteAnalysisService $service
    ) {}

    /**
     * Run satellite analysis for top parks from latest heatmap
     */
    public function runSatelliteAnalysis(Request $request)
    {
        try {
            $submissions = $this->service->analyzeTopParks(3);

            return response()->json([
                'success' => true,
                'message' => 'Satellite analysis submitted successfully',
                'submissions' => $submissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to run satellite analysis',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check satellite analysis status
     */
    public function checkSatelliteStatus(Request $request, string $activityId)
    {
        try {
            $status = $this->service->checkStatus($activityId);

            // If analysis is completed, save the result to database
            if ($status['data']['status'] === 'Completed' && isset($status['data']['result'])) {
                try {
                    $this->service->saveResultByActivityId($activityId, $status['data']['result']);
                } catch (\Exception $e) {
                    // Log error but still return status to frontend
                    \Log::error('Failed to save satellite result', [
                        'activity_id' => $activityId,
                        'error' => $e->getMessage()
                    ]);
                    // Add error info to response
                    $status['save_error'] = $e->getMessage();
                }
            }

            // If analysis failed, mark record as failed
            if ($status['data']['status'] === 'Failed') {
                try {
                    $this->service->markAsFailed($activityId);
                } catch (\Exception $e) {
                    \Log::error('Failed to mark satellite analysis as failed', [
                        'activity_id' => $activityId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check satellite status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
