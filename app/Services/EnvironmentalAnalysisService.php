<?php

namespace App\Services;

use App\Models\EnvironmentalMetric;
use App\Models\HeatmapAnalysis;
use App\Models\Park;
use App\Models\ParkHeatAnalysis;
use App\Services\FortyGuard\FortyGuardClient;

class EnvironmentalAnalysisService
{
    public function __construct(
        private FortyGuardClient $fortyGuard
    ) {}

    public function submitAnalysis(Park $park, float $temperature, string $startDate, string $startTime, int $filterType, string $endTime = '22:00'): string
    {
        $response = $this->fortyGuard->environmentalParameters(
            latitude: $park->latitude,
            longitude: $park->longitude,
            temperature: $temperature,
            startDate: $startDate,
            startTime: $startTime,
            filterType: $filterType,
            endTime: $endTime,
        );

        return $response['data']['activity_id'];
    }

    public function checkStatus(string $activityId): array
    {
        return $this->fortyGuard->getStatus($activityId);
    }

    public function saveResult(Park $park, int $heatmapAnalysisId, array $result): EnvironmentalMetric
    {
        return EnvironmentalMetric::updateOrCreate(
            [
                'park_id' => $park->id,
                'heatmap_analysis_id' => $heatmapAnalysisId,
            ],
            [
                'data' => $result,
            ]
        );
    }

    public function saveResultByActivityId(string $activityId, array $result): EnvironmentalMetric
    {
        $metric = EnvironmentalMetric::where('activity_id', $activityId)->first();
        
        if (!$metric) {
            throw new \Exception("Environmental metric not found for activity_id: {$activityId}");
        }

        $metric->status = 'completed';
        $metric->data = $result;
        $metric->save();

        return $metric;
    }

    public function markAsFailed(string $activityId): EnvironmentalMetric
    {
        $metric = EnvironmentalMetric::where('activity_id', $activityId)->first();
        
        if (!$metric) {
            throw new \Exception("Environmental metric not found for activity_id: {$activityId}");
        }

        $metric->status = 'failed';
        $metric->save();

        return $metric;
    }

    public function analyzeTopParks(int $limit): array
    {
        // Get the latest completed heatmap analysis from database
        $analysis = HeatmapAnalysis::query()
            ->where('status', 'Completed')
            ->orderByDesc('created_at')
            ->first();

        if (!$analysis) {
            throw new \Exception('No completed heatmap analysis found. Please run heat analysis first.');
        }

        // Check if environmental analysis already exists for this heatmap
        $existingAnalysis = EnvironmentalMetric::query()
            ->where('heatmap_analysis_id', $analysis->id)
            ->where('status', 'pending')
            ->exists();

        if ($existingAnalysis) {
            throw new \Exception('Environmental analysis is already in progress for this heatmap. Please wait for completion or check the results.');
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

        foreach ($topParks as $parkAnalysis) {
            $park = $parkAnalysis->park;
            
            if (!$park->latitude || !$park->longitude) {
                continue;
            }

            $activityId = $this->submitAnalysis(
                $park,
                $parkAnalysis->average_temperature,
                $startDate,
                $startTime,
                $filterType
            );

            // Create pending record in database to link activity_id with park and heatmap analysis
            EnvironmentalMetric::create([
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
