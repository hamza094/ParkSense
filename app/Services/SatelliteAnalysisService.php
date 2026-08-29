<?php

namespace App\Services;

use App\Models\SatelliteMetric;
use App\Models\HeatmapAnalysis;
use App\Models\Park;
use App\Models\ParkHeatAnalysis;
use App\Services\FortyGuard\FortyGuardClient;

class SatelliteAnalysisService
{
    public function __construct(
        private FortyGuardClient $fortyGuard
    ) {}

    public function submitAnalysis(Park $park, string $startDate, string $startTime, int $filterType, int $granularity, string $endTime = '22:00'): string
    {
        $response = $this->fortyGuard->satellite(
            latitude: $park->latitude,
            longitude: $park->longitude,
            startDate: $startDate,
            startTime: $startTime,
            filterType: $filterType,
            granularity: $granularity,
            endTime: $endTime,
        );

        return $response['data']['activity_id'];
    }

    public function checkStatus(string $activityId): array
    {
        return $this->fortyGuard->getStatus($activityId);
    }

    public function saveResultByActivityId(string $activityId, array $result): SatelliteMetric
    {
        $metric = SatelliteMetric::where('activity_id', $activityId)->first();
        
        if (!$metric) {
            throw new \Exception("Satellite metric not found for activity_id: {$activityId}");
        }

        $metric->status = 'completed';
        $metric->data = $result;
        $metric->save();

        return $metric;
    }

    public function markAsFailed(string $activityId): SatelliteMetric
    {
        $metric = SatelliteMetric::where('activity_id', $activityId)->first();
        
        if (!$metric) {
            throw new \Exception("Satellite metric not found for activity_id: {$activityId}");
        }

        $metric->status = 'failed';
        $metric->save();

        return $metric;
    }

    public function analyzeTopParks(int $heatmapAnalysisId, int $limit): array
    {
        // Get the specific heatmap analysis from database
        $analysis = HeatmapAnalysis::find($heatmapAnalysisId);

        if (!$analysis) {
            throw new \Exception('Heatmap analysis not found. Please provide a valid analysis ID.');
        }

        if ($analysis->status !== 'Completed') {
            throw new \Exception('Heatmap analysis must be completed before running satellite analysis.');
        }

        // Check if satellite analysis already exists for this heatmap (pending or completed)
        $existingAnalysis = SatelliteMetric::query()
            ->where('heatmap_analysis_id', $analysis->id)
            ->where('status', '!=', 'failed')
            ->exists();

        if ($existingAnalysis) {
            throw new \Exception('Satellite analysis already exists for this heatmap. Data is available.');
        }

        $topParks = ParkHeatAnalysis::query()
            ->where('heatmap_analysis_id', $analysis->id)
            ->orderByDesc('average_temperature')
            ->limit($limit)
            ->with('park')
            ->get();

        if ($topParks->isEmpty()) {
            throw new \Exception('No parks found in heat analysis');
        }

        $submissions = [];
        $startDate = now()->subDays(7)->toDateString();
        $startTime = '08:00';
        $filterType = 2;
        $granularity = 80; // Default granularity

        foreach ($topParks as $parkAnalysis) {
            $park = $parkAnalysis->park;
            
            if (!$park->latitude || !$park->longitude) {
                continue;
            }

            $activityId = $this->submitAnalysis(
                $park,
                $startDate,
                $startTime,
                $filterType,
                $granularity
            );

            // Create pending record in database to link activity_id with park and heatmap analysis
            SatelliteMetric::create([
                'park_id' => $park->id,
                'heatmap_analysis_id' => $analysis->id,
                'activity_id' => $activityId,
                'status' => 'pending',
                'data' => null, // Will be updated when analysis completes
            ]);

            $submissions[] = [
                'park_id' => $park->id,
                'park_name' => $park->name,
                'average_temperature' => $parkAnalysis->average_temperature,
                'activity_id' => $activityId,
            ];
        }

        return $submissions;
    }
}
