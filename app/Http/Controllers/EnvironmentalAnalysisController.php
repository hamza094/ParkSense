<?php

namespace App\Http\Controllers;

use App\Services\EnvironmentalAnalysisService;
use Illuminate\Http\Request;

class EnvironmentalAnalysisController extends Controller
{
    public function __construct(
        private EnvironmentalAnalysisService $service
    ) {}

    /**
     * Run environmental analysis for top parks from latest heatmap
     */
    public function runEnvironmentalAnalysis(Request $request)
    {
        try {
            $submissions = $this->service->analyzeTopParks(3);

            return response()->json([
                'success' => true,
                'message' => 'Environmental analysis submitted successfully',
                'submissions' => $submissions,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to run environmental analysis',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check environmental analysis status
     */
    public function checkEnvironmentalStatus(Request $request, string $activityId)
    {
        try {
            $status = $this->service->checkStatus($activityId);

            // If analysis is completed, save the result to database
            if ($status['data']['status'] === 'Completed' && isset($status['data']['result'])) {
                try {
                    $this->service->saveResultByActivityId($activityId, $status['data']['result']);
                } catch (\Exception $e) {
                    // Log error but still return status to frontend
                    \Log::error('Failed to save environmental result', [
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
                    \Log::error('Failed to mark environmental analysis as failed', [
                        'activity_id' => $activityId,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json($status);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to check environmental status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
