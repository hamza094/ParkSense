<?php

namespace App\Actions;

use App\Models\HeatmapAnalysis;

class ManageHeatmapAnalysis
{
    /**
     * Create a new heatmap analysis record with pending status
     */
    public function create(array $data): HeatmapAnalysis
    {
        return HeatmapAnalysis::create([
            'activity_id' => $data['activity_id'],
            'aoi' => $data['aoi'],
            'park_ids' => $data['park_ids'] ?? null,
            'start_date' => $data['start_date'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'filter_type' => $data['filter_type'],
            'granularity' => $data['granularity'],
            'analytic_type' => $data['analytic_type'],
            'map_data' => null,
            'stats_data' => null,
            'status' => 'pending',
        ]);
    }

    /**
     * Update heatmap analysis record when completed
     */
    public function markAsCompleted(string $activityId, array $responseData): void
    {
        HeatmapAnalysis::where('activity_id', $activityId)->update([
            'map_data' => $responseData['result']['map_data'] ?? null,
            'stats_data' => $responseData['result']['stats_data'] ?? null,
            'status' => 'completed',
        ]);
    }
}
